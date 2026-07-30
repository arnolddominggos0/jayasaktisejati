<?php

namespace App\Console\Commands;

use App\Enums\TrackStatus;
use App\Enums\UnitAllocationStatus;
use App\Models\Container;
use App\Models\ContainerReadinessSession;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ContainersBackfillCommand extends Command
{
    protected $signature = 'containers:backfill {--commit : Actually write changes. Without this flag, the command only reports what WOULD happen (dry-run, no writes).}';

    protected $description = 'DATA-02: Backfill Container/Unit.container_id from legacy Unit.container_display, only where a matching Container Readiness Session already validates the container number.';

    /** @var array<string, mixed> */
    private array $summary = [
        'units_scanned' => 0,
        'containers_created' => 0,
        'containers_reused' => 0,
        'units_linked' => 0,
        'units_pending' => 0,
        'failures' => [],
    ];

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');

        $this->info($commit
            ? 'RUNNING IN COMMIT MODE — changes WILL be written to the database.'
            : 'RUNNING IN DRY-RUN MODE — no changes will be written. Pass --commit to apply.');

        Unit::query()
            ->whereNotNull('container_display')
            ->whereNull('container_id')
            ->with('shipment')
            ->chunkById(200, function ($units) use ($commit) {
                foreach ($units as $unit) {
                    $this->processUnit($unit, $commit);
                }
            });

        $this->printReport();

        return self::SUCCESS;
    }

    private function processUnit(Unit $unit, bool $commit): void
    {
        $this->summary['units_scanned']++;

        $shipment = $unit->shipment;
        $containerNo = strtoupper(trim((string) $unit->container_display));

        if ($containerNo === '') {
            $this->pending($unit, $shipment?->code, $containerNo, 'container_display kosong setelah trim — tidak ada data untuk dimigrasikan.');

            return;
        }

        if ($shipment === null) {
            $this->pending($unit, null, $containerNo, 'Unit tidak memiliki shipment (data tidak konsisten) — dilewati, tidak diasumsikan apa pun.');

            return;
        }

        // Anchor date: tanggal Handover track shipment ini — saat container_display
        // ditulis oleh modal Handover Depo. BUKAN tanggal hari ini, BUKAN tanggal
        // shipment dibuat — harus persis tanggal Readiness yang relevan.
        $handoverTrack = $shipment->tracks()
            ->where('status', TrackStatus::Handover->value)
            ->whereNotNull('tracked_at')
            ->first();

        if (! $handoverTrack) {
            $this->pending($unit, $shipment->code, $containerNo, 'Tidak ada ShipmentTrack Handover dengan tracked_at — tidak bisa menentukan tanggal Container Readiness Session yang relevan.');

            return;
        }

        $anchorDate = $handoverTrack->tracked_at->toDateString();

        $session = ContainerReadinessSession::whereDate('session_date', $anchorDate)->first();

        if (! $session) {
            $this->pending($unit, $shipment->code, $containerNo, "Tidak ada Container Readiness Session untuk tanggal {$anchorDate} (kemungkinan shipment diproses sebelum Container Readiness ada, atau sesi hari itu tidak pernah dibuat).");

            return;
        }

        if (! in_array($containerNo, $session->container_number_list, true)) {
            $this->pending($unit, $shipment->code, $containerNo, "Container {$containerNo} tidak terdaftar di daftar container Readiness sesi {$anchorDate} — tidak bisa dibuktikan valid terhadap SSOT.");

            return;
        }

        // Sampai titik ini: data legacy TERBUKTI valid terhadap SSOT Readiness
        // untuk tanggal yang benar. Aman membuat/menghubungkan Container.
        $existingContainer = Container::query()
            ->where('container_readiness_session_id', $session->id)
            ->where('container_no', $containerNo)
            ->first();

        if ($commit) {
            DB::transaction(function () use ($session, $containerNo, $unit) {
                $container = Container::resolveForSession($session, $containerNo);

                $unit->container_id = $container->id;
                $unit->allocation_status = UnitAllocationStatus::InContainer->value;
                $unit->saveQuietly();
            });
        }

        $existingContainer ? $this->summary['containers_reused']++ : $this->summary['containers_created']++;
        $this->summary['units_linked']++;
    }

    private function pending(Unit $unit, ?string $shipmentCode, string $containerNo, string $reason): void
    {
        $this->summary['units_pending']++;
        $this->summary['failures'][] = [
            'unit_id' => $unit->id,
            'shipment_code' => $shipmentCode,
            'container_display' => $containerNo,
            'reason' => $reason,
        ];
    }

    private function printReport(): void
    {
        $this->newLine();
        $this->line('=== DATA-02 Backfill Report ===');
        $this->table(['Metric', 'Count'], [
            ['Unit dipindai (container_display terisi, container_id kosong)', $this->summary['units_scanned']],
            ['Container dibuat baru', $this->summary['containers_created']],
            ['Container reused (sudah ada dari proses lain)', $this->summary['containers_reused']],
            ['Unit berhasil dihubungkan (container_id terisi)', $this->summary['units_linked']],
            ['Unit migration pending (TIDAK dihubungkan — lihat alasan)', $this->summary['units_pending']],
        ]);

        if (! empty($this->summary['failures'])) {
            $this->newLine();
            $this->warn('Detail unit migration pending (tidak ada asumsi/data sintetis dibuat untuk ini):');
            $this->table(
                ['Unit ID', 'Shipment', 'Container (legacy)', 'Alasan'],
                array_map(
                    fn (array $f) => [$f['unit_id'], $f['shipment_code'] ?? '—', $f['container_display'], $f['reason']],
                    $this->summary['failures']
                )
            );
        }
    }
}

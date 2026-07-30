<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use Illuminate\Console\Command;

class ContainersAuditCommand extends Command
{
    protected $signature = 'containers:audit
        {--shipment= : Limit to one shipment code}
        {--limit=200 : Max shipments to scan}';

    protected $description = 'DATA-02: Read-only comparison of legacy (container_display) vs engine (Container/container_id) data. Reports mismatches only.';

    public function handle(): int
    {
        $query = Shipment::query()
            ->whereHas('units', fn ($q) => $q->whereNotNull('container_display')->orWhereNotNull('container_id'))
            ->with(['units.container']);

        if ($code = $this->option('shipment')) {
            $query->where('code', $code);
        }

        $limit = (int) $this->option('limit');
        $rows = [];
        $counts = ['legacy_only' => 0, 'engine_only' => 0, 'consistent' => 0, 'conflicting' => 0];

        $query->limit($limit)->get()->each(function (Shipment $shipment) use (&$rows, &$counts) {
            foreach ($shipment->units as $unit) {
                $legacy = $unit->container_display;
                $engineContainerNo = $unit->container?->container_no;

                if (! $legacy && ! $engineContainerNo) {
                    continue; // neither set — not relevant to this audit
                }

                $status = match (true) {
                    $legacy && ! $engineContainerNo => 'legacy_only',
                    ! $legacy && $engineContainerNo => 'engine_only',
                    strtoupper((string) $legacy) === strtoupper((string) $engineContainerNo) => 'consistent',
                    default => 'conflicting',
                };

                $counts[$status]++;

                $rows[] = [
                    $shipment->code,
                    $unit->reg_no ?? $unit->chassis_no ?? "Unit #{$unit->id}",
                    $legacy ?? '—',
                    $engineContainerNo ?? '—',
                    strtoupper($status),
                ];
            }
        });

        if (empty($rows)) {
            $this->info('Tidak ada unit dengan container_display atau container_id terisi dalam cakupan ini.');

            return self::SUCCESS;
        }

        $this->table(['Shipment', 'Unit', 'Legacy (container_display)', 'Engine (Container.container_no)', 'Status'], $rows);

        $this->newLine();
        $this->table(['Kategori', 'Jumlah'], [
            ['legacy_only (belum di-backfill — kandidat containers:backfill)', $counts['legacy_only']],
            ['engine_only (sudah pakai Container Allocation Workspace, container_display belum diisi)', $counts['engine_only']],
            ['consistent (kedua sisi cocok)', $counts['consistent']],
            ['CONFLICTING (berbeda — perlu investigasi manual, TIDAK diperbaiki otomatis)', $counts['conflicting']],
        ]);

        if ($counts['conflicting'] > 0) {
            $this->error("Ditemukan {$counts['conflicting']} unit dengan data KONFLIK antara legacy dan engine. Sprint ini TIDAK memperbaiki — hanya melaporkan, sesuai Scope 6.");
        }

        return self::SUCCESS;
    }
}

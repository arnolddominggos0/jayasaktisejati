<?php

namespace Database\Seeders;

use App\Enums\VoyageRegistryStatus;
use App\Models\ShippingLine;
use App\Models\Vessel;
use App\Models\VesselPlan;
use App\Models\Voyage;
use App\Models\VoyageScheduleHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Backfill Schedule History — Februari 2026
 *
 * Mengisi tabel voyage_schedule_histories dengan data historis
 * jadwal voyage Februari 2026 yang sudah diverifikasi.
 *
 * Strategi:
 *   - Cari voyage berdasarkan voyage_no
 *   - Jika ditemukan   → gunakan voyage yang ada
 *   - Jika tidak ada   → CREATE voyage historis (backfill memerlukan anchor FK)
 *   - Semua insert menggunakan updateOrCreate → idempotent, aman dijalankan ulang
 *   - saveQuietly() digunakan agar model hooks tidak double-fire
 *
 * TIDAK mengubah:
 *   - shipment, units, delivery
 *   - vessel_plan, vessel_plan_items
 *   - cargo_plan
 *
 * HANYA mengisi:
 *   - voyage_schedule_histories (draft, final, actual)
 *   - atd_at / ata_at / cargo_actual voyage jika masih kosong
 */
class VoyageScheduleHistoryFeb2026Seeder extends Seeder
{
    // 
    // Data historis yang sudah diverifikasi
    // 

    private array $voyages = [

        [
            'voyage_no' => '307',
            'vessel' => 'Tanto Jaya',

            'draft' => [
                'etd' => '2026-03-02',
                'eta' => null,
            ],

            'final' => [
                'etd' => '2026-03-04',
                'eta' => '2026-03-16',
            ],

            'actual' => [
                'atd' => '2026-03-04',
                'ata' => '2026-03-15',
                'cargo' => 19,
            ],
        ],

        [
            'voyage_no' => '245',
            'vessel' => 'Tanto Tangguh',

            'draft' => [
                'etd' => '2026-03-09',
                'eta' => null,
            ],

            'final' => [
                'etd' => '2026-03-11',
                'eta' => '2026-03-23',
            ],

            'actual' => [
                'atd' => '2026-03-07',
                'ata' => '2026-03-18',
                'cargo' => 33,
            ],
        ],

        [
            'voyage_no' => '158',
            'vessel' => 'Tanto Salam',

            'draft' => [
                'etd' => '2026-03-16',
                'eta' => null,
            ],

            'final' => [
                'etd' => '2026-03-18',
                'eta' => '2026-03-30',
            ],

            'actual' => [
                'atd' => '2026-03-19',
                'ata' => '2026-03-28',
                'cargo' => 42,
            ],
        ],

        [
            'voyage_no' => '152',
            'vessel' => 'Tanto Sejahtera',

            'draft' => [
                'etd' => '2026-03-26',
                'eta' => null,
            ],

            'final' => [
                'etd' => '2026-03-28',
                'eta' => '2026-04-09',
            ],

            'actual' => [
                'atd' => '2026-03-25',
                'ata' => '2026-04-03',
                'cargo' => 7,
            ],
        ],

        [
            'voyage_no' => '246',
            'vessel' => 'Tanto Tangguh',

            'draft' => [
                'etd' => '2026-03-31',
                'eta' => null,
            ],

            'final' => [
                'etd' => '2026-03-31',
                'eta' => '2026-04-12',
            ],

            'actual' => [
                'atd' => '2026-03-31',
                'ata' => '2026-04-09',
                'cargo' => 22,
            ],
        ],
    ];

    // 

    public function run(): void
    {
        $this->line('');
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║   VESSEL PLAN SCHEDULE HISTORY BACKFILL — MAR 2026      ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->line('');

        // Resolve shared references
        $shippingLineId = ShippingLine::where('code', 'TANTO')->value('id');
        $samplePlan     = VesselPlan::orderBy('id')->first();
        $polId          = $samplePlan?->pol_id ?? 1;
        $podId          = $samplePlan?->pod_id ?? 2;

        // Audit
        $this->line('PHASE 1 — AUDIT VOYAGE EXISTING');
        $this->line(str_repeat('─', 62));

        $auditRows   = [];   // ['voyage_no' => ..., 'voyage' => Voyage|null, 'action' => found|create|skip]
        $auditPassed = true;

        foreach ($this->voyages as $row) {
            $vno    = $row['voyage_no'];
            $voyage = Voyage::where('voyage_no', $vno)->first();

            if ($voyage) {
                $this->line(sprintf(
                    '✓ Voyage %s ditemukan (id=%d, vessel=%s, etd=%s)',
                    $vno,
                    $voyage->id,
                    $voyage->vessel?->name ?? 'null',
                    $voyage->etd?->format('Y-m-d') ?? 'null',
                ));
                $auditRows[] = ['row' => $row, 'voyage' => $voyage, 'action' => 'found'];
                continue;
            }

            // Voyage tidak ada → cek vessel tersedia
            $vessel = Vessel::where('name', $row['vessel'])->first();

            if (! $vessel) {
                $this->warn(sprintf(
                    '✗ Voyage %s TIDAK DITEMUKAN dan vessel [%s] tidak ada di master — SKIP',
                    $vno,
                    $row['vessel']
                ));
                $auditRows[]  = ['row' => $row, 'voyage' => null, 'action' => 'skip'];
                $auditPassed  = false;
                continue;
            }

            $this->warn(sprintf(
                '! Voyage %s tidak ditemukan — akan dibuat sebagai historical record (vessel %s id=%d)',
                $vno,
                $vessel->name,
                $vessel->id
            ));
            $auditRows[] = ['row' => $row, 'voyage' => null, 'action' => 'create', 'vessel' => $vessel];
        }

        $this->line('');

        // Backfill
        $this->line('PHASE 2–6 — BACKFILL SCHEDULE HISTORIES');
        $this->line(str_repeat('─', 62));
        $this->line(sprintf(
            '%-6s │ %-14s │ %-14s │ %-14s │ %s',
            'Voyage',
            'Draft',
            'Final',
            'Actual',
            'Status'
        ));
        $this->line(str_repeat('─', 68));

        $stats = [
            'found'           => 0,
            'created'         => 0,
            'skipped'         => 0,
            'atd_ata_updated' => 0,
            'draft'           => 0,
            'final'           => 0,
            'actual'          => 0,
        ];

        foreach ($auditRows as $audit) {
            $row    = $audit['row'];
            $vno    = $row['voyage_no'];
            $action = $audit['action'];

            // Skip jika vessel tidak ada
            if ($action === 'skip') {
                $stats['skipped']++;
                $this->line(sprintf(
                    '%-6s │ %-14s │ %-14s │ %-14s │ %s',
                    $vno,
                    '—',
                    '—',
                    '—',
                    'SKIP'
                ));
                continue;
            }

            try {
                DB::transaction(function () use (
                    &$audit,
                    $row,
                    $vno,
                    $action,
                    &$stats,
                    $shippingLineId,
                    $polId,
                    $podId
                ) {
                    $voyage = $audit['voyage'];

                    // CREATE historical voyage jika belum ada
                    if ($action === 'create') {
                        /** @var Vessel $vessel */
                        $vessel  = $audit['vessel'];
                        $newV    = new Voyage();

                        $newV->forceFill([
                            'voyage_no'        => $vno,
                            'vessel_id'        => $vessel->id,
                            'shipping_line_id' => $shippingLineId,
                            'pol_id'           => $polId,
                            'pod_id'           => $podId,
                            // etd/eta dari Final Schedule
                            'etd'              => $row['final']['etd'],
                            'eta'              => $row['final']['eta'],
                            // actual
                            'atd_at'           => $row['actual']['atd'],
                            'ata_at'           => $row['actual']['ata'],
                            'cargo_actual'     => $row['actual']['cargo'],
                            // sailing hari aktual
                            'actual_sailing_days' => VoyageScheduleHistory::calcSailingDays(
                                $row['actual']['atd'],
                                $row['actual']['ata']
                            ),
                            'period_month'     => Carbon::parse($row['final']['etd'])->startOfMonth(),
                            'registry_status'  => VoyageRegistryStatus::COMPLETED,
                        ]);

                        // saveQuietly → model hooks tidak fire, kita kontrol manual
                        $newV->saveQuietly();
                        $voyage         = $newV;
                        $audit['voyage'] = $newV;
                        $stats['created']++;
                    } else {
                        $stats['found']++;

                        // Update atd_at/ata_at jika kosong
                        $needsSave = false;

                        if (blank($voyage->atd_at)) {
                            $voyage->atd_at = $row['actual']['atd'];
                            $needsSave = true;
                        }
                        if (blank($voyage->ata_at)) {
                            $voyage->ata_at = $row['actual']['ata'];
                            $needsSave = true;
                        }
                        if (blank($voyage->cargo_actual) && filled($row['actual']['cargo'])) {
                            $voyage->cargo_actual = $row['actual']['cargo'];
                            $needsSave = true;
                        }

                        if ($needsSave) {
                            $voyage->saveQuietly();
                            $stats['atd_ata_updated']++;
                        }
                    }

                    // Draft History
                    $draftEtd = $row['draft']['etd'];
                    $draftEta = $row['draft']['eta'];   // null untuk draft

                    VoyageScheduleHistory::updateOrCreate(
                        [
                            'voyage_id'     => $voyage->id,
                            'schedule_type' => 'draft',
                        ],
                        [
                            'etd'          => $draftEtd,
                            'eta'          => $draftEta,
                            'sailing_days' => VoyageScheduleHistory::calcSailingDays($draftEtd, $draftEta),
                            'notes'        => 'Historical Draft Schedule',
                            'captured_at'  => Carbon::parse($draftEtd)->startOfDay(),
                            'captured_by'  => 'VoyageScheduleHistoryFeb2026Seeder',
                        ]
                    );
                    $stats['draft']++;

                    // Final History
                    $finalEtd = $row['final']['etd'];
                    $finalEta = $row['final']['eta'];

                    VoyageScheduleHistory::updateOrCreate(
                        [
                            'voyage_id'     => $voyage->id,
                            'schedule_type' => 'final',
                        ],
                        [
                            'etd'          => $finalEtd,
                            'eta'          => $finalEta,
                            'sailing_days' => VoyageScheduleHistory::calcSailingDays($finalEtd, $finalEta),
                            'notes'        => 'Historical Final Schedule',
                            'captured_at'  => Carbon::parse($finalEtd)->startOfDay(),
                            'captured_by'  => 'VoyageScheduleHistoryFeb2026Seeder',
                        ]
                    );
                    $stats['final']++;

                    // Actual History
                    $actualAtd = $row['actual']['atd'];
                    $actualAta = $row['actual']['ata'];

                    VoyageScheduleHistory::updateOrCreate(
                        [
                            'voyage_id'     => $voyage->id,
                            'schedule_type' => 'actual',
                        ],
                        [
                            'etd'          => $actualAtd,
                            'eta'          => $actualAta,
                            'sailing_days' => VoyageScheduleHistory::calcSailingDays($actualAtd, $actualAta),
                            'notes'        => 'Historical Actual Schedule',
                            'captured_at'  => Carbon::parse($actualAta)->startOfDay(),
                            'captured_by'  => 'VoyageScheduleHistoryFeb2026Seeder',
                        ]
                    );
                    $stats['actual']++;
                });

                $statusNote = match ($action) {
                    'create' => 'dibuat baru (id=' . $audit['voyage']->id . ')',
                    'found'  => 'id=' . $audit['voyage']->id,
                    default  => '',
                };

                $this->line(sprintf(
                    '%-6s │ %-14s │ %-14s │ %-14s │ %s',
                    $vno,
                    '✓ Draft',
                    '✓ Final',
                    '✓ Actual',
                    $statusNote
                ));
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    '%-6s │ %-14s │ GAGAL: %s',
                    $vno,
                    'ERROR',
                    $e->getMessage()
                ));
            }
        }

        // Summary
        $this->line('');
        $this->line(str_repeat('─', 62));
        $this->line('PHASE 7 — SUMMARY');
        $this->line(str_repeat('─', 62));
        $this->line('');
        $this->line(sprintf('%-26s : %d', 'Voyages ditemukan', $stats['found']));
        $this->line(sprintf('%-26s : %d', 'Voyages dibuat (historical)', $stats['created']));
        $this->line(sprintf('%-26s : %d', 'Voyages di-skip', $stats['skipped']));
        $this->line(sprintf('%-26s : %d', 'ATD/ATA di-update', $stats['atd_ata_updated']));
        $this->line('');
        $this->line(sprintf('%-26s : %d', 'Draft histories', $stats['draft']));
        $this->line(sprintf('%-26s : %d', 'Final histories', $stats['final']));
        $this->line(sprintf('%-26s : %d', 'Actual histories', $stats['actual']));
        $this->line('');

        $total = $stats['draft'] + $stats['final'] + $stats['actual'];
        $this->line(sprintf('%-26s : %d', 'Total histories', $total));
        $this->line('');

        if ($total === 12) {
            $this->info('✅ Backfill selesai — 4 voyage, 12 history records.');
        } else {
            $this->warn('⚠ Backfill selesai dengan hasil partial (' . $total . '/12 records).');
        }

        $this->line('');
    }

    // 
    // Helper — output shortcuts
    // 

    private function line(string $text): void
    {
        $this->command->line($text);
    }

    private function info(string $text): void
    {
        $this->command->info($text);
    }

    private function warn(string $text): void
    {
        $this->command->warn($text);
    }

    private function error(string $text): void
    {
        $this->command->error($text);
    }
}

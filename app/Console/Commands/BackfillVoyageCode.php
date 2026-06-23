<?php

namespace App\Console\Commands;

use App\Models\Port;
use App\Models\Voyage;
use App\Supports\RouteCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVoyageCode extends Command
{
    protected $signature = 'voyages:backfill-code
                            {--dry-run : Tampilkan hasil tanpa menyimpan ke database}';

    protected $description = 'Backfill voyages.route_code (business identifier) lalu regenerate voyages.code';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN — tidak ada perubahan yang disimpan.');
        }

        $voyages = Voyage::with('vessel')->get();
        $total   = $voyages->count();

        $this->line('');
        $this->line("  Total voyage: {$total}");
        $this->line('');

        // ── PASS 1: Fill route_code ──────────────────────────────────────────
        $this->info('PASS 1 — route_code backfill');

        $routeFilled  = 0;
        $routeSkipped = 0;
        $routeRows    = [];

        foreach ($voyages as $voyage) {
            if (! empty($voyage->route_code)) {
                $routeSkipped++;
                continue;
            }

            // Look up port UNLOCODE codes, then resolve voyage route_code via RouteCode registry
            $polCode  = Port::where('id', $voyage->pol_id)->value('code') ?? '';
            $podCode  = Port::where('id', $voyage->pod_id)->value('code') ?? '';
            $resolved = RouteCode::voyageFromPortCodes($polCode, $podCode);

            if (! $resolved) {
                $routeSkipped++;
                $routeRows[] = [
                    $voyage->id,
                    $voyage->voyage_no ?? '—',
                    "{$polCode}-{$podCode}",
                    '⚠ Tidak ada mapping di RouteCode — skip',
                ];
                continue;
            }

            $routeRows[] = [
                $voyage->id,
                $voyage->voyage_no ?? '—',
                $mapKey,
                $isDryRun ? "(dry) → {$resolved}" : "✓ → {$resolved}",
            ];

            // Always update in-memory so Pass 2 dry-run simulation is accurate
            $voyage->route_code = $resolved;

            if (! $isDryRun) {
                DB::table('voyages')->where('id', $voyage->id)->update(['route_code' => $resolved]);
            }

            $routeFilled++;
        }

        if (! empty($routeRows)) {
            $this->table(['ID', 'Voyage No', 'pol_id-pod_id', 'Hasil'], $routeRows);
        }

        $this->table(
            ['Metric', 'Jumlah'],
            [['Route Filled', $routeFilled], ['Route Skipped', $routeSkipped]]
        );

        // Reload from DB for the live run so relations are fresh
        if (! $isDryRun) {
            $voyages = Voyage::with('vessel')->get();
        }

        // ── PASS 2: Regenerate voyages.code ─────────────────────────────────
        $this->line('');
        $this->info('PASS 2 — code regeneration');

        $codeUpdated    = 0;
        $codeSkipped    = 0;
        $collisions     = [];
        $sampleBefore   = [];
        $codeRows       = [];

        foreach ($voyages as $voyage) {
            $before    = $voyage->code;
            $generated = Voyage::generateCode($voyage);

            if (! $generated) {
                $codeSkipped++;
                $codeRows[] = [
                    $voyage->id,
                    $voyage->voyage_no ?? '—',
                    $before ?? '(null)',
                    '—',
                    '⚠ Skip — route_code atau vessel.code kosong',
                ];
                continue;
            }

            if ($before === $generated) {
                $codeSkipped++;
                $codeRows[] = [
                    $voyage->id,
                    $voyage->voyage_no ?? '—',
                    $before,
                    $generated,
                    '✓ Sudah benar',
                ];
                continue;
            }

            $conflict = Voyage::where('code', $generated)->where('id', '!=', $voyage->id)->first();
            if ($conflict) {
                $collisions[] = compact('voyage', 'generated', 'conflict');
                $codeSkipped++;
                $codeRows[] = [
                    $voyage->id,
                    $voyage->voyage_no ?? '—',
                    $before ?? '(null)',
                    $generated,
                    '✗ COLLISION id=' . $conflict->id,
                ];
                continue;
            }

            if (count($sampleBefore) < 5) {
                $sampleBefore[] = [
                    $voyage->id,
                    $voyage->voyage_no ?? '—',
                    $voyage->vessel?->name ?? '—',
                    $before ?? '(null)',
                    $generated,
                ];
            }

            $codeRows[] = [
                $voyage->id,
                $voyage->voyage_no ?? '—',
                $before ?? '(null)',
                $generated,
                $isDryRun ? '(dry) akan diupdate' : '✓ Diupdate',
            ];

            if (! $isDryRun) {
                DB::table('voyages')->where('id', $voyage->id)->update(['code' => $generated]);
            }

            $codeUpdated++;
        }

        $this->table(['ID', 'Voyage No', 'Code Lama', 'Code Baru', 'Hasil'], $codeRows);

        $this->line('');
        $this->table(
            ['Metric', 'Jumlah'],
            [
                ['Total',      $total],
                ['Route Filled', $routeFilled],
                ['Code Updated', $codeUpdated],
                ['Skipped',    $codeSkipped],
                ['Collisions', count($collisions)],
            ]
        );

        // ── Sample before/after ──────────────────────────────────────────────
        if (! empty($sampleBefore)) {
            $this->line('');
            $this->info('SAMPLE — 5 code sebelum/sesudah:');
            $this->table(
                ['ID', 'Voyage No', 'Vessel', 'Sebelum', 'Sesudah'],
                $sampleBefore
            );
        }

        // ── Collision report ─────────────────────────────────────────────────
        if (! empty($collisions)) {
            $this->line('');
            $this->error('COLLISION REPORT:');
            $this->table(
                ['Voyage ID', 'Voyage No', 'Generated', 'Conflict ID', 'Conflict No'],
                array_map(fn($c) => [
                    $c['voyage']->id,
                    $c['voyage']->voyage_no,
                    $c['generated'],
                    $c['conflict']->id,
                    $c['conflict']->voyage_no,
                ], $collisions)
            );
            return self::FAILURE;
        }

        $this->line('');

        if ($isDryRun) {
            $this->info('Dry run selesai. Jalankan tanpa --dry-run untuk menyimpan.');
        } else {
            $this->info('Backfill voyages.route_code + voyages.code selesai.');
        }

        return self::SUCCESS;
    }
}

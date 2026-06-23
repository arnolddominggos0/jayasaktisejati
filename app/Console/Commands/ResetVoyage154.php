<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetVoyage154 extends Command
{
    protected $signature = 'voyage154:reset
                            {--force : Eksekusi. Tanpa flag ini hanya dry-run.}';

    protected $description = 'Reset Voyage 154 (id=2): hapus shipment, unit, track, inspection, checkpoint. Voyage tetap ada.';

    private const VOYAGE_ID = 2;

    public function handle(): int
    {
        $isDryRun = ! $this->option('force');

        if ($isDryRun) {
            $this->warn('DRY RUN — tidak ada data dihapus. Gunakan --force untuk eksekusi.');
        } else {
            $this->warn('EXECUTE MODE — data akan dihapus secara permanen.');
        }

        $this->line('');

        // ── Collect IDs ──────────────────────────────────────────────────────
        $voyageId    = self::VOYAGE_ID;
        $shipmentIds = DB::table('shipments')->where('voyage_id', $voyageId)->pluck('id');
        $unitIds     = DB::table('units')->whereIn('shipment_id', $shipmentIds)->pluck('id');
        $inspIds     = DB::table('unit_inspections')->whereIn('unit_id', $unitIds)->pluck('id');

        // ── Count for report ─────────────────────────────────────────────────
        $counts = [
            'unit_inspection_items' => DB::table('unit_inspection_items')->whereIn('unit_inspection_id', $inspIds)->count(),
            'unit_inspections'      => $inspIds->count(),
            'shipment_tracks'       => DB::table('shipment_tracks')->whereIn('shipment_id', $shipmentIds)->count(),
            'units'                 => $unitIds->count(),
            'shipments'             => $shipmentIds->count(),
            'voyage_checkpoints'    => DB::table('voyage_checkpoints')->where('voyage_id', $voyageId)->count(),
            'voyage_milestones'     => DB::table('voyage_milestones')->where('voyage_id', $voyageId)->count(),
            'vessel_checks'         => DB::table('vessel_checks')->where('voyage_id', $voyageId)->count(),
        ];

        // ── DRY-RUN report ───────────────────────────────────────────────────
        $this->info("Voyage id={$voyageId} — record yang akan dihapus:");
        $this->table(
            ['Table', 'Count'],
            collect($counts)->map(fn($c, $t) => [$t, $c])->values()->toArray()
        );

        $totalToDelete = array_sum($counts);
        $this->line("  Total: {$totalToDelete} records");
        $this->line('');

        $this->info("Voyage id={$voyageId} — record yang DIPERTAHANKAN:");
        $this->line("  voyages (id=2)          : 1");
        $this->line("  vessel_plan_items        : " . DB::table('vessel_plan_items')->where('voyage_id', $voyageId)->count());
        $this->line('');

        if ($isDryRun) {
            $this->comment('Jalankan: php artisan voyage154:reset --force  untuk eksekusi.');
            return self::SUCCESS;
        }

        // ── EXECUTE ──────────────────────────────────────────────────────────
        $deleted = [];

        DB::transaction(function () use ($voyageId, $shipmentIds, $unitIds, $inspIds, &$deleted) {

            // 1. unit_inspection_items
            $deleted['unit_inspection_items'] = DB::table('unit_inspection_items')
                ->whereIn('unit_inspection_id', $inspIds)
                ->delete();

            // 2. unit_inspections
            $deleted['unit_inspections'] = DB::table('unit_inspections')
                ->whereIn('unit_id', $unitIds)
                ->delete();

            // 3. shipment_tracks
            $deleted['shipment_tracks'] = DB::table('shipment_tracks')
                ->whereIn('shipment_id', $shipmentIds)
                ->delete();

            // 4. units
            $deleted['units'] = DB::table('units')
                ->whereIn('shipment_id', $shipmentIds)
                ->delete();

            // 5. shipments
            $deleted['shipments'] = DB::table('shipments')
                ->where('voyage_id', $voyageId)
                ->delete();

            // 6. voyage_checkpoints
            $deleted['voyage_checkpoints'] = DB::table('voyage_checkpoints')
                ->where('voyage_id', $voyageId)
                ->delete();

            // 7. voyage_milestones
            $deleted['voyage_milestones'] = DB::table('voyage_milestones')
                ->where('voyage_id', $voyageId)
                ->delete();

            // 8. vessel_checks (cascade vessel_check_cases if any)
            $vcIds = DB::table('vessel_checks')->where('voyage_id', $voyageId)->pluck('id');
            if ($vcIds->isNotEmpty()) {
                DB::table('vessel_check_cases')->whereIn('vessel_check_id', $vcIds)->delete();
            }
            $deleted['vessel_checks'] = DB::table('vessel_checks')
                ->where('voyage_id', $voyageId)
                ->delete();
        });

        $this->line('');
        $this->info('DELETED:');
        $this->table(
            ['Table', 'Deleted'],
            collect($deleted)->map(fn($c, $t) => [$t, $c])->values()->toArray()
        );

        // ── VALIDATION ───────────────────────────────────────────────────────
        $this->line('');
        $this->info('VALIDATION:');

        $voyage        = DB::table('voyages')->find($voyageId);
        $shipCount     = DB::table('shipments')->where('voyage_id', $voyageId)->count();
        $unitCount     = DB::table('units')->whereIn('shipment_id',
            DB::table('shipments')->where('voyage_id', $voyageId)->pluck('id')
        )->count();
        $trackCount    = DB::table('shipment_tracks')->whereIn('shipment_id',
            DB::table('shipments')->where('voyage_id', $voyageId)->pluck('id')
        )->count();
        $inspCount     = DB::table('unit_inspections')->whereIn('unit_id',
            DB::table('units')->whereIn('shipment_id',
                DB::table('shipments')->where('voyage_id', $voyageId)->pluck('id')
            )->pluck('id')
        )->count();
        $cpCount       = DB::table('voyage_checkpoints')->where('voyage_id', $voyageId)->count();

        $checks = [
            ['Voyage id=2 masih ada',       $voyage !== null,   $voyage !== null    ? '✓ YES'   : '✗ MISSING'],
            ['voyage_no = 154',             $voyage?->voyage_no === '154', $voyage?->voyage_no === '154' ? '✓' : '✗ ' . $voyage?->voyage_no],
            ['code = VOY154TTSTJKTMND',     $voyage?->code === 'VOY154TTSTJKTMND', $voyage?->code === 'VOY154TTSTJKTMND' ? '✓' : '✗ ' . $voyage?->code],
            ['shipments = 0',               $shipCount === 0,   "count={$shipCount}"],
            ['units = 0',                   $unitCount === 0,   "count={$unitCount}"],
            ['shipment_tracks = 0',         $trackCount === 0,  "count={$trackCount}"],
            ['unit_inspections = 0',        $inspCount === 0,   "count={$inspCount}"],
            ['voyage_checkpoints = 0',      $cpCount === 0,     "count={$cpCount}"],
        ];

        $allPassed = true;
        foreach ($checks as [$label, $pass, $result]) {
            if (! $pass) $allPassed = false;
            $this->line('  ' . ($pass ? '<fg=green>✓</fg=green>' : '<fg=red>✗</fg=red>') . " {$label}: {$result}");
        }

        $this->line('');

        if ($allPassed) {
            $this->info('✓ Semua validasi PASS. Voyage 154 siap untuk import ulang.');
        } else {
            $this->error('✗ Beberapa validasi FAIL. Periksa di atas.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

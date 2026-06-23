<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeSyntheticVoyages extends Command
{
    protected $signature = 'voyages:purge-synthetic
                            {--force : Eksekusi purge. Tanpa flag ini hanya dry-run.}';

    protected $description = 'Purge voyage synthetic (VY-YYYYMM-X) beserta child record. Default: dry-run.';

    // Child tables to DELETE (cascade with the voyage)
    private const CHILD_TABLES = [
        'voyage_checkpoints',
        'voyage_milestones',
        'voyage_schedule_histories',
        'voyage_delay_logs',
        'sla_results',
        'vessel_check_cases',
        'vessel_checks',
    ];

    public function handle(): int
    {
        $isForce = $this->option('force');

        if (! $isForce) {
            $this->warn('DRY RUN — tidak ada data dihapus. Gunakan --force untuk eksekusi.');
            $this->line('');
        }

        // ── Fetch synthetics ────────────────────────────────────────────────
        $voyages = Voyage::where('voyage_no', 'like', 'VY-%')
            ->with('vessel')
            ->orderBy('id')
            ->get();

        if ($voyages->isEmpty()) {
            $this->info('Tidak ada voyage synthetic ditemukan. Database bersih.');
            return self::SUCCESS;
        }

        $this->line("Synthetic voyages found: {$voyages->count()}");
        $this->line('');

        // ── Classify ────────────────────────────────────────────────────────
        $safe    = [];
        $warning = [];

        foreach ($voyages as $v) {
            $sh   = DB::table('shipments')->where('voyage_id', $v->id)->count();
            $hasCargo = ($v->cargo_actual !== null && $v->cargo_actual > 0);

            $entry = [
                'voyage'    => $v,
                'shipments' => $sh,
                'hasCargo'  => $hasCargo,
            ];

            if ($sh > 0 || $hasCargo) {
                $warning[] = $entry;
            } else {
                $safe[] = $entry;
            }
        }

        // ── SAFE group ──────────────────────────────────────────────────────
        if (! empty($safe)) {
            $this->info('SAFE (tidak ada shipment atau cargo_actual):');
            foreach ($safe as $e) {
                $this->line("  - Voyage {$e['voyage']->id} ({$e['voyage']->voyage_no} · {$e['voyage']->vessel?->name})");
            }
            $this->line('');
        }

        // ── WARNING group ───────────────────────────────────────────────────
        if (! empty($warning)) {
            $this->warn('WARNING (memiliki shipment atau cargo_actual):');
            foreach ($warning as $e) {
                $flags = [];
                if ($e['shipments'] > 0) $flags[] = "{$e['shipments']} shipment";
                if ($e['hasCargo'])      $flags[] = "cargo_actual={$e['voyage']->cargo_actual}";
                $this->line("  - Voyage {$e['voyage']->id} ({$e['voyage']->voyage_no} · {$e['voyage']->vessel?->name}) [" . implode(', ', $flags) . ']');
            }
            $this->line('');

            if (! $isForce) {
                $this->comment('WARNING voyages akan di-purge beserta relasi. Shipment tidak dihapus — voyage_id di-null-kan.');
            }
        }

        // ── Child record preview ────────────────────────────────────────────
        $allIds = $voyages->pluck('id')->toArray();
        $childCounts = [];
        foreach (self::CHILD_TABLES as $table) {
            $cnt = DB::table($table)->whereIn('voyage_id', $allIds)->count();
            if ($cnt > 0) $childCounts[$table] = $cnt;
        }
        $shipmentCount = DB::table('shipments')->whereIn('voyage_id', $allIds)->count();

        if (! empty($childCounts) || $shipmentCount > 0) {
            $this->line('Child records yang terdampak:');
            foreach ($childCounts as $table => $cnt) {
                $this->line("  {$table}: {$cnt} baris akan DIHAPUS");
            }
            if ($shipmentCount > 0) {
                $this->line("  shipments: {$shipmentCount} baris — voyage_id akan di-NULL (tidak dihapus)");
            }
            $this->line('');
        }

        if (! $isForce) {
            $this->line('No data deleted.');
            $this->line('');
            $this->comment('Jalankan: php artisan voyages:purge-synthetic --force  untuk eksekusi.');
            return self::SUCCESS;
        }

        // ── EXECUTE ─────────────────────────────────────────────────────────
        $this->warn('Memulai purge...');

        DB::transaction(function () use ($allIds) {

            // Step 1 — Putus relasi VesselPlanItem → voyage
            $vpItems = DB::table('vessel_plan_items')
                ->whereIn('voyage_id', $allIds)
                ->update(['voyage_id' => null]);
            $this->line("  vessel_plan_items.voyage_id nulled: {$vpItems}");

            // Step 2 — Null-kan shipment.voyage_id (jangan hapus shipment)
            $shUpdated = DB::table('shipments')
                ->whereIn('voyage_id', $allIds)
                ->update(['voyage_id' => null]);
            if ($shUpdated > 0) {
                $this->line("  shipments.voyage_id nulled: {$shUpdated} (shipment dipertahankan)");
            }

            // Step 3 — Delete child records
            foreach (self::CHILD_TABLES as $table) {
                $deleted = DB::table($table)->whereIn('voyage_id', $allIds)->delete();
                if ($deleted > 0) {
                    $this->line("  {$table} deleted: {$deleted}");
                }
            }

            // Step 4 — Delete voyages
            $deleted = Voyage::whereIn('id', $allIds)->delete();
            $this->line("  voyages deleted: {$deleted}");
        });

        $this->line('');

        // ── VALIDATION ──────────────────────────────────────────────────────
        $this->info('VALIDATION:');

        $remainVy   = Voyage::where('voyage_no', 'like', 'VY-%')->count();
        $remainCode = Voyage::where('code', 'like', 'VOYVY-%')->count();

        $this->line("  Voyage::where('voyage_no', 'like', 'VY-%')->count()  = {$remainVy}");
        $this->line("  Voyage::where('code', 'like', 'VOYVY-%')->count()    = {$remainCode}");

        if ($remainVy === 0 && $remainCode === 0) {
            $this->info('  ✓ Validasi PASS — tidak ada voyage synthetic tersisa.');
        } else {
            $this->error("  ✗ Validasi FAIL — masih ada {$remainVy} VY- voyage dan {$remainCode} VOYVY- code.");
            return self::FAILURE;
        }

        $this->line('');
        $this->info('Purge selesai.');
        return self::SUCCESS;
    }
}

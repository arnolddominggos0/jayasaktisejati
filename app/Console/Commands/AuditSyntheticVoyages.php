<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditSyntheticVoyages extends Command
{
    protected $signature = 'voyages:audit-synthetic';

    protected $description = 'Audit seluruh voyage synthetic (VY-YYYYMM-X) beserta relasi yang dimilikinya';

    public function handle(): int
    {
        $voyages = Voyage::where('voyage_no', 'like', 'VY-%')
            ->with('vessel')
            ->orderBy('id')
            ->get();

        if ($voyages->isEmpty()) {
            $this->info('Tidak ada voyage synthetic ditemukan.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->warn("  Voyage synthetic ditemukan: {$voyages->count()}");
        $this->line('');

        $rows   = [];
        $safe    = [];
        $warning = [];

        foreach ($voyages as $v) {
            $sh  = DB::table('shipments')->where('voyage_id', $v->id)->count();
            $vc  = DB::table('vessel_checks')->where('voyage_id', $v->id)->count();
            $ms  = DB::table('voyage_milestones')->where('voyage_id', $v->id)->count();
            $cp  = DB::table('voyage_checkpoints')->where('voyage_id', $v->id)->count();
            $sch = DB::table('voyage_schedule_histories')->where('voyage_id', $v->id)->count();
            $dl  = DB::table('voyage_delay_logs')->where('voyage_id', $v->id)->count();
            $sla = DB::table('sla_results')->where('voyage_id', $v->id)->count();

            $hasData = $sh > 0 || $vc > 0 || ($v->cargo_actual !== null && $v->cargo_actual > 0);
            $status  = $hasData ? '⚠ WARNING' : '✓ SAFE';

            $rows[] = [
                $v->id,
                $v->voyage_no,
                $v->code ?? '—',
                $v->vessel_plan_id ?? '—',
                $v->vessel_plan_item_id ?? '—',
                $sh,
                $v->cargo_actual ?? 0,
                $cp,
                $sch,
                $dl,
                $status,
            ];

            if ($hasData) {
                $warning[] = $v->id;
            } else {
                $safe[] = $v->id;
            }
        }

        $this->table(
            ['ID', 'voyage_no', 'code', 'vp_id', 'vp_item_id', 'shipments', 'cargo_actual', 'checkpoints', 'sched_hist', 'delay_logs', 'Status'],
            $rows
        );

        $this->line('');
        $this->line('  <fg=green>SAFE</fg=green>    (ids): ' . (empty($safe)    ? '(tidak ada)' : implode(', ', $safe)));
        $this->line('  <fg=yellow>WARNING</fg=yellow> (ids): ' . (empty($warning) ? '(tidak ada)' : implode(', ', $warning)));
        $this->line('');
        $this->comment('Jalankan: php artisan voyages:purge-synthetic          → dry-run');
        $this->comment('Jalankan: php artisan voyages:purge-synthetic --force  → execute');

        return self::SUCCESS;
    }
}

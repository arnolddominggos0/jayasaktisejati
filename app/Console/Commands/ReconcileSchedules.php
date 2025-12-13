<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShippingSchedule;
use App\Enums\ScheduleState;
use Illuminate\Support\Facades\DB;

class ReconcileSchedules extends Command
{
    protected $signature = 'voyage:reconcile-schedules {--fix : Apply fixes automatically}';
    protected $description = 'Find and reconcile inconsistent shipping_schedules <-> voyages (final state & finalized_at).';

    public function handle()
    {
        $this->info('Scanning shipping_schedules for inconsistencies...');

        $query = ShippingSchedule::with('voyage');

        $problematic = $query->where(function ($q) {
            $q->where('state', ScheduleState::Final->value)
                ->whereNull('finalized_at');
        })->orWhereHas('voyage', function($q) {
            $q->where('is_final', false);
        })->get();

        $count = $problematic->count();

        if (! $count) {
            $this->info('No problematic schedules found.');
            return 0;
        }

        $this->info("Found {$count} problematic schedules. Listing samples:");
        foreach ($problematic->take(20) as $s) {
            $voyage = $s->voyage;

            // read raw DB value to avoid enum->string conversion issues
            $stateRaw = $s->getRawOriginal('state');
            $state = $stateRaw ?? 'NULL';

            $finalizedAt = $s->finalized_at ? $s->finalized_at->toDateTimeString() : 'NULL';
            $voyageFinalizedAt = $voyage?->finalized_at ? $voyage->finalized_at->toDateTimeString() : 'NULL';
            $voyageIsFinal = $voyage ? ($voyage->is_final ? 'true' : 'false') : 'no-voyage';

            $this->line(sprintf(
                "Schedule#%d (voyage_id=%d, voyage_no=%s) state=%s finalized_at=%s | voyage.is_final=%s voyage.finalized_at=%s",
                $s->id,
                $s->voyage_id,
                $s->voyage_no,
                $state,
                $finalizedAt,
                $voyageIsFinal,
                $voyageFinalizedAt
            ));
        }

        if ($this->option('fix')) {
            $this->info('Applying fixes...');

            DB::transaction(function () use ($problematic) {
                foreach ($problematic as $s) {
                    $v = $s->voyage;

                    if ($s->getRawOriginal('state') === ScheduleState::Final->value && !$s->finalized_at) {
                        $s->finalized_at = $v?->finalized_at ?? now();
                        $s->finalized_by = $s->finalized_by ?? $v?->finalized_by;
                        $s->finalized_by_name = $s->finalized_by_name ?? $v?->finalized_by_name;
                        $s->save();
                    }

                    if ($s->getRawOriginal('state') === ScheduleState::Final->value && $v && !$v->is_final) {
                        $v->is_final = true;
                        $v->finalized_at = $v->finalized_at ?? $s->finalized_at ?? now();
                        $v->finalized_by = $v->finalized_by ?? $s->finalized_by;
                        $v->finalized_by_name = $v->finalized_by_name ?? $s->finalized_by_name;
                        $v->save();
                    }

                    if ($v && ($v->is_final || $v->finalized_at) && $s->getRawOriginal('state') !== ScheduleState::Final->value) {
                        $s->state = ScheduleState::Final->value;
                        $s->finalized_at = $s->finalized_at ?? $v->finalized_at ?? now();
                        $s->finalized_by = $s->finalized_by ?? $v->finalized_by;
                        $s->finalized_by_name = $s->finalized_by_name ?? $v->finalized_by_name;
                        $s->save();
                    }
                }
            });

            $this->info('Fixes applied. Re-run command to verify.');
        } else {
            $this->line('Run this command with --fix to apply automated fixes:');
            $this->line('php artisan voyage:reconcile-schedules --fix');
        }

        return 0;
    }
}

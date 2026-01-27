<?php

namespace App\Services;

use App\Models\SlaRule;
use App\Models\SlaResult;
use App\Models\Voyage;
use Illuminate\Support\Carbon;

class SlaEvaluator
{
    public static function evaluateVoyage(Voyage $voyage): void
    {
        if (! $voyage->atd_at || ! $voyage->ata_at) {
            return;
        }

        $rule = SlaRule::query()
            ->where('is_active', true)
            ->where('mode', 'sea')
            ->where('activity', 'sailing')
            ->where('pol_id', $voyage->pol_id)
            ->where('pod_id', $voyage->pod_id)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now());
            })
            ->first();

        if (! $rule) {
            return;
        }

        $start = Carbon::parse($voyage->atd_at);
        $end   = Carbon::parse($voyage->ata_at);

        $actualDays = round($start->diffInSeconds($end) / 86400, 2);
        $lateDays   = max(0, round($actualDays - $rule->target_days, 2));
        $status     = $lateDays > 0 ? 'late' : 'on_time';

        SlaResult::updateOrCreate(
            [
                'voyage_id'   => $voyage->id,
                'sla_rule_id' => $rule->id,
                'activity'    => 'sailing',
            ],
            [
                'start_at'    => $start,
                'end_at'      => $end,
                'target_days' => $rule->target_days,
                'actual_days' => $actualDays,
                'late_days'   => $lateDays,
                'status'      => $status,
            ]
        );
    }
}

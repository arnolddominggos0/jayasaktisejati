<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Models\Voyage;
use App\Models\SlaResult;
use App\Models\SlaRule;

class SlaEvaluator
{
    public static function evaluateVoyage(Voyage $voyage): void
    {
        if (! $voyage->atd_at || ! $voyage->ata_at) {
            return;
        }

        $rule = SlaRule::query()
            ->where('mode', 'sea')
            ->where('activity', 'sailing')
            ->where('pol_id', $voyage->pol_id)
            ->where('pod_id', $voyage->pod_id)
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            return;
        }

        $actualDays = round(
            $voyage->atd_at->diffInSeconds($voyage->ata_at) / 86400,
            2
        );

        $lateDays = max(0, $actualDays - $rule->target_days);

        $status = match (true) {
            $lateDays > 0 => SlaStatus::LATE,
            $actualDays >= ($rule->target_days - 1) => SlaStatus::RISK,
            default => SlaStatus::ONTIME,
        };

        SlaResult::updateOrCreate(
            [
                'voyage_id' => $voyage->id,
                'activity'  => 'sailing',
            ],
            [
                'sla_rule_id' => $rule->id,
                'start_at'    => $voyage->atd_at,
                'end_at'      => $voyage->ata_at,
                'target_days' => $rule->target_days,
                'actual_days' => $actualDays,
                'late_days'   => $lateDays,
                'status'      => $status,
            ]
        );
    }
}

<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Models\SlaResult;
use App\Models\SlaRule;
use App\Models\Voyage;

class SlaEvaluator
{
    public static function evaluateVoyage(Voyage $voyage): void
    {

        if (! $voyage->atd_at || ! $voyage->ata_at) {
            return;
        }

        if ($voyage->ata_at->lte($voyage->atd_at)) {
            return;
        }

        $rule = SlaRule::query()
            ->where('mode', 'sea')
            ->where('activity', 'sailing')
            ->where('pol_id', $voyage->pol_id)
            ->where('pod_id', $voyage->pod_id)
            ->where('is_active', true)
            ->orderByDesc('target_days')
            ->first();

        if (! $rule) {
            return;
        }

        $actualDays = round(
            $voyage->atd_at->diffInSeconds($voyage->ata_at) / 86400,
            2
        );

        $lateDays = max(0, $actualDays - $rule->target_days);

        if ($actualDays <= $rule->target_days) {

            $status = SlaStatus::ONTIME;

        } elseif ($lateDays <= 1) {

            $status = SlaStatus::RISK;

        } else {

            $status = SlaStatus::LATE;

        }

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
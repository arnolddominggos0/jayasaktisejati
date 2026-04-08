<?php

namespace App\Services;

use App\Models\VesselPlan;

class VesselPlanAnalyzer
{
    public function analyze(VesselPlan $plan): array
    {
        $items = $plan->items->sortBy('planned_etd')->values();

        if ($items->isEmpty()) {
            return ['ok' => false];
        }

        $dwelling = config('kpi.manado.thresholds.dwelling_days', 6);
        $dooring  = config('kpi.manado.thresholds.dooring_days', 3);
        $limit    = config('kpi.manado.thresholds.total_days.normal', 19);

        $avgSailing = $items->map(fn($i) => $i->planned_sailing_days)
            ->filter()
            ->avg() ?? 0;

        $total = $dwelling + $avgSailing + $dooring;

        $gapData = $this->calculateEtdGaps($items);
        $maxGap = $gapData['max_gap'];

        $ok = $total <= $limit && $maxGap <= 6;

        return [
            'dwelling'    => $dwelling,
            'sailing_avg' => round($avgSailing, 2),
            'dooring'     => $dooring,
            'total'       => round($total, 2),
            'limit'       => $limit,
            'max_gap'     => $maxGap,
            'gaps'        => $gapData['gaps'],
            'ok'          => $ok,
        ];
    }

    protected function calculateEtdGaps($items): array
    {
        $gaps = [];
        $maxGap = 0;

        foreach ($items as $i => $item) {
            if ($i === 0) {
                $gaps[$item->id] = null;
                continue;
            }

            $prev = $items[$i - 1];

            $gap = $prev->planned_etd
                ->startOfDay()
                ->diffInDays($item->planned_etd->startOfDay());

            $gaps[$item->id] = $gap;
            $maxGap = max($maxGap, $gap);
        }

        return [
            'gaps' => $gaps,
            'max_gap' => $maxGap,
        ];
    }
}
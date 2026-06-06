<?php

namespace App\Services;

use App\Models\VesselPlan;

class VesselPlanAnalyzer
{
    public function analyze(VesselPlan $plan): array
    {
        $items = $plan->items->sortBy('planned_etd')->values();

        if ($items->isEmpty()) {
            return [
                'sailing_avg'    => 0,
                'max_gap'        => 0,
                'gaps'           => [],
                'schedule_count' => 0,
                'gap_ok'         => true,
                'gap_limit'      => config('jss_kpi.manado.thresholds.etd_gap_max', 6),
                'risk_level'     => 'valid',
                'violations'     => [],
                'ok'             => true,
            ];
        }

        $avgSailing = $items->map(fn($i) => $i->planned_sailing_days)
            ->filter()
            ->avg() ?? 0;

        $gapData = $this->calculateEtdGaps($items);
        $maxGap = $gapData['max_gap'];

        $gapLimit = config('jss_kpi.manado.thresholds.etd_gap_max', 6);
        $gapOk = $maxGap <= $gapLimit;

        $riskLevel = match (true) {
            $maxGap <= $gapLimit => 'valid',
            $maxGap <= 10        => 'warning',
            default              => 'critical',
        };

        $violations = [];
        if ($riskLevel === 'warning') {
            $violations[] = 'Max ETD Gap ' . $maxGap . ' hari melebihi target SOP ' . $gapLimit . ' hari. Potensi peningkatan dwelling time.';
        } elseif ($riskLevel === 'critical') {
            $violations[] = 'ETD Gap sangat tinggi (' . $maxGap . ' hari). Berpotensi mempengaruhi siklus kapal berikutnya.';
        }

        return [
            'sailing_avg'    => round($avgSailing, 2),
            'max_gap'        => $maxGap,
            'gaps'           => $gapData['gaps'],
            'schedule_count' => $items->count(),
            'gap_ok'         => $gapOk,
            'gap_limit'      => $gapLimit,
            'risk_level'     => $riskLevel,
            'violations'     => $violations,
            'ok'             => $gapOk,
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
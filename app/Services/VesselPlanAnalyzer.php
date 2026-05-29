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
                'sailing_avg' => 0,
                'max_gap' => 0,
                'gaps' => [],
                'schedule_count' => 0,
                'gap_ok' => false,
                'gap_limit' => 6,
                'violations' => ['Belum ada jadwal kapal.'],
                'ok' => false,
            ];
        }

        $avgSailing = $items->map(fn($i) => $i->planned_sailing_days)
            ->filter()
            ->avg() ?? 0;

        $gapData = $this->calculateEtdGaps($items);
        $maxGap = $gapData['max_gap'];

        $gapLimit = config('jss_kpi.manado.thresholds.etd_gap_max', 6);
        $gapOk = $maxGap <= $gapLimit;

        $violations = [];
        if (! $gapOk) {
            $violations[] = 'Max ETD Gap ' . $maxGap . ' hari melebihi batas ' . $gapLimit . ' hari.';
        }

        return [
            'sailing_avg' => round($avgSailing, 2),
            'max_gap' => $maxGap,
            'gaps' => $gapData['gaps'],
            'schedule_count' => $items->count(),
            'gap_ok' => $gapOk,
            'gap_limit' => $gapLimit,
            'violations' => $violations,
            'ok' => $gapOk,
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
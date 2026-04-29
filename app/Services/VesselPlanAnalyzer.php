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
                'dwelling' => 0,
                'sailing_avg' => 0,
                'dooring' => 0,
                'total' => 0,
                'limit' => 0,
                'max_gap' => 0,
                'gaps' => [],
                'schedule_count' => 0,
                'kpi_ok' => false,
                'gap_ok' => false,
                'violations' => ['Belum ada jadwal kapal.'],
                'ok' => false,
            ];
        }

        $dwelling = config('jss_kpi.manado.thresholds.dwelling_days', 6);
        $dooring  = config('jss_kpi.manado.thresholds.dooring_days', 3);
        $limit    = config('jss_kpi.manado.thresholds.total_days.normal', 19);

        $avgSailing = $items->map(fn($i) => $i->planned_sailing_days)
            ->filter()
            ->avg() ?? 0;

        $total = $dwelling + $avgSailing + $dooring;

        $gapData = $this->calculateEtdGaps($items);
        $maxGap = $gapData['max_gap'];

        $kpiOk = $total <= $limit;
        $gapLimit = 6;
        $gapOk = $maxGap <= $gapLimit;
        $ok = $kpiOk && $gapOk;

        $violations = [];
        if (! $kpiOk) {
            $violations[] = 'Total KPI ' . round($total, 2) . ' hari melebihi batas ' . $limit . ' hari.';
        }
        if (! $gapOk) {
            $violations[] = 'Max ETD Gap ' . $maxGap . ' hari melebihi batas ' . $gapLimit . ' hari.';
        }

        return [
            'dwelling'    => $dwelling,
            'sailing_avg' => round($avgSailing, 2),
            'dooring'     => $dooring,
            'total'       => round($total, 2),
            'limit'       => $limit,
            'max_gap'     => $maxGap,
            'gaps'        => $gapData['gaps'],
            'schedule_count' => $items->count(),
            'kpi_ok' => $kpiOk,
            'gap_ok' => $gapOk,
            'gap_limit' => $gapLimit,
            'violations' => $violations,
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

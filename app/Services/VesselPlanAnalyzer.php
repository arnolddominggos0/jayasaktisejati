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
                'conflicts'      => [],
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
            $violations[] = 'Max ETD Gap ' . $maxGap . ' hari melebihi target SOP ' . $gapLimit . ' hari. Periksa kontinuitas jadwal antar kapal.';
        } elseif ($riskLevel === 'critical') {
            $violations[] = 'ETD Gap sangat tinggi (' . $maxGap . ' hari). Berpotensi mempengaruhi siklus kapal berikutnya.';
        }

        $conflicts = $this->detectConflicts($items);

        return [
            'sailing_avg'    => round($avgSailing, 2),
            'max_gap'        => $maxGap,
            'gaps'           => $gapData['gaps'],
            'schedule_count' => $items->count(),
            'gap_ok'         => $gapOk,
            'gap_limit'      => $gapLimit,
            'risk_level'     => $riskLevel,
            'violations'     => $violations,
            'conflicts'      => $conflicts,
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

    protected function detectConflicts($items): array
    {
        $conflicts = [];
        $sorted = $items->sortBy('planned_etd')->values();

        for ($i = 0; $i < $sorted->count(); $i++) {
            $current = $sorted[$i];
            $currentName = $current->vessel?->name ?? 'Unknown';

            if ($i > 0) {
                $prev = $sorted[$i - 1];
                $prevName = $prev->vessel?->name ?? 'Unknown';

                if ($current->planned_etd && $prev->planned_etd
                    && $current->planned_etd->isSameDay($prev->planned_etd)) {
                    $conflicts[] = sprintf(
                        '%s dan %s memiliki ETD sama (%s)',
                        $prevName,
                        $currentName,
                        $current->planned_etd->translatedFormat('d M Y')
                    );
                }

                if ($prev->planned_eta && $current->planned_etd
                    && $prev->planned_eta > $current->planned_etd) {
                    $conflicts[] = sprintf(
                        'ETA %s (%s) melebihi ETD %s (%s) — potensi overlap rute',
                        $prevName,
                        $prev->planned_eta->translatedFormat('d M Y'),
                        $currentName,
                        $current->planned_etd->translatedFormat('d M Y')
                    );
                }
            }

            if ($current->planned_eta && $current->planned_etd
                && $current->planned_eta <= $current->planned_etd) {
                $conflicts[] = sprintf(
                    '%s: ETA (%s) harus setelah ETD (%s)',
                    $currentName,
                    $current->planned_eta->translatedFormat('d M Y'),
                    $current->planned_etd->translatedFormat('d M Y')
                );
            }
        }

        return $conflicts;
    }
}
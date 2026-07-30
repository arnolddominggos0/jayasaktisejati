<?php

namespace App\Services;

use App\Models\VesselPlan;

class VesselPlanAnalyzer
{
    public function analyze(VesselPlan $plan): array
    {
        $items = $plan->items->sortBy('planned_etd')->values();

        if ($items->isEmpty()) {
            return $this->emptyResult();
        }

        $gapLimit = config('jss_kpi.manado.thresholds.etd_gap_max', 6);

        $avgSailing = $items->map(fn ($i) => $i->planned_sailing_days)
            ->filter()
            ->avg() ?? 0;

        $gapData = $this->calculateEtdGaps($items);
        $maxGap = $gapData['max_gap'];
        $gapOk = $maxGap <= $gapLimit;

        $riskLevel = match (true) {
            $maxGap <= $gapLimit => 'valid',
            $maxGap <= 10 => 'warning',
            default => 'critical',
        };

        $gapWarnings = $this->buildGapWarnings($items, $gapData['gaps'], $gapLimit);

        $violations = $this->buildViolationSummary($maxGap, $gapLimit, $riskLevel);

        $chronologyIssues = $this->detectChronologyIssues($items);

        $conflicts = $this->detectConflicts($items);

        $missingSailing = $this->detectMissingSailing($items);
        $missingVoyage = $this->detectMissingVoyage($items);

        $readiness = $this->buildReadiness(
            $gapWarnings,
            $chronologyIssues,
            $missingSailing,
            $missingVoyage
        );

        return [
            'sailing_avg' => round($avgSailing, 2),
            'max_gap' => $maxGap,
            'gaps' => $gapData['gaps'],
            'schedule_count' => $items->count(),
            'gap_ok' => $gapOk,
            'gap_limit' => $gapLimit,
            'risk_level' => $riskLevel,
            'violations' => $violations,
            'conflicts' => $conflicts,
            'ok' => $gapOk,
            'gap_warnings' => $gapWarnings,
            'chronology_issues' => $chronologyIssues,
            'missing_sailing' => $missingSailing,
            'missing_voyage' => $missingVoyage,
            'readiness' => $readiness,
        ];
    }

    protected function emptyResult(): array
    {
        return [
            'sailing_avg' => 0,
            'max_gap' => 0,
            'gaps' => [],
            'schedule_count' => 0,
            'gap_ok' => true,
            'gap_limit' => config('jss_kpi.manado.thresholds.etd_gap_max', 6),
            'risk_level' => 'valid',
            'violations' => [],
            'conflicts' => [],
            'ok' => true,
            'gap_warnings' => [],
            'chronology_issues' => [],
            'missing_sailing' => [],
            'missing_voyage' => [],
            'readiness' => ['ready' => false, 'reasons' => []],
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

    protected function buildGapWarnings($items, array $gaps, int $gapLimit): array
    {
        $warnings = [];

        foreach ($items as $i => $item) {
            if ($i === 0) {
                continue;
            }

            $gap = $gaps[$item->id] ?? null;
            if ($gap === null || $gap <= $gapLimit) {
                continue;
            }

            $warnings[] = [
                'vessel' => $item->vessel?->name ?? 'Unknown',
                'gap' => $gap,
                'limit' => $gapLimit,
                'severity' => $gap > 10 ? 'critical' : 'warning',
            ];
        }

        return $warnings;
    }

    protected function buildViolationSummary(int $maxGap, int $gapLimit, string $riskLevel): array
    {
        if ($riskLevel === 'warning') {
            return ['Max ETD Gap '.$maxGap.' hari melebihi target SOP '.$gapLimit.' hari. Periksa kontinuitas jadwal antar kapal.'];
        }
        if ($riskLevel === 'critical') {
            return ['ETD Gap sangat tinggi ('.$maxGap.' hari). Berpotensi mempengaruhi siklus kapal berikutnya.'];
        }

        return [];
    }

    protected function detectConflicts($items): array
    {
        $issues = $this->detectChronologyIssues($items);

        return array_map(
            fn ($c) => sprintf('%s: ETA (%s) harus setelah ETD (%s)', $c['vessel'], $c['eta'], $c['etd']),
            $issues
        );
    }

    protected function detectChronologyIssues($items): array
    {
        $issues = [];

        foreach ($items as $item) {
            if ($item->planned_eta && $item->planned_etd
                && $item->planned_eta <= $item->planned_etd) {
                $issues[] = [
                    'vessel' => $item->vessel?->name ?? 'Unknown',
                    'etd' => $item->planned_etd->translatedFormat('d M Y'),
                    'eta' => $item->planned_eta->translatedFormat('d M Y'),
                ];
            }
        }

        return $issues;
    }

    protected function detectMissingSailing($items): array
    {
        $missing = [];

        foreach ($items as $item) {
            if (! $item->planned_etd || ! $item->planned_eta) {
                $missing[] = [
                    'vessel' => $item->vessel?->name ?? 'Unknown',
                    'field' => ! $item->planned_etd ? 'ETD' : 'ETA',
                ];
            }
        }

        return $missing;
    }

    protected function detectMissingVoyage($items): array
    {
        $missing = [];

        foreach ($items as $item) {
            if (! filled($item->voyage_no)) {
                $missing[] = [
                    'vessel' => $item->vessel?->name ?? 'Unknown',
                ];
            }
        }

        return $missing;
    }

    protected function buildReadiness(
        array $gapWarnings,
        array $chronologyIssues,
        array $missingSailing,
        array $missingVoyage
    ): array {
        $reasons = [];

        if (! empty($gapWarnings)) {
            $critical = count(array_filter($gapWarnings, fn ($w) => $w['severity'] === 'critical'));
            $warning = count($gapWarnings) - $critical;

            if ($critical > 0) {
                $reasons[] = [
                    'text' => $critical.' gap sangat tinggi (kritikal, > 10 hari)',
                    'count' => $critical,
                    'severity' => 'critical',
                ];
            }
            if ($warning > 0) {
                $reasons[] = [
                    'text' => $warning.' gap belum memenuhi SOP',
                    'count' => $warning,
                    'severity' => 'warning',
                ];
            }
        }

        if (! empty($chronologyIssues)) {
            $reasons[] = [
                'text' => count($chronologyIssues).' kronologi ETD/ETA tidak valid (ETA ≤ ETD)',
                'count' => count($chronologyIssues),
                'severity' => 'critical',
            ];
        }

        if (! empty($missingSailing)) {
            $reasons[] = [
                'text' => count($missingSailing).' sailing days belum diisi (ETD/ETA kosong)',
                'count' => count($missingSailing),
                'severity' => 'warning',
            ];
        }

        if (! empty($missingVoyage)) {
            $reasons[] = [
                'text' => count($missingVoyage).' voyage belum dipilih',
                'count' => count($missingVoyage),
                'severity' => 'warning',
            ];
        }

        return [
            'ready' => empty($reasons),
            'reasons' => $reasons,
        ];
    }
}

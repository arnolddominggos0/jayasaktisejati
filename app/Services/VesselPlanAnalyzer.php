<?php

namespace App\Services;

use App\Models\VesselPlan;

/**
 * Planning Recognition engine — Vessel Plan SOP validation.
 *
 * Canon v1.1 (Axiom 5 — Recognition): produces planning-domain Recognition
 * only. Sprint 12.3 refines this into a Decision Support engine:
 *   - every output must yield a clear Planner decision;
 *   - false-positive inter-vessel overlap rules were removed;
 *   - per-vessel actionable warnings + readiness aggregation were added.
 *
 * Architecture preservation (Sprint 12.3 constraint):
 *   - `detectConflicts()` method retained (now emits Invalid chronology only);
 *   - `conflicts` key retained as string list (legacy contract);
 *   - `violations` key retained as plain-text list (consumed by sopStatus()
 *     and the header KPI strip widget);
 *   - no service / model / accessor / widget / column removed.
 */
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
            $maxGap <= 10        => 'warning',
            default              => 'critical',
        };

        // Actionable per-vessel ETD gap warnings (SOP violation, with vessel name).
        $gapWarnings = $this->buildGapWarnings($items, $gapData['gaps'], $gapLimit);

        // SOP violation summary text — preserved for legacy consumers
        // (VesselPlan::sopStatus() + header KPI strip widget).
        $violations = $this->buildViolationSummary($maxGap, $gapLimit, $riskLevel);

        // Invalid chronology (ETA <= ETD) — retained planning-domain rule.
        $chronologyIssues = $this->detectChronologyIssues($items);

        // conflicts key preserved (architecture) — now Invalid chronology only.
        $conflicts = $this->detectConflicts($items);

        // New planning Recognition — actionable per vessel.
        $missingSailing = $this->detectMissingSailing($items);
        $missingVoyage  = $this->detectMissingVoyage($items);

        // Planning Readiness aggregation — Sprint 12.3 main focus.
        $readiness = $this->buildReadiness(
            $gapWarnings,
            $chronologyIssues,
            $missingSailing,
            $missingVoyage
        );

        return [
            'sailing_avg'       => round($avgSailing, 2),
            'max_gap'           => $maxGap,
            'gaps'              => $gapData['gaps'],
            'schedule_count'    => $items->count(),
            'gap_ok'            => $gapOk,
            'gap_limit'         => $gapLimit,
            'risk_level'        => $riskLevel,
            'violations'        => $violations,
            'conflicts'         => $conflicts,
            'ok'                => $gapOk,
            // Sprint 12.3 — Decision Support outputs
            'gap_warnings'      => $gapWarnings,
            'chronology_issues' => $chronologyIssues,
            'missing_sailing'   => $missingSailing,
            'missing_voyage'    => $missingVoyage,
            'readiness'         => $readiness,
        ];
    }

    protected function emptyResult(): array
    {
        return [
            'sailing_avg'       => 0,
            'max_gap'           => 0,
            'gaps'              => [],
            'schedule_count'    => 0,
            'gap_ok'            => true,
            'gap_limit'         => config('jss_kpi.manado.thresholds.etd_gap_max', 6),
            'risk_level'        => 'valid',
            'violations'        => [],
            'conflicts'         => [],
            'ok'                => true,
            'gap_warnings'      => [],
            'chronology_issues' => [],
            'missing_sailing'   => [],
            'missing_voyage'    => [],
            'readiness'         => ['ready' => false, 'reasons' => []],
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

    /**
     * Actionable per-vessel ETD gap warnings (SOP violation Recognition).
     * Each warning carries the vessel name so Planner knows exactly which
     * vessel to inspect — Sprint 12.3 Decision Support.
     */
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
                'vessel'   => $item->vessel?->name ?? 'Unknown',
                'gap'      => $gap,
                'limit'    => $gapLimit,
                'severity' => $gap > 10 ? 'critical' : 'warning',
            ];
        }

        return $warnings;
    }

    /**
     * SOP violation summary text — preserved for legacy consumers
     * (VesselPlan::sopStatus() + header KPI strip widget). Kept as plain
     * strings so existing contracts are not broken (Sprint 12.3 architecture
     * preservation).
     */
    protected function buildViolationSummary(int $maxGap, int $gapLimit, string $riskLevel): array
    {
        if ($riskLevel === 'warning') {
            return ['Max ETD Gap ' . $maxGap . ' hari melebihi target SOP ' . $gapLimit . ' hari. Periksa kontinuitas jadwal antar kapal.'];
        }
        if ($riskLevel === 'critical') {
            return ['ETD Gap sangat tinggi (' . $maxGap . ' hari). Berpotensi mempengaruhi siklus kapal berikutnya.'];
        }

        return [];
    }

    /**
     * Detect planning-domain conflicts.
     *
     * Sprint 12.3 — removed false-positive inter-vessel overlap rules:
     *   - same-ETD overlap ("ETA overlap antar vessel")
     *   - "ETA previous > ETD next" (overlap route warning)
     * Parallel vessel operation is normal, not a Vessel Plan business rule.
     * Only the legitimate Invalid chronology rule (ETA <= ETD) is retained.
     *
     * Returns string messages (legacy `conflicts` contract) so existing
     * consumers keep working. Structured form available via
     * `detectChronologyIssues()`.
     */
    protected function detectConflicts($items): array
    {
        $issues = $this->detectChronologyIssues($items);

        return array_map(
            fn ($c) => sprintf('%s: ETA (%s) harus setelah ETD (%s)', $c['vessel'], $c['eta'], $c['etd']),
            $issues
        );
    }

    /**
     * Invalid chronology Recognition (ETA <= ETD) — legitimate planning-domain
     * rule, single-vessel check, actionable per vessel.
     */
    protected function detectChronologyIssues($items): array
    {
        $issues = [];

        foreach ($items as $item) {
            if ($item->planned_eta && $item->planned_etd
                && $item->planned_eta <= $item->planned_etd) {
                $issues[] = [
                    'vessel' => $item->vessel?->name ?? 'Unknown',
                    'etd'    => $item->planned_etd->translatedFormat('d M Y'),
                    'eta'    => $item->planned_eta->translatedFormat('d M Y'),
                ];
            }
        }

        return $issues;
    }

    /**
     * Missing sailing days Recognition — vessel with ETD/ETA not filled so
     * planned_sailing_days cannot be derived. Actionable per vessel.
     */
    protected function detectMissingSailing($items): array
    {
        $missing = [];

        foreach ($items as $item) {
            if (! $item->planned_etd || ! $item->planned_eta) {
                $missing[] = [
                    'vessel' => $item->vessel?->name ?? 'Unknown',
                    'field'  => ! $item->planned_etd ? 'ETD' : 'ETA',
                ];
            }
        }

        return $missing;
    }

    /**
     * Missing voyage Recognition — vessel without voyage_no selected.
     * Required for finalization. Actionable per vessel.
     */
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

    /**
     * Planning Readiness aggregation — Decision Support summary.
     * Planner sees "Siap dikirim ke TAM" or "Belum siap" plus specific
     * reason counts (not a long technical list). Sprint 12.3 main focus.
     */
    protected function buildReadiness(
        array $gapWarnings,
        array $chronologyIssues,
        array $missingSailing,
        array $missingVoyage
    ): array {
        $reasons = [];

        if (! empty($gapWarnings)) {
            $critical = count(array_filter($gapWarnings, fn ($w) => $w['severity'] === 'critical'));
            $warning  = count($gapWarnings) - $critical;

            if ($critical > 0) {
                $reasons[] = [
                    'text'     => $critical . ' gap sangat tinggi (kritikal, > 10 hari)',
                    'count'    => $critical,
                    'severity' => 'critical',
                ];
            }
            if ($warning > 0) {
                $reasons[] = [
                    'text'     => $warning . ' gap belum memenuhi SOP',
                    'count'    => $warning,
                    'severity' => 'warning',
                ];
            }
        }

        if (! empty($chronologyIssues)) {
            $reasons[] = [
                'text'     => count($chronologyIssues) . ' kronologi ETD/ETA tidak valid (ETA ≤ ETD)',
                'count'    => count($chronologyIssues),
                'severity' => 'critical',
            ];
        }

        if (! empty($missingSailing)) {
            $reasons[] = [
                'text'     => count($missingSailing) . ' sailing days belum diisi (ETD/ETA kosong)',
                'count'    => count($missingSailing),
                'severity' => 'warning',
            ];
        }

        if (! empty($missingVoyage)) {
            $reasons[] = [
                'text'     => count($missingVoyage) . ' voyage belum dipilih',
                'count'    => count($missingVoyage),
                'severity' => 'warning',
            ];
        }

        return [
            'ready'   => empty($reasons),
            'reasons' => $reasons,
        ];
    }
}

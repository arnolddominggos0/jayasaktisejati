<?php

namespace App\Services\Monitoring;

use App\Models\Unit;
use App\Models\UnitInspection;
use App\ViewModels\Monitoring\InspectionStageSummary;
use App\ViewModels\Monitoring\InspectionSummary;

final class InspectionSummaryBuilder
{
    // Gate decision severity order (higher index = worse)
    private const GATE_ORDER = [
        UnitInspection::GATE_ACCEPT,
        UnitInspection::GATE_ALLOW_WITH_REMARK,
        UnitInspection::GATE_RETURN_TO_PDC,
    ];

    public function build(Unit $unit): InspectionSummary
    {
        $inspections = $unit->relationLoaded('inspections') ? $unit->inspections : collect();

        // Index by stage
        $byStage = $inspections->keyBy('stage');

        $stageSummaries = [];
        $totalStages = count(UnitInspection::STAGES);
        $submittedCount = 0;
        $totalNg = 0;
        $worstGateDecision = null;

        foreach (UnitInspection::STAGES as $stage) {
            $insp = $byStage->get($stage);
            $stageLabel = UnitInspection::STAGE_LABELS[$stage] ?? ucfirst(str_replace('_', ' ', $stage));

            if ($insp === null) {
                $stageSummaries[] = new InspectionStageSummary(
                    stage: $stage,
                    stage_label: $stageLabel,
                    status: 'not_required',
                    gate_decision: null,
                    ng_count: 0,
                    is_submitted: false,
                    summary_1line: null,
                    checked_at: null,
                    inspector_name: null,
                );
                continue;
            }

            $items = $insp->relationLoaded('items') ? $insp->items : collect();
            $ngCount = $items->where('result', 'ng')->count();
            $totalNg += $ngCount;

            $isSubmitted = $insp->submitted_at !== null;
            if ($isSubmitted) {
                $submittedCount++;
            }

            if ($insp->gate_decision !== null) {
                $worstGateDecision = $this->worseDecision($worstGateDecision, $insp->gate_decision);
            }

            $status = $this->resolveStatus($insp->status, $isSubmitted);

            $summary1line = null;
            if ($ngCount > 0) {
                $gateLabel = UnitInspection::GATE_LABELS[$insp->gate_decision] ?? null;
                $summary1line = $ngCount . ' NG' . ($gateLabel ? ' · ' . $gateLabel : '');
            }

            $inspectorName = null;
            if ($insp->relationLoaded('checkedBy')) {
                $inspectorName = optional($insp->checkedBy)->name;
            }

            $stageSummaries[] = new InspectionStageSummary(
                stage: $stage,
                stage_label: $stageLabel,
                status: $status,
                gate_decision: $insp->gate_decision,
                ng_count: $ngCount,
                is_submitted: $isSubmitted,
                summary_1line: $summary1line,
                checked_at: $insp->checked_at,
                inspector_name: $inspectorName,
            );
        }

        return new InspectionSummary(
            stages: $stageSummaries,
            total_stages: $totalStages,
            submitted_stages: $submittedCount,
            pending_stages: $totalStages - $submittedCount,
            ng_item_count: $totalNg,
            overall_gate_decision: $worstGateDecision,
        );
    }

    private function resolveStatus(string $rawStatus, bool $isSubmitted): string
    {
        if (!$isSubmitted) {
            return 'pending';
        }

        return match ($rawStatus) {
            UnitInspection::STATUS_PASSED => 'passed',
            UnitInspection::STATUS_FAILED => 'failed',
            default => 'pending',
        };
    }

    private function worseDecision(?string $current, string $candidate): string
    {
        if ($current === null) {
            return $candidate;
        }

        $currIdx = array_search($current, self::GATE_ORDER, true) ?? 0;
        $candIdx = array_search($candidate, self::GATE_ORDER, true) ?? 0;

        return $candIdx > $currIdx ? $candidate : $current;
    }
}
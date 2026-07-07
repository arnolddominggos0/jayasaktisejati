<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Models\VesselPlan;
use Carbon\Carbon;
use Filament\Widgets\Widget;

/**
 * @deprecated
 *
 * Removed from Vessel Plan Workspace in Sprint 12.8.
 *
 * This widget belongs to the Operational/Voyage Evaluation domain
 * because it analyzes Draft -> Final -> Actual execution,
 * Dwelling, Sailing, and Dooring after planning has finished.
 *
 * Candidate for reuse in Voyage Evaluation Workspace.
 */
class VesselPlanScheduleAnalysis extends Widget
{
    protected static string $view =
        'filament.resources.vessel-plan-resource.widgets.vessel-plan-schedule-analysis';

    protected int|string|array $columnSpan = 'full';

    public ?VesselPlan $record = null;

    protected function getViewData(): array
    {
        if (! $this->record) {
            return ['hasData' => false, 'rows' => [], 'narrative' => null];
        }

        $items = $this->record->items()->with(['vessel', 'voyage'])->orderBy('planned_etd')->get();

        if ($items->isEmpty()) {
            return ['hasData' => false, 'rows' => [], 'narrative' => null];
        }

        $rows = $items->map(function ($item) {
            $v = $item->voyage;

            $dETD = $item->planned_etd;     // Draft ETD
            $dETA = $item->planned_eta;     // Draft ETA (for Sailing Plan)
            $fETD = $v?->etd;              // Final ETD
            $aATD = $v?->atd_at;           // Actual ATD

            // Dwelling Impact = Final ETD − Draft ETD
            $dwellingImpact = ($dETD && $fETD)
                ? $this->signedDays($dETD, $fETD)
                : null;

            // Sailing Impact = Actual ATD − Final ETD (departure accuracy)
            $sailingImpact = ($fETD && $aATD)
                ? $this->signedDays($fETD, $aATD)
                : null;

            // Dooring Impact = Dwelling + Sailing (null if sailing not yet available)
            $dooringImpact = ($dwellingImpact !== null && $sailingImpact !== null)
                ? round($dwellingImpact + $sailingImpact, 1)
                : null;

            // Sailing Plan = Draft ETA − Draft ETD (days) — for narrative/forecast
            $sailingPlan = ($dETD && $dETA)
                ? round($dETD->diffInSeconds($dETA) / 86400, 1)
                : null;

            // Effective impact for status: dooring if known, else dwelling
            $effectiveImpact = $dooringImpact ?? $dwellingImpact;

            $status = match (true) {
                $fETD === null                         => 'no_final',
                abs($effectiveImpact ?? 0) >= 4        => 'tinggi',
                abs($effectiveImpact ?? 0) >= 2        => 'sedang',
                default                                => 'rendah',
            };

            return [
                'voyage_no'      => $item->voyage_no ?? '—',
                'vessel_name'    => $item->vessel?->name ?? '—',
                'draftETD'       => $dETD,
                'finalETD'       => $fETD,
                'actualATD'      => $aATD,
                'dwellingImpact' => $dwellingImpact,
                'sailingImpact'  => $sailingImpact,
                'dooringImpact'  => $dooringImpact,
                'sailingPlan'    => $sailingPlan,
                'hasActual'      => $aATD !== null,
                'status'         => $status,
            ];
        })->all();

        // Narrative: most-impacted revised voyage
        $revisedRows = array_filter($rows, fn($r) => abs($r['dwellingImpact'] ?? 0) > 0);
        $revisedCount = count($revisedRows);

        usort($revisedRows, fn($a, $b) =>
            abs($b['dwellingImpact'] ?? 0) <=> abs($a['dwellingImpact'] ?? 0)
        );

        $topRow = ! empty($revisedRows) ? array_values($revisedRows)[0] : null;

        // Forecast Dooring total for narrative = Dwelling Impact + Sailing Plan
        $forecastDooringTotal = ($topRow && $topRow['dwellingImpact'] !== null && $topRow['sailingPlan'] !== null)
            ? round($topRow['dwellingImpact'] + $topRow['sailingPlan'], 1)
            : null;

        return [
            'hasData'             => true,
            'rows'                => $rows,
            'revisedCount'        => $revisedCount,
            'narrative' => [
                'topRow'               => $topRow,
                'revisedCount'         => $revisedCount,
                'forecastDooringTotal' => $forecastDooringTotal,
            ],
        ];
    }

    private function signedDays(Carbon $from, Carbon $to): float
    {
        return round($from->diffInSeconds($to, false) / 86400, 1);
    }
}

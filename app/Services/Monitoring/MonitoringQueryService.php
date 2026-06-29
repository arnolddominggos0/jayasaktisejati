<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Queries\Monitoring\UnitMonitoringQueryBuilder;
use App\ViewModels\Monitoring\AgeData;
use App\ViewModels\Monitoring\CurrentStageData;
use App\ViewModels\Monitoring\MonitoringRowData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

final class MonitoringQueryService
{
    public function __construct(
        private readonly UnitMonitoringQueryBuilder $queryBuilder,
        private readonly StageResolver $stageResolver,
        private readonly AgeCalculator $ageCalculator,
        private readonly ProgressCalculator $progressCalculator,
        private readonly ExceptionEvaluator $exceptionEvaluator,
    ) {}

    public function paginate(MonitoringFilter $filter): LengthAwarePaginator
    {
        $pageSize = config('monitoring.page_size', 50);
        $query = $this->queryBuilder->build($filter);

        // Extend eager loading for ViewModel hydration. This is presentation
        // hydration, not a filter/sort rule — the QueryBuilder stays intact.
        $query->with([
            'tracks' => fn($q) => $q->orderBy('tracked_at', 'desc')->select([
                'id', 'shipment_id', 'status', 'tracked_at', 'note',
            ]),
            'units' => fn($q) => $q->select([
                'id', 'shipment_id', 'reg_no', 'model_no', 'chassis_no',
                'color', 'container_display',
            ]),
            'units.inspections' => fn($q) => $q->select([
                'id', 'unit_id', 'stage', 'status', 'gate_decision', 'submitted_at',
            ]),
            'units.inspections.items' => fn($q) => $q->select([
                'id', 'unit_inspection_id', 'result',
            ]),
        ]);

        $paginator = $query->paginate($pageSize, ['*'], 'page', $filter->page);

        $rows = $paginator->getCollection()->map(fn($shipment) => $this->transform($shipment, $filter));

        return new LengthAwarePaginator(
            items: $rows,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            options: $paginator->getOptions(),
        );
    }

    private function transform($shipment, MonitoringFilter $filter): MonitoringRowData
    {
        $stage = $this->stageResolver->resolve($shipment);
        $age = $this->ageCalculator->calculate(
            $shipment->latestTrackedAt ?? $shipment->latestTrack?->tracked_at,
            $shipment->requested_at,
            $shipment->mode?->value ?? 'sea',
        );
        $progress = $this->progressCalculator->calculate(
            $stage->current_stage,
            $stage->is_held,
            $stage->is_cancelled,
        );
        $exceptions = $this->exceptionEvaluator->evaluate($shipment);

        $firstUnit = $shipment->relationLoaded('units')
            ? $shipment->units->first()
            : null;

        $unitCount = $shipment->relationLoaded('units')
            ? $shipment->units->count()
            : 0;

        $isFinished = $shipment->isHistorical()
            || in_array($shipment->status, [ShipmentStatus::Delivered, ShipmentStatus::Cancelled], true);

        // is_search_match: prefer the computed subquery column if present
        // (added by UnitMonitoringQueryBuilder::applySearchMatch), otherwise
        // resolve via the filter search term against code/doc_number.
        $isSearchMatch = (bool) ($shipment->is_search_match ?? false);
        if (!$isSearchMatch && filled($filter->search)) {
            $term = strtolower((string) $filter->search);
            $haystack = strtolower(($shipment->code ?? '') . ' ' . ($shipment->doc_number ?? ' '));
            $isSearchMatch = str_contains($haystack, $term);
        }

        return new MonitoringRowData(
            shipment_id:      $shipment->id,
            shipment_code:    (string) ($shipment->code ?? ''),
            doc_number:       (string) ($shipment->doc_number ?? ''),
            unit_id:          $firstUnit?->id,
            unit_reg_no:      $firstUnit?->reg_no,
            unit_model_no:    $firstUnit?->model_no,
            unit_chassis_no:  $firstUnit?->chassis_no,
            unit_color:       $firstUnit?->color,
            container_display: $firstUnit?->container_display,
            customer_name:    (string) ($shipment->customer?->name ?? '—'),
            branch_name:      $shipment->branch?->name,
            route_label:      (string) ($shipment->route_label ?? '—'),
            mode:             $shipment->mode ?? ShipmentMode::Sea,
            voyage_no:        $shipment->voyage ? (string) $shipment->voyage : null,
            vessel_name:      $shipment->vessel_name ? (string) $shipment->vessel_name : null,
            stage:            $stage,
            age:              $age,
            progress_pct:     $progress,
            exceptions:       $exceptions,
            eta_label:        $shipment->eta ? $this->formatEta($shipment->eta) : null,
            lead_time_summary: $shipment->kpiManadoSummaryText(),
            is_search_match:  $isSearchMatch,
            is_finished:      $isFinished,
            unit_count:       $unitCount,
        );
    }

    private function formatEta(Carbon $eta): string
    {
        $now = now();
        if ($eta->year === $now->year) {
            return $eta->translatedFormat('d M');
        }

        return $eta->translatedFormat('d M Y');
    }
}
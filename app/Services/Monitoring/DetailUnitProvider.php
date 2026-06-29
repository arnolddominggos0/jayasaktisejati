<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Models\Shipment;
use App\Models\Unit;
use App\Queries\Monitoring\ShipmentDetailQueryBuilder;
use App\ViewModels\Monitoring\AdministrativeInfo;
use App\ViewModels\Monitoring\UnitDetailData;

final class DetailUnitProvider
{
    public function __construct(
        private readonly ShipmentDetailQueryBuilder $queryBuilder,
        private readonly StageResolver $stageResolver,
        private readonly ProgressCalculator $progressCalculator,
        private readonly TimelineBuilder $timelineBuilder,
        private readonly InspectionSummaryBuilder $inspectionSummaryBuilder,
        private readonly LeadTimeBuilder $leadTimeBuilder,
        private readonly AgeCalculator $ageCalculator,
        private readonly ExceptionEvaluator $exceptionEvaluator,
        private readonly SiblingUnitBuilder $siblingUnitBuilder,
        private readonly DeepLinkBuilder $deepLinkBuilder,
    ) {}

    public function provide(int $unitId, ?int $branchId = null): UnitDetailData
    {
        $shipment = $this->queryBuilder->buildForUnit($unitId, $branchId);

        if ($shipment === null) {
            return UnitDetailData::empty();
        }

        $unit = $shipment->units->firstWhere('id', $unitId);

        if ($unit === null) {
            return UnitDetailData::empty();
        }

        $stage = $this->stageResolver->resolve($shipment);

        $age = $this->ageCalculator->calculate(
            $shipment->latestTrack?->tracked_at,
            $shipment->requested_at,
            $shipment->mode?->value ?? 'sea',
        );

        $progress = $this->progressCalculator->calculate(
            $stage->current_stage,
            $stage->is_held,
            $stage->is_cancelled,
        );

        $timeline = $this->timelineBuilder->build($shipment);
        $inspection = $this->inspectionSummaryBuilder->build($unit);
        $leadTime = $this->leadTimeBuilder->build($shipment);
        $admin = $this->buildAdmin($shipment);
        $siblings = $this->siblingUnitBuilder->build($shipment, $unitId);
        $deepLinks = $this->deepLinkBuilder->build($shipment, $unit);
        $exceptions = $this->exceptionEvaluator->evaluate($shipment);

        $mode = $shipment->mode instanceof ShipmentMode
            ? $shipment->mode
            : (ShipmentMode::tryFrom((string) $shipment->mode) ?? ShipmentMode::Sea);

        return new UnitDetailData(
            unit_id: $unit->id,
            unit_reg_no: $unit->reg_no ?? '—',
            unit_model_no: $unit->model_no,
            unit_chassis_no: $unit->chassis_no,
            unit_color: $unit->color ?? '—',
            container_display: $unit->container_display,
            shipment_id: $shipment->id,
            shipment_code: $shipment->code,
            doc_number: $shipment->doc_number,
            customer_name: $shipment->relationLoaded('customer')
                ? (optional($shipment->customer)->name ?? '—')
                : '—',
            route_label: $shipment->route_label,
            mode: $mode,
            stage: $stage,
            age: $age,
            progress_pct: $progress,
            timeline: $timeline,
            inspection: $inspection,
            lead_time: $leadTime,
            admin: $admin,
            sibling_units: $siblings,
            deep_links: $deepLinks,
            exceptions: $exceptions,
        );
    }

    private function buildAdmin(Shipment $shipment): AdministrativeInfo
    {
        $voyage = $shipment->relationLoaded('voyageRecord')
            ? $shipment->voyageRecord
            : null;

        $vessel = ($voyage && $voyage->relationLoaded('vessel'))
            ? $voyage->vessel
            : null;

        $driver = $shipment->relationLoaded('driver')
            ? $shipment->driver
            : null;

        $polName = $this->resolvePortName($shipment, 'pol');
        $podName = $this->resolvePortName($shipment, 'pod');

        return new AdministrativeInfo(
            vessel_name: $vessel?->name ?? $shipment->vessel_name,
            voyage_no: $voyage?->voyage_no ?? $shipment->voyage,
            etd: $shipment->etd,
            eta: $shipment->eta,
            pol_name: $polName,
            pod_name: $podName,
            driver_name: $driver?->name,
            vehicle_plate: $shipment->vehicle_plate,
            priority: $shipment->priority,
            requested_at: $shipment->requested_at,
            delivered_at: $shipment->delivered_at,
            pic_name: $shipment->pic_name,
            pic_phone: $shipment->pic_phone,
        );
    }

    // pol and pod are shadowed by string columns; access eager-loaded Port via getRelation()
    private function resolvePortName(Shipment $shipment, string $relation): ?string
    {
        if ($shipment->relationLoaded($relation)) {
            $port = $shipment->getRelation($relation);
            if ($port !== null) {
                return $port->name;
            }
        }

        $attrs = $shipment->getAttributes();

        return isset($attrs[$relation]) && $attrs[$relation] !== '' ? $attrs[$relation] : null;
    }
}
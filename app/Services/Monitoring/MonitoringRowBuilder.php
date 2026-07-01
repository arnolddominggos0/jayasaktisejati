<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\Unit;
use App\ViewModels\Monitoring\MonitoringRowData;

/**
 * Composes a MonitoringRowData from an eagerly loaded Unit model.
 * No database queries allowed here — all data must be on the unit or its
 * eager-loaded shipment relation.
 *
 * Sprint 6.4.4: root changed from Shipment to Unit. All Shipment fields are
 * accessed via $unit->shipment. Unit fields are accessed directly on $unit.
 */
final class MonitoringRowBuilder
{
    public function __construct(
        private readonly StageResolver $stageResolver,
        private readonly AgeCalculator $ageCalculator,
        private readonly ProgressCalculator $progressCalculator,
        private readonly ExceptionEvaluator $exceptionEvaluator,
    ) {}

    public function build(Unit $unit): MonitoringRowData
    {
        $shipment = $unit->shipment;

        $stage = $this->stageResolver->resolve($shipment);

        $age = $this->ageCalculator->calculate(
            lastTrackedAt: $shipment->latestTrack?->tracked_at,
            requestedAt: $shipment->requested_at,
        );

        $progress = $this->progressCalculator->calculate(
            currentStage: $stage->current_stage,
            isHeld: $stage->is_held,
            isCancelled: $stage->is_cancelled,
        );

        // ExceptionEvaluator reads $shipment->has_ng_inspection, which in the old
        // Shipment-root query was a SELECT computed column on the shipment row.
        // Now that the root is Unit, the computed column lives on the Unit model.
        // Bridge it onto the Shipment model so ExceptionEvaluator remains unchanged.
        $shipment->has_ng_inspection = (bool) ($unit->has_ng_inspection ?? false);
        $exceptions = $this->exceptionEvaluator->evaluate($shipment);

        $statusValue = $shipment->status instanceof ShipmentStatus
            ? $shipment->status->value
            : (string) $shipment->status;
        $isFinished = in_array($statusValue, [
            ShipmentStatus::Delivered->value,
            ShipmentStatus::Cancelled->value,
        ], true);

        return new MonitoringRowData(
            shipment_id: $shipment->id,
            shipment_code: $shipment->code ?? '',
            doc_number: $shipment->doc_number ?? '',
            unit_id: $unit->id,
            unit_reg_no: $unit->reg_no,
            unit_model_no: $unit->model_no,
            unit_chassis_no: $unit->chassis_no,
            unit_color: $unit->color,
            container_display: $unit->container_display ?? $shipment->container_no,
            customer_name: $shipment->customer?->name ?? '—',
            branch_name: $shipment->branch?->name,
            route_label: $this->resolveRouteLabel($shipment),
            mode: $shipment->mode instanceof ShipmentMode
                ? $shipment->mode
                : (ShipmentMode::tryFrom((string) $shipment->mode) ?? ShipmentMode::Sea),
            voyage_no: $shipment->voyage ?: null,
            vessel_name: $shipment->vessel_name ?: null,
            stage: $stage,
            age: $age,
            progress_pct: $progress,
            exceptions: $exceptions,
            eta_label: $shipment->eta?->format('d M Y'),
            lead_time_summary: null,
            is_search_match: (bool) ($unit->is_search_match ?? false),
            is_finished: $isFinished,
        );
    }

    private function resolveRouteLabel(\App\Models\Shipment $shipment): string
    {
        $from = $shipment->originCity?->name
            ?? $shipment->route_from
            ?? '—';

        $to = $shipment->destinationCity?->name
            ?? $shipment->route_to
            ?? '—';

        return "{$from} → {$to}";
    }
}

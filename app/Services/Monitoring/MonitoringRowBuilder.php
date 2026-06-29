<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\MonitoringRowData;

/**
 * Composes a MonitoringRowData from an eagerly loaded Shipment model.
 * No database queries allowed here — all data must be on the model or its relations.
 */
final class MonitoringRowBuilder
{
    public function __construct(
        private readonly StageResolver $stageResolver,
        private readonly AgeCalculator $ageCalculator,
        private readonly ProgressCalculator $progressCalculator,
        private readonly ExceptionEvaluator $exceptionEvaluator,
    ) {}

    public function build(Shipment $shipment): MonitoringRowData
    {
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

        $exceptions = $this->exceptionEvaluator->evaluate($shipment);

        $firstUnit  = $shipment->units->first();
        $unitCount  = $shipment->units->count();

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
            unit_id: $firstUnit?->id,
            unit_reg_no: $firstUnit?->reg_no,
            unit_model_no: $firstUnit?->model_no,
            unit_chassis_no: $firstUnit?->chassis_no,
            unit_color: $firstUnit?->color,
            container_display: $firstUnit?->container_display ?? $shipment->container_no,
            customer_name: $shipment->customer?->name ?? '—',
            branch_name: $shipment->branch?->name,
            route_label: $this->resolveRouteLabel($shipment),
            mode: $shipment->mode instanceof ShipmentMode
                ? $shipment->mode
                : (ShipmentMode::tryFrom((string) $shipment->mode) ?? ShipmentMode::Sea),
            // `voyage` is the string snapshot column; `vessel_name` is the string snapshot
            voyage_no: $shipment->voyage ?: null,
            vessel_name: $shipment->vessel_name ?: null,
            stage: $stage,
            age: $age,
            progress_pct: $progress,
            exceptions: $exceptions,
            eta_label: $shipment->eta?->format('d M Y'),
            lead_time_summary: null,
            is_search_match: (bool) ($shipment->is_search_match ?? false),
            is_finished: $isFinished,
            unit_count: $unitCount,
        );
    }

    private function resolveRouteLabel(Shipment $shipment): string
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

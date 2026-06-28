<?php

namespace App\Services\Monitoring;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\CurrentStageData;

final class StageResolver
{
    public function resolve(Shipment $shipment): CurrentStageData
    {
        $current = $shipment->currentTrackStatus() ?? TrackStatus::Pickup;
        $next = $shipment->nextTrackStatus();

        $isHeld = $current === TrackStatus::Hold;
        $isCancelled = $current === TrackStatus::Cancelled;
        $isDelivered = $current === TrackStatus::Delivered;

        $order = TrackStatus::orderForMode($shipment->mode);
        $stageOrder = array_search($current, $order, true);

        return new CurrentStageData(
            current_stage: $current,
            next_stage: $next,
            stage_label: $current->label(),
            stage_order: $stageOrder !== false ? (int) $stageOrder : 0,
            is_held: $isHeld,
            is_cancelled: $isCancelled,
            is_delivered: $isDelivered,
            flow_zone: $this->resolveFlowZone($current),
        );
    }

    private function resolveFlowZone(TrackStatus $status): string
    {
        return match (true) {
            in_array($status, [TrackStatus::Pickup, TrackStatus::Handover], true) => 'pickup',
            in_array($status, [TrackStatus::Stuffing, TrackStatus::DeliveryToPort, TrackStatus::Stacking], true) => 'depot',
            in_array($status, [TrackStatus::UnitLoading, TrackStatus::OnShip, TrackStatus::VesselDepart, TrackStatus::VesselArrival], true) => 'vessel',
            in_array($status, [TrackStatus::Unloading, TrackStatus::HandoverTrucking], true) => 'port',
            in_array($status, [TrackStatus::DeliveryToCustomer, TrackStatus::Delivered], true) => 'dooring',
            $status === TrackStatus::Hold => 'hold',
            $status === TrackStatus::Cancelled => 'cancelled',
            default => 'pickup',
        };
    }
}
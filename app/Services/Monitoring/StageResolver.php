<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\CurrentStageData;

final class StageResolver
{
    /**
     * Resolve the current stage from a Shipment with latestTrack eager-loaded.
     * No DB queries — all data must be on the model or its relations.
     *
     * Sea-mode only — TrackStatus::orderSea() is used directly. When land mode
     * is added, restore orderForMode() branching here.
     */
    public function resolve(Shipment $shipment): CurrentStageData
    {
        $status = $shipment->status;
        $statusValue = $status instanceof ShipmentStatus ? $status->value : (string) $status;

        $isHeld      = $statusValue === ShipmentStatus::Hold->value;
        $isCancelled = $statusValue === ShipmentStatus::Cancelled->value;
        $isDelivered = $statusValue === ShipmentStatus::Delivered->value;

        // Determine current TrackStatus from the latest track event.
        if ($isDelivered) {
            $currentStage = TrackStatus::Delivered;
        } elseif ($isCancelled) {
            $currentStage = TrackStatus::Cancelled;
        } else {
            $track = $shipment->latestTrack;
            $currentStage = $track?->status instanceof TrackStatus
                ? $track->status
                : ($track?->status ? TrackStatus::tryFrom((string) $track->status) : null);

            if ($currentStage === null) {
                $currentStage = TrackStatus::orderSea()[0] ?? TrackStatus::Pickup;
            }
        }

        // v1: sea-mode stage sequence only.
        $order     = TrackStatus::orderSea();
        $nextStage = null;
        foreach ($order as $i => $ts) {
            if ($ts === $currentStage && isset($order[$i + 1])) {
                $nextStage = $order[$i + 1];
                break;
            }
        }

        return new CurrentStageData(
            current_stage: $currentStage,
            next_stage: $nextStage,
            stage_label: $currentStage->label(),
            stage_order: $currentStage->toNormalizedValue(),
            is_held: $isHeld,
            is_cancelled: $isCancelled,
            is_delivered: $isDelivered,
            flow_zone: $this->resolveFlowZone($currentStage),
        );
    }

    private function resolveFlowZone(TrackStatus $stage): string
    {
        return match ($stage) {
            TrackStatus::Pickup                                                        => 'pickup',
            TrackStatus::Handover, TrackStatus::Stuffing, TrackStatus::DeliveryToPort => 'planning',
            TrackStatus::Stacking, TrackStatus::UnitLoading                           => 'terminal',
            TrackStatus::OnShip, TrackStatus::VesselDepart, TrackStatus::VesselArrival => 'ocean',
            TrackStatus::Unloading                                                     => 'discharge',
            TrackStatus::HandoverTrucking, TrackStatus::DeliveryToCustomer,
            TrackStatus::Delivered                                                     => 'delivery',
            default                                                                    => 'terminal',
        };
    }
}

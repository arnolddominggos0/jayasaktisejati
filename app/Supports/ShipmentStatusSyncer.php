<?php

namespace App\Supports;

use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\Models\ShipmentTrack;

final class ShipmentStatusSyncer
{
    private const MAP = [
        'pickup'              => ShipmentStatus::Pickup,
        'handover'            => ShipmentStatus::Transit,
        'stuffing'            => ShipmentStatus::Transit,
        'delivery_to_port'    => ShipmentStatus::Transit,
        'stacking'            => ShipmentStatus::Transit,
        'unit_loading'        => ShipmentStatus::Transit,
        'onship'              => ShipmentStatus::Transit,
        'vessel_depart'       => ShipmentStatus::Transit,
        'vessel_arrival'      => ShipmentStatus::Transit,
        'unloading'           => ShipmentStatus::Transit,
        'delivery_to_customer'=> ShipmentStatus::Transit,
        'delivered'           => ShipmentStatus::Delivered,
        'hold'                => ShipmentStatus::Hold,
        'cancelled'           => ShipmentStatus::Cancelled,
    ];

    private const RANK = [
        'draft'     => 0,
        'pending'   => 1,
        'pickup'    => 2,
        'transit'   => 3,
        'delivered' => 4,
        'hold'      => 90,
        'cancelled' => 100,
    ];

    public function syncFromTrack(ShipmentTrack $track): void
    {
        $shipment = $track->shipment;
        if (!$shipment) {
            return;
        }

        if (in_array($shipment->status, [ShipmentStatus::Delivered, ShipmentStatus::Cancelled], true)) {
            return;
        }

        $candidate = $this->candidateFromTrack($track);
        if (!$candidate) {
            return;
        }

        if (in_array($candidate, [ShipmentStatus::Hold, ShipmentStatus::Cancelled], true)) {
            $this->apply($shipment, $candidate);
            return;
        }

        if ($this->rank($candidate) > $this->rank($shipment->status)) {
            $this->apply($shipment, $candidate);
        }
    }

    private function candidateFromTrack(ShipmentTrack $track): ?ShipmentStatus
    {
        $ts = $track->status;
        if (!$ts) return null;

        $ts = $ts instanceof TrackStatus ? $ts : TrackStatus::tryFrom((string) $ts);
        if (!$ts) return null;

        return self::MAP[$ts->value] ?? null;
    }

    private function rank(ShipmentStatus $state): int
    {
        return self::RANK[$state->value] ?? -1;
    }

    private function apply(Shipment $shipment, ShipmentStatus $to): void
    {
        $shipment->update(['status' => $to]);
    }
}

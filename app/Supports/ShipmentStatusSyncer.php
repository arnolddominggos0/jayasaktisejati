<?php

namespace App\Supports;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\Models\ShipmentTrack;

class ShipmentStatusSyncer
{
        public function syncFromTrack(ShipmentTrack $track): void
    {
        $shipment = $track->shipment()->with('tracks')->first();
        if (! $shipment) {
            return;
        }

        $shipment->status = $this->reduce($shipment);
        $shipment->saveQuietly();
    }

    public function reduce(Shipment $shipment): ShipmentStatus
    {
        $tracks = $shipment->relationLoaded('tracks')
            ? $shipment->tracks
            : $shipment->tracks()->get();

        $tracks = $tracks->sortBy('tracked_at')->values();

        if ($tracks->isEmpty()) {
            return ShipmentStatus::tryFrom((string) $shipment->status) ?? ShipmentStatus::Pending;
        }

        $last = $tracks->last();
        $lastVal = $last->status instanceof TrackStatus ? $last->status->value : (string) $last->status;

        if ($lastVal === TrackStatus::Cancelled->value) {
            return ShipmentStatus::Cancelled;
        }
        if ($lastVal === TrackStatus::Hold->value) {
            return ShipmentStatus::Hold;
        }

        $mode = $shipment->mode instanceof ShipmentMode ? $shipment->mode->value : (string) $shipment->mode;

        $ordered = $mode === ShipmentMode::Land->value
            ? TrackStatus::orderLand()
            : TrackStatus::orderSea();

        $rank = [];
        foreach ($ordered as $idx => $s) {
            $rank[$s->value] = $idx;
        }

        $max = null;
        foreach ($tracks as $t) {
            $ts = $t->status instanceof TrackStatus ? $t->status->value : (string) $t->status;
            if (isset($rank[$ts])) {
                if ($max === null || $rank[$ts] > $rank[$max]) {
                    $max = $ts;
                }
            }
        }

        if ($max === null) {
            return ShipmentStatus::Transit;
        }

        return TrackStatus::tryFrom($max)?->toShipmentStatus() ?? ShipmentStatus::Transit;
    }
}

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
        $tracks = ($shipment->relationLoaded('tracks')
            ? $shipment->tracks
            : $shipment->tracks()->get())
            ->filter(fn($t) => ! empty($t->tracked_at))
            ->sortBy('tracked_at')
            ->values();

        if ($tracks->isEmpty()) {
            return ShipmentStatus::Pending;
        }

        $last = $tracks->last();
        $lastVal = $last->status instanceof TrackStatus
            ? $last->status->value
            : (string) $last->status;

        if ($lastVal === TrackStatus::Cancelled->value) {
            return ShipmentStatus::Cancelled;
        }

        if ($lastVal === TrackStatus::Hold->value) {
            return ShipmentStatus::Hold;
        }

        $mode = $shipment->mode instanceof ShipmentMode
            ? $shipment->mode->value
            : (string) $shipment->mode;

        $order = TrackStatus::orderForMode($mode);

        $rank = [];
        foreach ($order as $i => $s) {
            $rank[$s->value] = $i;
        }

        $max = null;
        foreach ($tracks as $t) {
            $v = $t->status instanceof TrackStatus ? $t->status->value : (string) $t->status;
            if (isset($rank[$v]) && ($max === null || $rank[$v] > $rank[$max])) {
                $max = $v;
            }
        }

        return TrackStatus::tryFrom($max)?->toShipmentStatus()
            ?? ShipmentStatus::Transit;
    }
}

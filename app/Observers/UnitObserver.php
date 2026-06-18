<?php

namespace App\Observers;

use App\Models\Unit;
use App\Models\UnitInspection;
use App\Services\InspectionDraftAutoCreate;

class UnitObserver
{
    /**
     * When a unit is added to a shipment that already has tracks, create
     * inspection draft records for all 6 stages so the unit is visible in
     * every stage's inspection list immediately.
     *
     * Guard 1: unit must belong to a shipment.
     * Guard 2: shipment must already have at least one track (skeleton or real).
     *          Without tracks the shipment hasn't been sent to FC yet — drafts
     *          will be created by InspectionDraftAutoCreate::ensureForTrack()
     *          when sendToFc() runs and creates the skeleton tracks.
     *
     * ensureForShipmentAndStage() is idempotent (firstOrCreate) — safe to call
     * even if some stage drafts already exist.
     */
    public function created(Unit $unit): void
    {
        if (! $unit->shipment_id) {
            return;
        }

        $shipment = $unit->shipment;

        if (! $shipment) {
            return;
        }

        if (! $shipment->tracks()->exists()) {
            return;
        }

        foreach (UnitInspection::STAGES as $stage) {
            InspectionDraftAutoCreate::ensureForShipmentAndStage($shipment, $stage);
        }
    }
}

<?php
namespace App\Services;

use App\Models\Shipment;
use App\Models\Depot;
use App\Enums\ShipmentMode;

class AssignShipmentDepot
{
    public function resolve(?Shipment $shipment): ?int
    {
        if (!$shipment) return null;

        if ($shipment->assigned_depot_id) {
            return $shipment->assigned_depot_id;
        }

        if ($shipment->mode !== ShipmentMode::Sea) {
            return null;
        }

        if (!$shipment->branch_id) {
            return null;
        }

        $depots = Depot::query()
            ->where('branch_id', $shipment->branch_id)
            ->where('mode', 'sea')
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id']);

        if ($depots->count() === 1) {
            return $depots->first()->id;
        }
        
        return $depots->first()->id ?? null;
    }
}

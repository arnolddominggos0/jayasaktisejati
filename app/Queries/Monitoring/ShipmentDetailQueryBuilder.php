<?php

namespace App\Queries\Monitoring;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;

final class ShipmentDetailQueryBuilder
{
    public function build(int $shipmentId, ?int $branchId = null): ?Shipment
    {
        $query = Shipment::query()->whereKey($shipmentId);

        $this->applyEagerLoading($query);

        $shipment = $query->first();

        if (!$shipment) {
            return null;
        }

        if ($branchId && $shipment->branch_id !== null && (int) $shipment->branch_id !== (int) $branchId) {
            return null;
        }

        return $shipment;
    }

    private function applyEagerLoading(Builder $q): void
    {
        $q->with([
            'tracks' => fn($tq) => $tq->orderBy('tracked_at', 'asc'),
            'units',
            'units.inspections',
            'units.inspections.items',
            'units.inspections.checkedBy:id,name',
            'voyageRecord',
            'voyageRecord.vessel:id,name,code',
            'voyageRecord.pol:id,name',
            'voyageRecord.pod:id,name',
            'customer:id,name',
            'branch:id,name',
            'originCity:id,name',
            'destinationCity:id,name',
            'pol:id,name',
            'pod:id,name',
            'driver:id,name',
            'shippingSchedule',
            'assignedDepot',
        ]);
    }
}
<?php

namespace App\Services;

use App\Models\BranchModeDefault;
use App\Models\PortModeDefault;
use App\Models\Shipment;
use App\Models\Depot;

class DepotResolver
{
    public function resolveOutbound(?int $branchId, string $mode): ?Depot
    {
        if (!$branchId) return null;
        $row = BranchModeDefault::query()
            ->where('branch_id', $branchId)
            ->where('mode', $mode)
            ->with('outboundDepot')
            ->first();

        return $row?->outboundDepot;
    }

    public function resolveInboundByPOD(?int $portId, string $mode): ?Depot
    {
        if (!$portId) return null;
        $row = PortModeDefault::query()
            ->where('port_id', $portId)
            ->where('mode', $mode)
            ->with('destinationDepot')
            ->first();

        return $row?->destinationDepot;
    }

    public function resolveForShipment(Shipment $s): array
    {
        $mode = $s->mode?->value ?? (string)$s->mode ?? 'sea';
        $out = $s->assigned_depot_id
            ? Depot::find($s->assigned_depot_id)
            : $this->resolveOutbound($s->branch_id, $mode);

        $podId = $s->voyage?->port_to_id; // pastikan relasi voyage sudah eager loaded bila perlu
        $in  = $this->resolveInboundByPOD($podId, $mode);

        return [
            'outbound' => $out,
            'inbound'  => $in,
        ];
    }
}

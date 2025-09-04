<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use App\Models\Shipment;
use Filament\Widgets\Widget;

class RecentShipmentActivities extends Widget
{
    protected static string $view = 'filament.resources.shipment-resource.widgets.recent-activities';
    protected int|string|array $columnSpan = 4;

    public function getRecords()
    {
        return Shipment::query()
            ->latest('updated_at')
            ->limit(7)
            ->get(['id','code','status','updated_at']);
    }
}

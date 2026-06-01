<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Filament\Resources\ShipmentTrackingResource\Widgets\RecentTrackingActivities;
use App\Filament\Resources\ShipmentTrackingResource\Widgets\TrackingKpis;
use Filament\Resources\Pages\ListRecords;

class ListShipmentTrackings extends ListRecords
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TrackingKpis::class,
            RecentTrackingActivities::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}

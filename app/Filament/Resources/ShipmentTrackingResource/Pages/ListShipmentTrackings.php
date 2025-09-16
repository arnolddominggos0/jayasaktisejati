<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Filament\Resources\ShipmentTrackingResource\Widgets\RecentTrackingActivities;
use App\Filament\Resources\ShipmentTrackingResource\Widgets\TrackingKpis;
use Filament\Resources\Pages\ListRecords;

class ListShipmentTrackings extends ListRecords
{
    protected static string $resource = ShipmentTrackingResource::class;

    /**
     * Hilangkan tombol Create di header.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Widget KPI & aktivitas terbaru (opsional, jika sudah ada).
     */
    protected function getHeaderWidgets(): array
    {
        return [
            TrackingKpis::class,
            RecentTrackingActivities::class,
        ];
    }

    /**
     * Layout kolom widget header.
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            3
        ];
    }
}

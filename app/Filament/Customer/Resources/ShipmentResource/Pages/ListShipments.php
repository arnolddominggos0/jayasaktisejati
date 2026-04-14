<?php

namespace App\Filament\Customer\Resources\ShipmentResource\Pages;

use App\Filament\Customer\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * List Shipments Page
 * 
 * Display list of customer's shipments
 */
class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - customers cannot create shipments
        ];
    }

    /**
     * Get header widgets
     */
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Customer\Widgets\CustomerStatsOverview::class,
        ];
    }
}

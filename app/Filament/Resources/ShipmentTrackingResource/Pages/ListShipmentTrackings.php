<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShipmentTrackings extends ListRecords
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

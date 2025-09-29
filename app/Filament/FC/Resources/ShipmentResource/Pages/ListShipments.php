<?php

namespace App\Filament\FC\Resources\ShipmentResource\Pages;

use App\Filament\FC\Resources\ShipmentResource;
use Filament\Resources\Pages\ListRecords;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;
    protected static ?string $title   = 'Pengiriman Ditugaskan';

    protected function getHeaderActions(): array
    {
        return []; 
    }
}

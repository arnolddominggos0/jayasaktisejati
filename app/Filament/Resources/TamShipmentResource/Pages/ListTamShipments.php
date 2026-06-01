<?php

namespace App\Filament\Resources\TamShipmentResource\Pages;

use App\Filament\Resources\TamShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTamShipments extends ListRecords
{
    protected static string $resource = TamShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

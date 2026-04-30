<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipmentTracking extends EditRecord
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

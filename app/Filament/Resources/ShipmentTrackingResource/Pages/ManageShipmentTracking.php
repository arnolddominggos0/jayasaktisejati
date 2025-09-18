<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use Filament\Resources\Pages\EditRecord;

class ManageShipmentTracking extends EditRecord
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return "Timeline Tracking • {$this->record->code}";
    }
}

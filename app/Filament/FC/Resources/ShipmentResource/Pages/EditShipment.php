<?php

namespace App\Filament\FC\Resources\ShipmentResource\Pages;

use App\Filament\FC\Resources\ShipmentResource;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'status'      => $data['status'] ?? null,
            'notes'       => $data['notes'] ?? null,
            'attachments' => $data['attachments'] ?? null,
        ];
    }
}

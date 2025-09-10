<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Resources\Pages\CreateRecord;

class CreateShipment extends CreateRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // fallback code/doc (model juga handle)
        if (empty($data['code'])) {
            $data['code'] = Shipment::generateCode($data['mode'] ?? null);
        }
        if (($data['request_type'] ?? null) === 'walk_in' && empty($data['doc_number'])) {
            $data['doc_number'] = 'AUTO-' . now()->format('Ymd-His');
        }

        // fallback ETA rules (model juga handle)
        $modeCode = match (strtolower((string)($data['mode'] ?? 'land'))) {
            'sea','sea_freight' => 'SH',
            default             => 'TC',
        };
        $priority = (string)($data['priority'] ?? 'normal');
        $data['eta'] = Shipment::computeEta($modeCode, $priority)->toDateTimeString();

        return $data;
    }
}


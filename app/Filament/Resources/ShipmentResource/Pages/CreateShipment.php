<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\RequestType;
use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateShipment extends CreateRecord
{
    protected static string $resource = ShipmentResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return ShipmentResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Buat Permintaan'),
            $this->getCancelFormAction()->label('Batal')->url(ShipmentResource::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['code'])) {
            $data['code'] = Shipment::generateCode($data['mode'] ?? null);
        }

        $requestType = (string)($data['request_type'] ?? '');
        $docNumber   = isset($data['doc_number']) ? trim((string)$data['doc_number']) : null;
        $data['doc_number'] = $docNumber ?: null;

        if ($requestType === RequestType::SPPB_DO->value && empty($data['doc_number'])) {
            throw ValidationException::withMessages([
                'doc_number' => 'No. Dokumen SPPB/DO wajib diisi.',
            ]);
        }

        if ($requestType === RequestType::WALK_IN->value && empty($data['doc_number'])) {
            $data['doc_number'] = 'AUTO-' . now()->format('Ymd-His');
        }

        $base = null;
        if (!empty($data['etd'])) {
            try { $base = Carbon::parse($data['etd']); } catch (\Throwable) {}
        }
        if (!$base && !empty($data['requested_at'])) {
            try { $base = Carbon::parse($data['requested_at']); } catch (\Throwable) {}
        }

        $modeCode = match (strtolower((string)($data['mode'] ?? 'land'))) {
            'sea', 'sea_freight' => 'SH',
            default              => 'TC',
        };

        $priority = strtolower((string)($data['priority'] ?? 'normal'));
        $priority = in_array($priority, ['normal','urgent'], true) ? $priority : 'normal';

        $data['eta'] = Shipment::computeEta($modeCode, $priority, $base)->toDateTimeString();

        return $data;
    }
}

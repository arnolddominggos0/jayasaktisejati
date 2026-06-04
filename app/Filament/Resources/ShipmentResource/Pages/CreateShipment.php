<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\RequestType;
use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
            $data['doc_number'] = 'JSS-' . now()->format('Ymd-His');
        }

        $base = null;
        if (!empty($data['etd'])) {
            try {
                $base = Carbon::parse($data['etd']);
            } catch (\Throwable) {
            }
        }
        if (!$base && !empty($data['requested_at'])) {
            try {
                $base = Carbon::parse($data['requested_at']);
            } catch (\Throwable) {
            }
        }

        $modeCode = match (strtolower((string)($data['mode'] ?? 'land'))) {
            'sea', 'sea_freight' => 'SH',
            default              => 'TC',
        };

        $priority = strtolower((string)($data['priority'] ?? 'normal'));
        $priority = in_array($priority, ['normal', 'urgent'], true) ? $priority : 'normal';

        $data['eta'] = Shipment::computeEta($modeCode, $priority, $base)->toDateTimeString();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $units = $data['units'] ?? null;

        unset($data['units']);

        $shipment = Shipment::create($data);

        if (is_array($units) && count($units) > 0) {
            $toInsert = [];
            foreach ($units as $u) {
                $toInsert[] = [
                    'model_no'          => $u['model_no'] ?? null,
                    'reg_no'            => $u['reg_no'] ?? null,
                    'chassis_no'        => $u['chassis_no'] ?? null,
                    'engine_no'         => $u['engine_no'] ?? null,
                    'color'             => $u['color'] ?? null,
                    'do_number'         => $u['do_number'] ?? null,
                    'qty'               => isset($u['qty']) ? (int)$u['qty'] : 1,
                    'container_display' => $u['container_display'] ?? null,
                    'notes'             => $u['notes'] ?? null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            $shipment->units()->createMany($toInsert);
        }

        return $shipment;
    }
}

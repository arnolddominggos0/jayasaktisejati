<?php

namespace App\Filament\Resources\ArmadaResource\Pages;

use App\Enums\ArmadaStatus;
use App\Filament\Resources\ArmadaResource;
use App\Models\Armada;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateArmada extends CreateRecord
{
    protected static string $resource = ArmadaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ArmadaStatus::Available->value;

        $prefix = Armada::resolvePrefixFromTypeValue($data['type'] ?? null);
        $data['code'] = Armada::nextCodeForPrefix($prefix, pad: 3);
        return $data;
    }

    protected function afterCreate(): void
    {
        $toStatus = $this->record->status instanceof ArmadaStatus
            ? $this->record->status->value
            : (string) $this->record->status;

        $this->record->statusLogs()->create([
            'from_status' => null,
            'to_status'   => $toStatus,
            'reason'      => 'Initial onboarding',
            'changed_by'  => Auth::id(),
            'changed_at'  => now(),
        ]);
    }
}

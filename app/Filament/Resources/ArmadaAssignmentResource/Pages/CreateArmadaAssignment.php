<?php

namespace App\Filament\Resources\ArmadaAssignmentResource\Pages;

use App\Filament\Resources\ArmadaAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArmadaAssignment extends CreateRecord
{
    protected static string $resource = ArmadaAssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $prefill = request()->query('prefill', []);
        foreach (['shipment_id','branch_id','depot_id'] as $k) {
            if (isset($prefill[$k]) && empty($data[$k])) $data[$k] = $prefill[$k];
        }
        return $data;
    }
}

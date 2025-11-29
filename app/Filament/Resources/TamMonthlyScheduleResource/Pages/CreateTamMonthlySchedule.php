<?php

namespace App\Filament\Resources\TamMonthlyScheduleResource\Pages;

use App\Filament\Resources\TamMonthlyScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTamMonthlySchedule extends CreateRecord
{
    protected static string $resource = TamMonthlyScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['status']) || $data['status'] === '') {
            $data['status'] = 'draft';
        }

        return $data;
    }
}

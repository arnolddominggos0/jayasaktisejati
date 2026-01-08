<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateVesselPlan extends CreateRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['period_month'] = Carbon::parse($data['period_month'])
            ->startOfMonth()
            ->toDateString();

        return $data;
    }
}

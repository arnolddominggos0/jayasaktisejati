<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingSchedule extends CreateRecord
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['state'] = \App\Enums\ScheduleState::Draft->value;
        $base = !empty($data['etd']) ? \Illuminate\Support\Carbon::parse($data['etd']) : now()->addMonthNoOverflow();
        $data['period_month'] = $base->copy()->startOfMonth()->toDateString();
        return $data;
    }


    public function getTitle(): string
    {
        return 'Buat Jadwal (Draft)';
    }
}

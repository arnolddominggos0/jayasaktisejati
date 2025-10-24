<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

<<<<<<< HEAD
use App\Enums\ScheduleState;
=======
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
use App\Filament\Resources\ShippingScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingSchedule extends CreateRecord
{
    protected static string $resource = ShippingScheduleResource::class;
<<<<<<< HEAD

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
=======
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
}

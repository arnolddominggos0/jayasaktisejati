<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Filament\Resources\ShippingScheduleResource;
use Filament\Resources\Pages\EditRecord;

class EditShippingSchedule extends EditRecord
{
    protected static string $resource = ShippingScheduleResource::class;

    public function getTitle(): string
    {
        return 'Ubah Jadwal Kapal';
    }
}

<?php

namespace App\Filament\Resources\TamMonthlyScheduleResource\Pages;

use App\Filament\Resources\TamMonthlyScheduleResource;
use Filament\Resources\Pages\ListRecords;

class ListTamMonthlySchedules extends ListRecords
{
    protected static string $resource = TamMonthlyScheduleResource::class;

    public function getTitle(): string
    {
        return 'Daftar Paket Bulanan TAM';
    }
}

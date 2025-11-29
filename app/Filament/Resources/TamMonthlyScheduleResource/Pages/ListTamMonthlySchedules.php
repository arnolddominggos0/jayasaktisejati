<?php

namespace App\Filament\Resources\TamMonthlyScheduleResource\Pages;

use App\Filament\Resources\TamMonthlyScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTamMonthlySchedules extends ListRecords
{
    protected static string $resource = TamMonthlyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Paket Bulanan'),
        ];
    }

    public function getTitle(): string
    {
        return 'Daftar Paket Bulanan TAM';
    }
}

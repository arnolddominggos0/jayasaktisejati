<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Filament\Resources\ShippingScheduleResource;
use App\Filament\Widgets\ShippingScheduleCalendar;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShippingSchedules extends ListRecords
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function hasTable(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Buat Jadwal')];
    }

    protected function getHeaderWidgets(): array
    {
        return [ShippingScheduleCalendar::class];
    }

    public function getTitle(): string
    {
        return 'Daftar Jadwal Kapal TAM';
    }
}

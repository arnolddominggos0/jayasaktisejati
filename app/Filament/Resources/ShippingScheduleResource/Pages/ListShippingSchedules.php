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
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Buat Jadwal')];
    }

    protected function getHeaderWidgets(): array
    {
        return [ShippingScheduleCalendar::class];
    }
    
    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }

    public function getTitle(): string
    {
        return 'Daftar Jadwal Kapal TAM';
    }
}

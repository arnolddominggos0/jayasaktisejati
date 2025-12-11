<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Filament\Resources\ShippingScheduleResource;
use App\Filament\Resources\ShippingScheduleResource\Widgets\ShippingScheduleCalendar;
use App\Filament\Resources\ShippingScheduleResource\Widgets\TamKpiSummary;
use Filament\Resources\Pages\ListRecords;

class ListShippingSchedules extends ListRecords
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function hasTable(): bool
    {
        return false;
    }

    protected function hasFiltersForm(): bool
    {
        return false;
    }

    protected function hasSearchForm(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ShippingScheduleCalendar::class,
            TamKpiSummary::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }

    public function getTitle(): string
    {
        return 'Monitoring Jadwal Kapal TAM';
    }
}

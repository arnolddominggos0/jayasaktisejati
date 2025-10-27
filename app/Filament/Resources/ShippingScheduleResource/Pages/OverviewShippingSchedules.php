<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\ShippingScheduleResource;
class OverviewShippingSchedules extends Page
{
    protected static string $resource = ShippingScheduleResource::class;
    protected static string $view = 'filament.pages.schedule-overview';
    protected static ?string $title = 'Dashboard Jadwal Kapal';

    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\ScheduleKpiPlaceholder::class];
    }

    protected function getFooterWidgets(): array
    {
        // Gantt full-width di bawah
        return [\App\Filament\Widgets\ScheduleGanttPlaceholder::class];
    }
}

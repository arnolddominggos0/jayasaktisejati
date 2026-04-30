<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\ShippingScheduleResource;
use App\Filament\Widgets\ScheduleGanttPlaceholder;
use App\Filament\Widgets\ScheduleKpiPlaceholder;

class OverviewShippingSchedules extends Page
{
    protected static string $resource = ShippingScheduleResource::class;
    protected static string $view = 'filament.pages.schedule-overview';
    protected static ?string $title = 'Dashboard Jadwal Kapal';

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}

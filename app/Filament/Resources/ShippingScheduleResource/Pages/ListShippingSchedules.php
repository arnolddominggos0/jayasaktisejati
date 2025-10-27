<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Filament\Resources\ShippingScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingSchedules extends ListRecords
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toOverview')
                ->label('Dashboard Jadwal')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(route('filament.admin.resources.shipping-schedules.overview')),
            Actions\CreateAction::make()
                ->label('Buat shipping schedule'),
        ];
    }
}

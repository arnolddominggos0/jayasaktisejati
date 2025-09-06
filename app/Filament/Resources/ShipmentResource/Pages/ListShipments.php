<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use App\Filament\Resources\ShipmentResource\Widgets\ShipmentStats;
use App\Filament\Resources\ShipmentResource\Widgets\RecentShipmentActivities;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ShipmentStats::class,
            RecentShipmentActivities::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 12,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action(fn() => $this->dispatch('toast', type: 'success', message: 'Simulasi export berhasil ngab😉')),
            Actions\CreateAction::make()->label('Buat Permintaan'),
        ];
    }
}

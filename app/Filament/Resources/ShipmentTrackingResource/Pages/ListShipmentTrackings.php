<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShipmentTrackings extends ListRecords
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('dashboard')
                ->label('Pelacakan (Dashboard)')
                ->icon('heroicon-m-map-pin')
                ->color('gray')
                ->url(static::getResource()::getUrl('dashboard')),

            \Filament\Actions\CreateAction::make(),
        ];
    }
}

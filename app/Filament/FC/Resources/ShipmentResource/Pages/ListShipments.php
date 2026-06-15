<?php

namespace App\Filament\FC\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\FC\Resources\ShipmentResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;
    protected static ?string $title   = 'Riwayat Pengiriman';

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Scope the list to only completed shipments (delivered or cancelled).
     * Active shipments live exclusively in Tugas Operasional.
     */
    protected function modifyQueryWithActiveTab(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShipmentStatus::Delivered->value,
            ShipmentStatus::Cancelled->value,
        ]);
    }
}

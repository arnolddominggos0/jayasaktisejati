<?php

namespace App\Filament\FC\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\FC\Resources\ShipmentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;
    protected static ?string $title   = 'Pengiriman Ditugaskan';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'aktif' => Tab::make('Aktif')
                ->icon('heroicon-m-truck')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', [
                    ShipmentStatus::Delivered->value,
                    ShipmentStatus::Cancelled->value,
                ])),

            'selesai' => Tab::make('Selesai')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ShipmentStatus::Delivered->value,
                    ShipmentStatus::Cancelled->value,
                ])),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'aktif';
    }
}

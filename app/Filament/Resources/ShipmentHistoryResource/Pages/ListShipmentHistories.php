<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentHistoryResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListShipmentHistories extends ListRecords
{
    protected static string $resource = ShipmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Riwayat Pengiriman';
    }

    public function getSubheading(): ?string
    {
        return 'Pengiriman yang sudah Terkirim atau Dibatalkan.';
    }

     public function getTabs(): array
    {
        return [
            'Semua' => Tab::make('Semua'),

            'Terkirim' => Tab::make('Terkirim')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $q) =>
                    $q->where('status', ShipmentStatus::Delivered->value)
                ),

            'Dibatalkan' => Tab::make('Dibatalkan')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $q) =>
                    $q->where('status', ShipmentStatus::Cancelled->value)
                ),

            'KPI Target' => Tab::make('KPI Target')
                ->modifyQueryUsing(fn (Builder $q) =>
                    $q->manadoKpiTarget()
                ),
        ];
    }
}

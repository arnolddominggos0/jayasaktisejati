<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentHistoryResource;
use App\Filament\Resources\ShipmentHistoryResource\Widgets\HistoryStatsWidget;
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

    protected function getHeaderWidgets(): array
    {
        return [
            HistoryStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Riwayat Pengiriman';
    }

    public function getSubheading(): ?string
    {
        return 'Arsip pengiriman yang sudah Terkirim atau Dibatalkan.';
    }
}

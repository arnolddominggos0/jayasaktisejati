<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Widgets;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrackingKpis extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $base = Shipment::query();

        $allActive = (clone $base)->where('status', '!=', TrackStatus::Cancelled)->count();

        $inTransit = (clone $base)
            ->whereNotIn('status', [TrackStatus::Delivered, TrackStatus::Cancelled])
            ->count();

        $delivered = (clone $base)->where('status', TrackStatus::Delivered)->count();

        return [
            Stat::make('Semua', number_format($allActive))
                ->description('Pengiriman berjalan')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Dalam Proses', number_format($inTransit))
                ->description('Proses Pengiriman')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Terkirim', number_format($delivered))
                ->description('Selesai')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}

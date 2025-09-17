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
        $withTrack = Shipment::query()->whereHas('tracks');

        $delivered = (clone $withTrack)
            ->whereHas('latestTrack', function ($q) {
                $q->where('status', TrackStatus::Delivered->value);
            })
            ->count();

        $inTransit = (clone $withTrack)
            ->whereHas('latestTrack', function ($q) {
                $q->whereNotIn('status', array_map(fn($e) => $e->value, TrackStatus::finished()));
            })
            ->count();

        $allActive = (clone $withTrack)->count();

        return [
            Stat::make('Semua Ditacking', number_format($allActive))
                ->description('Pengiriman memiliki progres tracking')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Dalam Proses', number_format($inTransit))
                ->description('Belum selesai')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Terkirim', number_format($delivered))
                ->description('Selesai')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}

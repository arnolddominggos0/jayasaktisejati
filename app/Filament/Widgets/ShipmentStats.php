<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShipmentStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 8;

    protected function getStats(): array
    {
        $q = Shipment::query();

        $total = (clone $q)->count();
        $draft = (clone $q)->where('status', ShipmentStatus::Draft->value)->count();
        $inProgress = (clone $q)->whereIn('status', [
            ShipmentStatus::Pending->value,
            ShipmentStatus::Pickup->value,
            ShipmentStatus::Transit->value,
        ])->count();
        $thisMonthDone = (clone $q)
            ->where('status', ShipmentStatus::Delivered->value)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return [
            Stat::make('Total Permintaan', number_format($total))
                ->description('Semua waktu')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Sedang Diproses', number_format($draft + $inProgress))
                ->description("$draft Draft")
                ->color('warning')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Sedang Berjalan', number_format($inProgress))
                ->description('Pickup / Transit')
                ->color('info')
                ->icon('heroicon-o-truck'),

            Stat::make('Selesai Bulan Ini', number_format($thisMonthDone))
                ->description('Sudah delivered')
                ->color('success')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}

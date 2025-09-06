<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShipmentStats extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'xl' => 8,
        'lg' => 8,
        'md' => 12,
        'sm' => 12,
    ];

    protected function getColumns(): int
    {
        return 3; // 3 kartu sejajar di layar md+
    }

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $draft      = Shipment::query()->where('status', ShipmentStatus::Draft)->count();
        $inProgress = Shipment::query()->whereIn('status', ShipmentStatus::inProgress())->count();
        $delivered  = Shipment::query()->where('status', ShipmentStatus::Delivered)->count();

        return [
            Stat::make('Draft', $draft)
                ->icon('heroicon-m-document')
                ->color('gray')
                ->extraAttributes(['class' => 'py-4 px-4']),

            Stat::make('In Progress', $inProgress)
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->extraAttributes(['class' => 'py-4 px-4']),

            Stat::make('Delivered', $delivered)
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->extraAttributes(['class' => 'py-4 px-4']),
        ];
    }
}

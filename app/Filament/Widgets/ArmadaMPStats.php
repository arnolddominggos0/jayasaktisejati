<?php

namespace App\Filament\Widgets;

use App\Enums\ArmadaStatus;
use App\Models\Armada;
use App\Models\Manpower;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArmadaMpStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalArmada   = Armada::count();
        $available     = Armada::where('status', ArmadaStatus::Available->value)->count();
        $onDuty        = Armada::where('status', ArmadaStatus::OnDuty->value)->count();

        $totalMP       = Manpower::count();
        $activeMP      = Manpower::where('active', true)->count();

        return [
            Stat::make('Armada', (string)$totalArmada)
                ->description("Available: $available • OnDuty: $onDuty")
                ->descriptionIcon('heroicon-m-truck'),
            Stat::make('Manpower', (string)$totalMP)
                ->description("Aktif: $activeMP")
                ->descriptionIcon('heroicon-m-user-group'),
        ];
    }
}

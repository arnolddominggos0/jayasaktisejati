<?php

namespace App\Filament\Resources\VoyageResource\Widgets;

use App\Enums\VoyageOperationalStatus;
use App\Models\Voyage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;

class VoyageStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public ?string $period = null;

    #[On('voyage-period-updated')]
    public function updatePeriod(?string $period = null): void
    {
        $this->period = $period;
    }

    protected function getStats(): array
    {
        $query = Voyage::query();

        if ($this->period) {
            $date = Carbon::parse($this->period);

            $query->whereMonth('period_month', $date->month)
                ->whereYear('period_month', $date->year);
        }

        $voyages = $query->get();

        $scheduled = $voyages
            ->where('operational_status', VoyageOperationalStatus::SCHEDULED->value)
            ->count();

        $sailing = $voyages
            ->where('operational_status', VoyageOperationalStatus::SAILING->value)
            ->count();

        $delayed = $voyages
            ->where('operational_status', VoyageOperationalStatus::DELAYED->value)
            ->count();

        $completed = $voyages
            ->where('operational_status', VoyageOperationalStatus::COMPLETED->value)
            ->count();

        $total = $voyages->count();

        return [

            Stat::make(
                VoyageOperationalStatus::DELAYED->label(),
                $delayed
            )
                ->description('Perlu perhatian')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(VoyageOperationalStatus::DELAYED->color()),

            Stat::make(
                VoyageOperationalStatus::SAILING->label(),
                $sailing
            )
                ->description('Dalam perjalanan')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color(VoyageOperationalStatus::SAILING->color()),

            Stat::make(
                VoyageOperationalStatus::SCHEDULED->label(),
                $scheduled
            )
                ->description('Belum berangkat')
                ->descriptionIcon('heroicon-m-clock')
                ->color(VoyageOperationalStatus::SCHEDULED->color()),

            Stat::make(
                VoyageOperationalStatus::COMPLETED->label(),
                $completed
            )
                ->description('Sudah tiba')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color(VoyageOperationalStatus::COMPLETED->color()),

            Stat::make('Total Voyage', $total)
                ->description('Total periode ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}

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
            ->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::SCHEDULED)
            ->count();

        $sailing = $voyages
            ->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::SAILING)
            ->count();

        $delayed = $voyages
            ->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::DELAYED)
            ->count();

        $completed = $voyages
            ->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::COMPLETED)
            ->count();

        $total = $voyages->count();

        $statusColor = fn(VoyageOperationalStatus $status) => match ($status) {
            VoyageOperationalStatus::DELAYED   => 'danger',
            VoyageOperationalStatus::SAILING   => 'info',
            VoyageOperationalStatus::SCHEDULED => 'gray',
            VoyageOperationalStatus::COMPLETED => 'success',
        };

        return [
            Stat::make(
                VoyageOperationalStatus::DELAYED->label(),
                $delayed
            )
                ->description('Perlu perhatian')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($statusColor(VoyageOperationalStatus::DELAYED)),

            Stat::make(
                VoyageOperationalStatus::SAILING->label(),
                $sailing
            )
                ->description('Dalam perjalanan')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($statusColor(VoyageOperationalStatus::SAILING)),

            Stat::make(
                VoyageOperationalStatus::SCHEDULED->label(),
                $scheduled
            )
                ->description('Belum berangkat')
                ->descriptionIcon('heroicon-m-clock')
                ->color($statusColor(VoyageOperationalStatus::SCHEDULED)),

            Stat::make(
                VoyageOperationalStatus::COMPLETED->label(),
                $completed
            )
                ->description('Sudah tiba')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($statusColor(VoyageOperationalStatus::COMPLETED)),

            Stat::make('Total Voyage', $total)
                ->description('Total periode ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}

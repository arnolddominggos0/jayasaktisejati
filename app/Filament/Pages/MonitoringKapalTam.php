<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Services\Kpi\TamSailingKpiService;
use App\Services\Monitoring\TamMonitoringQueryService;
use App\Supports\ShippingCalendar\MonthlyCalendarBuilder;

class MonitoringKapalTam extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Jadwal';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $filter = 'all';

    public array $monthOptions = [];
    public array $calendar = [];
    public $rows;
    public array $kpi = [];

    public function mount(): void
    {
        $this->period = now()->format('Y-m');

        $this->generateMonthOptions();
        $this->loadData();
    }

    public function updatedPeriod(): void
    {
        $this->loadData();
    }

    public function updatedFilter(): void
    {
        $this->loadData();
    }

    protected function generateMonthOptions(): void
    {
        $start = now()->subMonths(12)->startOfMonth();
        $end   = now()->addMonths(12)->startOfMonth();

        while ($start <= $end) {
            $this->monthOptions[$start->format('Y-m')] =
                $start->translatedFormat('F Y');
            $start->addMonth();
        }
    }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        $this->calendar = app(MonthlyCalendarBuilder::class)
            ->forMonth($dt->year, $dt->month);

        $this->rows = app(TamMonitoringQueryService::class)
            ->getRows($this->period, $this->filter);

        $this->kpi = app(TamSailingKpiService::class)
            ->summaryForPeriod($dt->year, $dt->month);
    }
}

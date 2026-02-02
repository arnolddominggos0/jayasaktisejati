<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Models\ShippingSchedule;
use App\Models\Voyage;
use App\Services\Kpi\TamSailingKpiService;
use App\Supports\ShippingCalendar\MonthlyCalendarBuilder;

class MonitoringKapalTam extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Jadwal TAM';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static string $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $filter = 'all';

    public array $monthOptions = [];
    public array $calendar = [];
    public $rows;
    public array $kpi = [];

    public function mount(): void
    {
        $this->period = now()->format('Y-m');

        $start = now()->subMonths(12)->startOfMonth();
        $end   = now()->addMonths(12)->startOfMonth();

        while ($start <= $end) {
            $this->monthOptions[$start->format('Y-m')] =
                $start->translatedFormat('F Y');
            $start->addMonth();
        }

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

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        $this->calendar = app(MonthlyCalendarBuilder::class)
            ->forMonth($dt->year, $dt->month);

        $query = ShippingSchedule::query()
            ->with([
                'voyage.vessel',
                'voyage.pol',
                'voyage.pod',
                'voyage.sailingSla',
                'vesselChecks',
            ])
            ->whereDate('period_month', $dt->toDateString());

        if ($this->filter === 'ongoing') {
            $query->whereHas(
                'voyage',
                fn($q) =>
                $q->whereNotNull('atd_at')->whereNull('ata_at')
            );
        }

        if ($this->filter === 'risk') {
            $query->whereHas(
                'voyage',
                fn($q) =>
                $q->whereNotNull('atd_at')
                    ->whereNull('ata_at')
                    ->where('actual_sailing_days', '>=', 8)
            );
        }

        if ($this->filter === 'late') {
            $query->whereHas(
                'voyage',
                fn($q) =>
                $q->whereNotNull('ata_at')
                    ->where('actual_sailing_days', '>', 10)
            );
        }

        $this->rows = $query->get();

        $this->kpi = app(TamSailingKpiService::class)
            ->summaryForPeriod($dt->year, $dt->month);
    }
}

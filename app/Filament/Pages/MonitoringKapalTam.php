<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Models\ShippingSchedule;
use App\Models\SlaResult;
use App\Services\Monitoring\ShippingAchievementService;
use App\Supports\ShippingCalendar\MonthlyCalendarBuilder;

class MonitoringKapalTam extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Jadwal';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?int    $navigationSort  = 2;
    protected static string $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $filter = 'all';

    public array $monthOptions = [];
    public array $calendar = [];
    public $rows;
    public array $kpi = [];
    public array $achievement = [];

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
                $q->whereNotNull('atd_at')
                    ->whereNull('ata_at')
            );
        }

        if ($this->filter === 'risk') {
            $query->whereHas(
                'voyage.sailingSla',
                fn($q) => $q->where('status', 'risk')
            );
        }

        if ($this->filter === 'late') {
            $query->whereHas(
                'voyage.sailingSla',
                fn($q) => $q->where('status', 'late')
            );
        }

        $this->rows = $query->get();

        $slaQuery = SlaResult::query()
            ->where('activity', 'sailing')
            ->whereMonth('start_at', $dt->month)
            ->whereYear('start_at', $dt->year);

        $total  = $slaQuery->count();
        $ontime = (clone $slaQuery)->where('status', 'ontime')->count();
        $risk   = (clone $slaQuery)->where('status', 'risk')->count();
        $late   = (clone $slaQuery)->where('status', 'late')->count();

        $this->kpi = [
            'total'  => $total,
            'ontime' => $ontime,
            'risk'   => $risk,
            'late'   => $late,
            'compliance' => $total > 0
                ? round((($ontime + $risk) / $total) * 100, 2)
                : null,
        ];

        $this->achievement = app(ShippingAchievementService::class)
            ->summary($dt->year, $dt->month);
    }
}

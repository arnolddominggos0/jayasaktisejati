<?php

namespace App\Filament\Pages;

use App\Models\Voyage;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class MonitoringKapalTam extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Kapal TAM';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static string $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $filter = 'all';
    public string $search = '';

    public array $monthOptions = [];
    public $rows;
    public array $summary = [];

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

    public function updatedSearch(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        $query = Voyage::query()
            ->with(['vessel', 'pol', 'pod', 'sailingSla'])
            ->whereMonth('period_month', $dt->month)
            ->whereYear('period_month', $dt->year);

        if ($this->filter === 'delay') {
            $query->where('is_delayed', true);
        }

        if ($this->filter === 'ongoing') {
            $query->whereNotNull('atd_at')
                  ->whereNull('ata_at');
        }

        if ($this->filter === 'belum_update') {
            $query->where(function ($q) {
                $q->whereNull('atd_at')
                  ->orWhereNull('ata_at');
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('voyage_no', 'like', "%{$this->search}%")
                  ->orWhereHas('vessel', function ($v) {
                      $v->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        $query->orderByRaw("
            CASE
                WHEN is_delayed IS TRUE THEN 1
                WHEN ata_at IS NULL AND atd_at IS NOT NULL THEN 2
                WHEN ata_at IS NULL AND atd_at IS NULL THEN 3
                ELSE 4
            END
        ");

        $this->rows = $query->get();

        $this->summary = [
            'total'   => $this->rows->count(),
            'delay'   => $this->rows->where('is_delayed', true)->count(),
            'sla_ok'  => $this->rows->filter(fn ($v) =>
                            optional($v->sailingSla)->status !== 'late'
                        )->count(),
            'no_atd'  => $this->rows->whereNull('atd_at')->count(),
            'no_ata'  => $this->rows->whereNull('ata_at')->count(),
        ];
    }
}
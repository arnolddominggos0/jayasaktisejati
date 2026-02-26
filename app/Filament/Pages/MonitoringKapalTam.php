<?php

namespace App\Filament\Pages;

use App\Enums\SlaStatus;
use App\Enums\VoyageOperationalStatus;
use App\Models\Voyage;
use App\Models\VoyageCheckpoint;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class MonitoringKapalTam extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Monitoring Kapal TAM';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static string $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $mode = 'control';
    public string $search = '';

    public array $monthOptions = [];
    public $rows;
    public array $summary = [];
    public array $calendar = [];

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

    public function updatedPeriod() { $this->loadData(); }
    public function updatedMode() { $this->loadData(); }
    public function updatedSearch() { $this->loadData(); }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();
        $start = $dt->copy()->startOfMonth();
        $end   = $dt->copy()->endOfMonth();
        $daysCount = $dt->daysInMonth;

        $query = Voyage::query()
            ->with(['vessel', 'pol', 'pod', 'sailingSla'])
            ->whereMonth('period_month', $dt->month)
            ->whereYear('period_month', $dt->year);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('voyage_no', 'like', "%{$this->search}%")
                  ->orWhereHas('vessel',
                      fn($v) => $v->where('name', 'like', "%{$this->search}%")
                  );
            });
        }

        $this->rows = $query->get();

        /* SUMMARY BASED ON ENUM */

        $this->summary = [
            'total' => $this->rows->count(),
            'delayed' => $this->rows->filter(
                fn($v) => $v->operational_status_enum === VoyageOperationalStatus::DELAYED
            )->count(),
            'sailing' => $this->rows->filter(
                fn($v) => $v->operational_status_enum === VoyageOperationalStatus::SAILING
            )->count(),
            'completed' => $this->rows->filter(
                fn($v) => $v->operational_status_enum === VoyageOperationalStatus::COMPLETED
            )->count(),
            'sla_fail' => $this->rows->filter(
                fn($v) => $v->sla_status === SlaStatus::LATE
            )->count(),
            'no_ata' => $this->rows->whereNull('ata_at')->count(),
        ];

        /* CALENDAR */

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d = $start->copy()->day($i);
            $days[] = [
                'n' => $i,
                'date' => $d->toDateString(),
                'dow' => strtoupper($d->format('D')),
                'isWeekend' => $d->isWeekend(),
            ];
        }

        $lanes = [
            'plan_etd' => 'ETD (Plan)',
            'plan_eta' => 'ETA (Plan)',
            'act_atd'  => 'ATD (Actual)',
            'act_ata'  => 'ATA (Actual)',
        ];

        $bucket = [];
        foreach (array_keys($lanes) as $k) {
            $bucket[$k] = array_fill(1, $daysCount, []);
        }

        foreach ($this->rows as $voyage) {

            $status = $voyage->operational_status_enum;

            $chip = [
                'short' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no ?? '-',
                'color' => $status->color(),
            ];

            $map = [
                'plan_etd' => $voyage->etd,
                'plan_eta' => $voyage->eta,
                'act_atd'  => $voyage->atd_at,
                'act_ata'  => $voyage->ata_at,
            ];

            foreach ($map as $lane => $date) {
                if ($date && $date->between($start, $end, true)) {
                    $bucket[$lane][$date->day][] = $chip;
                }
            }
        }

        $this->calendar = [
            'month_label' => $dt->translatedFormat('F Y'),
            'days' => $days,
            'days_count' => $daysCount,
            'lanes' => $lanes,
            'bucket' => $bucket,
        ];
    }

    public function markCheckpointDone($checkpointId)
    {
        $checkpoint = VoyageCheckpoint::find($checkpointId);
        if (!$checkpoint) return;

        $checkpoint->update([
            'checked_at' => now(),
            'checked_by' => auth()->user()?->name,
            'status' => 'ok',
        ]);

        $this->loadData();
    }
}
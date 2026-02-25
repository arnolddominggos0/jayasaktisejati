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

    public function updatedPeriod()
    {
        $this->loadData();
    }

    public function updatedMode()
    {
        $this->loadData();
    }

    public function updatedSearch()
    {
        $this->loadData();
    }

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
                    ->orWhereHas(
                        'vessel',
                        fn($v) => $v->where('name', 'like', "%{$this->search}%")
                    );
            });
        }

        $this->rows = $query->get();

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

            $color = match ($status) {
                VoyageOperationalStatus::DELAYED   => 'bg-red-700 text-white',
                VoyageOperationalStatus::SAILING   => 'bg-blue-700 text-white',
                VoyageOperationalStatus::COMPLETED => 'bg-emerald-700 text-white',
                default                            => 'bg-gray-700 text-white',
            };

            $chip = [
                'short' => (string) ($voyage->vessel?->name ?? '-'),
                'voyage_no' => (string) ($voyage->voyage_no ?? '-'),
                'color' => $color,
                'label' => (string) $status->label(),
                'tooltip' =>
                    'Vessel: ' . ($voyage->vessel?->name ?? '-') . ' | ' .
                    'Voyage: ' . ($voyage->voyage_no ?? '-') . ' | ' .
                    'POL: ' . ($voyage->pol?->name ?? '-') . ' | ' .
                    'POD: ' . ($voyage->pod?->name ?? '-') . ' | ' .
                    'Status: ' . $status->label(),
            ];

            $etd = $voyage->etd;
            $eta = $voyage->eta;
            $atd = $voyage->atd_at;
            $ata = $voyage->ata_at;

            if ($etd && $etd->between($start, $end, true)) {
                $bucket['plan_etd'][$etd->day][] = $chip;
            }

            if ($eta && $eta->between($start, $end, true)) {
                $bucket['plan_eta'][$eta->day][] = $chip;
            }

            if ($atd && $atd->between($start, $end, true)) {
                $bucket['act_atd'][$atd->day][] = $chip;
            }

            if ($ata && $ata->between($start, $end, true)) {
                $bucket['act_ata'][$ata->day][] = $chip;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FINAL CALENDAR STRUCTURE
        |--------------------------------------------------------------------------
        */

        $this->calendar = [
            'month_label' => (string) $dt->translatedFormat('F Y'),
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
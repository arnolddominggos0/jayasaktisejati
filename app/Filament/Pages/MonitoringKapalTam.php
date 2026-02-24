<?php

namespace App\Filament\Pages;

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
    public string $focus = 'all';
    public string $search = '';

    public array $monthOptions = [];
    public $rows;
    public array $summary = [];
    public array $achievement = [];
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
    public function updatedFocus()
    {
        $this->loadData();
    }
    public function updatedSearch()
    {
        $this->loadData();
    }

    public function rescheduleVoyage($voyageId, $newDate)
    {
        $voyage = Voyage::find($voyageId);
        if (!$voyage) return;

        $newDate = Carbon::parse($newDate);

        if ($voyage->etd && $voyage->eta) {
            $duration = $voyage->etd->diffInDays($voyage->eta);
            $voyage->etd = $newDate;
            $voyage->eta = $newDate->copy()->addDays($duration);
        } else {
            $voyage->etd = $newDate;
        }

        $voyage->save();

        $this->loadData();
    }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();
        $startOfMonth = $dt->copy()->startOfMonth();
        $endOfMonth   = $dt->copy()->endOfMonth();
        $daysInMonth  = $dt->daysInMonth;

        $query = Voyage::query()
            ->with(['vessel', 'pol', 'pod', 'sailingSla'])
            ->whereMonth('period_month', $dt->month)
            ->whereYear('period_month', $dt->year);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('voyage_no', 'like', "%{$this->search}%")
                    ->orWhereHas(
                        'vessel',
                        fn($v) =>
                        $v->where('name', 'like', "%{$this->search}%")
                    );
            });
        }

        $this->rows = $query->get();

        $this->summary = [
            'total' => $this->rows->count(),
            'critical' => $this->rows->where('operational_status', 'delayed')->count(),
            'medium' => 0,
            'minor' => 0,
            'sla_fail' => $this->rows->filter(
                fn($v) =>
                optional($v->sailingSla)->status?->value === 'late'
            )->count(),
            'no_ata' => $this->rows->whereNull('ata_at')->count(),
        ];

        $days = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $startOfMonth->copy()->day($i);
            $days[] = [
                'n' => $i,
                'date' => $date->toDateString(),
                'dow' => strtoupper($date->format('D')),
                'isWeekend' => $date->isWeekend(),
            ];
        }

        $lanes = [];
        $bars = [];

        foreach ($this->rows as $voyage) {

            $laneKey = ($voyage->pol?->code ?? '-') . '-' . ($voyage->pod?->code ?? '-');
            $laneLabel = ($voyage->pol?->code ?? '-') . ' → ' . ($voyage->pod?->code ?? '-');

            $lanes[$laneKey] = $laneLabel;

            if (!$voyage->etd) continue;

            $start = $voyage->etd->copy();
            $end   = $voyage->eta ?? $voyage->etd;

            if ($end < $startOfMonth || $start > $endOfMonth) continue;

            $startDay = max($start->day, 1);
            $endDay   = min($end->day, $daysInMonth);

            $severity = $voyage->delay_severity;

            $color = match ($voyage->operational_status) {
                'delayed' => 'bg-red-500',
                'sailing' => 'bg-blue-500',
                'completed' => 'bg-green-500',
                default => 'bg-gray-400'
            };

            $bars[$laneKey][] = [
                'id' => $voyage->id,
                'start' => $startDay,
                'end' => $endDay,
                'color' => $color,
                'label' => $voyage->vessel?->name . ' — ' . $voyage->voyage_no,
                'tooltip' =>
                'Voyage: ' . $voyage->voyage_no .
                    ' | Vessel: ' . $voyage->vessel?->name .
                    ' | ETD: ' . optional($voyage->etd)->format('d M H:i') .
                    ' | ETA: ' . optional($voyage->eta)->format('d M H:i'),
            ];
        }

        $this->calendar = [
            'month_label' => $dt->translatedFormat('F Y'),
            'days' => $days,
            'days_count' => $daysInMonth,
            'lanes' => $lanes,
            'bars' => $bars,
        ];
    }

    public function markCheckpointDone($checkpointId)
    {
        $checkpoint = VoyageCheckpoint::find($checkpointId);

        if (!$checkpoint) return;

        $checkpoint->update([
            'checked_at' => now(),
            'checked_by' => auth_user()?->name,
            'status' => 'ok',
        ]);

        $this->loadData();
    }
}

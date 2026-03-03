<?php

namespace App\Filament\Pages;

use App\Models\Voyage;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MonitoringKapalTam extends Page
{
    protected static string $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $mode = 'control';
    public string $search = '';

    public array $monthOptions = [];
    public $rows;
    public array $calendar = [];
    public array $summary = [];
    public array $achievement = [];

    public function mount(): void
    {
        $this->mode = 'control';
        $this->period = now()->format('Y-m');
        $this->generateMonthOptions();
        $this->loadData();
    }

    public function updatedPeriod(): void
    {
        $this->loadData();
    }

    public function updatedSearch(): void
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

    protected function baseQuery(Carbon $dt): Builder
    {
        return Voyage::query()
            ->with(['vessel', 'sailingSla'])
            ->whereYear('period_month', $dt->year)
            ->whereMonth('period_month', $dt->month)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('voyage_no', 'like', "%{$this->search}%")
                        ->orWhereHas(
                            'vessel',
                            fn($v) => $v->where('name', 'like', "%{$this->search}%")
                        );
                });
            });
    }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        $start = $dt->copy()->startOfMonth();
        $end   = $dt->copy()->endOfMonth();
        $daysCount = $dt->daysInMonth;

        $this->rows = $this->baseQuery($dt)->get();

        $this->buildSummary();
        $this->buildAchievement();
        $this->buildCalendar($start, $end, $daysCount);
    }

    protected function buildSummary(): void
    {
        $delays = $this->rows
            ->pluck('departure_delay_minutes')
            ->filter(fn($d) => $d !== null && $d > 0);

        $this->summary = [
            'total_voyage' => $this->rows->count(),
            'delayed_count' => $delays->count(),
            'total_delay_minutes' => $delays->sum(),
            'average_delay_minutes' => $delays->count()
                ? round($delays->avg(), 0)
                : 0,
            'max_delay_minutes' => $delays->max() ?? 0,
        ];
    }

    protected function buildAchievement(): void
    {
        $total = $this->rows->count();

        $calc = function ($collection) use ($total) {
            $ok = $collection->filter(fn($v) => $v !== null && $v->value === 'ontime')->count();
            $ng = $collection->filter(fn($v) => $v !== null && $v->value === 'late')->count();

            return [
                'total' => $total,
                'ok' => $ok,
                'ng' => $ng,
                'ok_percent' => $total > 0 ? round(($ok / $total) * 100) : 0,
                'ng_percent' => $total > 0 ? round(($ng / $total) * 100) : 0,
            ];
        };

        $this->achievement = [
            'otd' => $calc($this->rows->pluck('otd_status')),
            'ota' => $calc($this->rows->pluck('ota_status')),
            'otb' => $calc($this->rows->pluck('otb_status')),
        ];
    }

    protected function buildCalendar(Carbon $start, Carbon $end, int $daysCount): void
    {
        $days = [];

        for ($i = 1; $i <= $daysCount; $i++) {
            $d = $start->copy()->day($i);

            $days[] = [
                'n' => $i,
                'dow' => strtoupper($d->format('D')),
                'isWeekend' => $d->isWeekend(),
                'isToday' => $d->isToday(),
            ];
        }

        $lanes = [
            'etd' => 'ETD (Plan)',
            'eta' => 'ETA (Plan)',
            'atd' => 'ATD (Actual)',
            'ata' => 'ATA (Actual)',
        ];

        $bucket = [];
        foreach (array_keys($lanes) as $lane) {
            $bucket[$lane] = array_fill(1, $daysCount, []);
        }

        foreach ($this->rows as $voyage) {

            $delayMinutes = $voyage->departure_delay_minutes;

            $delayLabel = null;

            if ($delayMinutes !== null && $delayMinutes > 0) {
                if ($delayMinutes >= 60) {
                    $delayLabel = 'Terlambat ' . round($delayMinutes / 60, 1) . ' jam';
                } else {
                    $delayLabel = 'Terlambat ' . $delayMinutes . ' menit';
                }
            }

            $chip = [
                'vessel' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no,
                'status' => $voyage->operational_status_enum,
                'delay_label' => $delayLabel,
                'severity' => $voyage->departure_delay_severity,
            ];

            if ($voyage->etd?->between($start, $end)) {
                $bucket['etd'][$voyage->etd->day][] = $chip;
            }

            if ($voyage->eta?->between($start, $end)) {
                $bucket['eta'][$voyage->eta->day][] = $chip;
            }

            if ($voyage->atd_at?->between($start, $end)) {
                $bucket['atd'][$voyage->atd_at->day][] = $chip;
            }

            if ($voyage->ata_at?->between($start, $end)) {
                $bucket['ata'][$voyage->ata_at->day][] = $chip;
            }
        }

        $this->calendar = [
            'month_label' => $start->translatedFormat('F Y'),
            'days' => $days,
            'days_count' => $daysCount,
            'lanes' => $lanes,
            'bucket' => $bucket,
        ];
    }
}

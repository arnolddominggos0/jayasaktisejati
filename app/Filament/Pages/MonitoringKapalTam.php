<?php

namespace App\Filament\Pages;

use App\Enums\VoyageOperationalStatus;
use App\Models\Voyage;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class MonitoringKapalTam extends Page
{
    protected static string $view = 'filament.pages.monitoring-kapal-tam';

    public string $period;
    public string $mode = 'calendar';
    public string $search = '';

    public array $monthOptions = [];
    public $rows;
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
            ->with(['vessel'])
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

            $status = $voyage->operational_status_enum;

            $chip = [
                'vessel' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no,
                'status' => $status,
            ];

            if ($voyage->etd && $voyage->etd->between($start, $end)) {
                $bucket['etd'][$voyage->etd->day][] = $chip;
            }

            if ($voyage->eta && $voyage->eta->between($start, $end)) {
                $bucket['eta'][$voyage->eta->day][] = $chip;
            }

            if ($voyage->atd_at && $voyage->atd_at->between($start, $end)) {
                $bucket['atd'][$voyage->atd_at->day][] = $chip;
            }

            if ($voyage->ata_at && $voyage->ata_at->between($start, $end)) {
                $bucket['ata'][$voyage->ata_at->day][] = $chip;
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
}

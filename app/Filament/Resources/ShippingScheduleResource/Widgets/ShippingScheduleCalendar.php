<?php

namespace App\Filament\Resources\ShippingScheduleResource\Widgets;

use App\Models\ShippingSchedule;
use App\Supports\MonthParam; 
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ShippingScheduleCalendar extends Widget
{
    protected static string $view = 'filament.widgets.shipping-schedule-calendar';
    protected int|string|array $columnSpan = 'full';    

    public string $month;     
    public int $year;         
    public int $monthNum;    

    public function mount(): void
    {
        $this->month = request('month', now()->format('Y-m'));
        [$y, $m] = array_map('intval', explode('-', $this->month));
        $this->year = $y ?: (int) now()->year;
        $this->monthNum = $m ?: (int) now()->month;
        $this->syncMonthString();
    }

    public function updatedYear(): void
    {
        $this->syncMonthString();
    }

    public function updatedMonthNum(): void
    {
        $this->syncMonthString();
    }

    protected function syncMonthString(): void
    {
        $this->month = sprintf('%04d-%02d', $this->year, $this->monthNum);
    }

    protected function getData(): array
    {
        $m = MonthParam::resolve($this->month);
        $start = $m['start'];
        $end   = $m['end'];
        $daysCount = $start->daysInMonth;

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d = $start->copy()->day($i);
            $days[] = [
                'n'         => $i,
                'date'      => $d->toDateString(),
                'isWeekend' => $d->isWeekend(),
                'dow'       => $d->isoFormat('dd'),
            ];
        }

        $cacheKey = "schedule:calendar:{$m['value']}";

        $rows = Cache::remember($cacheKey . ':rows', now()->addMinutes(10), function () use ($start, $end) {
            return ShippingSchedule::query()
                ->where('state', 'final')
                ->with(['voyage.vessel.shippingLine', 'voyage.pod'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('etd', [$start, $end])
                      ->orWhereBetween('eta', [$start, $end])
                      ->orWhere(function ($qq) use ($start, $end) {
                          $qq->where('etd', '<=', $start)->where('eta', '>=', $end);
                      });
                })
                ->orderBy('etd')
                ->get();
        });

        $totalPlan = Cache::remember($cacheKey . ':total', now()->addMinutes(10), function () use ($start, $end) {
            return (int) ShippingSchedule::query()
                ->where('state', 'final')
                ->whereBetween('etd', [$start, $end])
                ->sum('cargo_plan');
        });

        $lanes = [
            'plan_etd' => 'ETD (Plan)',
            'plan_eta' => 'ETA (Plan)',
            'act_atd'  => 'ATD (Actual)',
            'act_ata'  => 'ATA (Actual)',
            'sum_atd'  => 'Vol. ATD',
        ];

        $bucket = [];
        foreach (array_keys($lanes) as $k) $bucket[$k] = array_fill(1, $daysCount, []);
        $sumAtd = array_fill(1, $daysCount, 0);

        $in = fn($dt) => $dt && (method_exists($dt, 'betweenIncluded')
            ? $dt->betweenIncluded($start, $end)
            : $dt->between($start, $end, true));

        foreach ($rows as $s) {
            $voyage = $s->voyage;
            $vessel = $voyage?->vessel;
            $line   = $vessel?->shippingLine;

            $ls = strtoupper(substr($line?->code ?? 'LN', 0, 2));
            $vs = strtoupper(substr($vessel?->name ?? $s->vessel_name ?? 'VS', 0, 2));
            $short = $ls . $vs;

            $chip = [
                'short' => $short,
                'label' => $vessel?->code ?? strtoupper($vessel?->name ?? $s->vessel_name ?? 'N/A'),
                'head'  => trim(($vessel?->name ?: '-') . ' ' . ($s->voyage_no ?: $voyage?->voyage_no ?? '')),
                'sub'   => implode(' • ', array_filter([
                    $line?->name ?? '-',
                    'Plan ' . (int) ($s->cargo_plan ?? 0),
                    $vessel?->capacity ? 'Cap ' . $vessel->capacity : null,
                ])),
                'plan'  => (int) ($s->cargo_plan ?? 0),
            ];

            $etd = $s->etd ?? $voyage?->etd;
            $eta = $s->eta ?? $voyage?->eta;
            $atd = $voyage?->atd_at;
            $ata = $voyage?->ata_at;

            if ($in($etd)) $bucket['plan_etd'][$etd->day][] = $chip;
            if ($in($eta)) $bucket['plan_eta'][$eta->day][] = $chip;
            if ($in($atd)) {
                $bucket['act_atd'][$atd->day][] = $chip;
                $sumAtd[$atd->day] += $chip['plan'];
            }
            if ($in($ata)) $bucket['act_ata'][$ata->day][] = $chip;
        }

        for ($i = 1; $i <= $daysCount; $i++) {
            if ($sumAtd[$i] > 0) {
                $bucket['sum_atd'][$i][] = [
                    'short' => (string) $sumAtd[$i],
                    'label' => (string) $sumAtd[$i],
                    'head'  => '',
                    'sub'   => '',
                ];
            }
        }

        $monthOptions = [];
        foreach (range(1, 12) as $mm) {
            $monthOptions[$mm] = Carbon::createFromDate(null, $mm, 1)->translatedFormat('F');
        }
        $yearOptions = range(now()->year - 2, now()->year + 2);

        return [
            'month_label'   => $m['label'],
            'days'          => $days,
            'days_count'    => $daysCount,
            'lanes'         => $lanes,
            'bucket'        => $bucket,
            'total_plan'    => (int) $totalPlan,
            'today'         => $m['today'],
            'month_options' => $monthOptions,
            'year_options'  => $yearOptions,
        ];
    }
}

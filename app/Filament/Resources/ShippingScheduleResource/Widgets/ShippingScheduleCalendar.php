<?php

namespace App\Filament\Resources\ShippingScheduleResource\Widgets;

use App\Models\ShippingSchedule;
use App\Supports\MonthParam;
use Filament\Widgets\Widget;
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

        $this->year     = $y ?: (int) now()->year;
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
        $m         = MonthParam::resolve($this->month);
        $start     = $m['start'];
        $end       = $m['end'];
        $daysCount = $start->daysInMonth;

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d      = $start->copy()->day($i);
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
        foreach (array_keys($lanes) as $k) {
            $bucket[$k] = array_fill(1, $daysCount, []);
        }

        $sumAtd = array_fill(1, $daysCount, 0);

        $kpiTotal   = 0;
        $kpiOnTime  = 0;
        $kpiLate    = 0;

        $voyageTable = [];

        $in = fn($dt) => $dt && (method_exists($dt, 'betweenIncluded')
            ? $dt->betweenIncluded($start, $end)
            : $dt->between($start, $end, true));

        $laneClass = function (string $lane, ?string $slaStatus = null): string {
            $base = match ($lane) {
                'plan_etd' => 'bg-yellow-50 text-yellow-900',
                'plan_eta' => 'bg-amber-50 text-amber-900',
                'act_atd'  => 'bg-emerald-50 text-emerald-900',
                'act_ata'  => 'bg-purple-50 text-purple-900',
                'sum_atd'  => 'bg-orange-50 text-orange-900',
                default    => 'bg-gray-50 text-gray-900',
            };

            if (in_array($lane, ['act_atd', 'act_ata'], true)) {
                if ($slaStatus === 'late') {
                    return 'bg-rose-100 text-rose-800 border border-rose-300';
                }

                if ($slaStatus === 'on_time') {
                    return 'bg-emerald-100 text-emerald-800 border border-emerald-300';
                }
            }

            return $base . ' border border-gray-200';
        };

        foreach ($rows as $s) {
            $voyage = $s->voyage;
            $vessel = $voyage?->vessel;
            $line   = $vessel?->shippingLine;

            $ls    = strtoupper(substr($line?->code ?? 'LN', 0, 2));
            $vs    = strtoupper(substr($vessel?->name ?? $s->vessel_name ?? 'VS', 0, 2));
            $short = $ls . $vs;

            $etd = $s->etd ?? $voyage?->etd;
            $eta = $s->eta ?? $voyage?->eta;
            $atd = $voyage?->atd_at;
            $ata = $voyage?->ata_at;

            $leadTime  = null;
            $slaStatus = null;
            $isUrgent  = (bool) ($s->is_urgent ?? false);

            if ($etd && $ata) {
                $leadTime = $etd->diffInDays($ata);
                $limit    = $isUrgent ? 17 : 19;

                if ($leadTime > $limit) {
                    $slaStatus = 'late';
                } else {
                    $slaStatus = 'on_time';
                }

                $kpiTotal++;
                if ($slaStatus === 'on_time') {
                    $kpiOnTime++;
                } elseif ($slaStatus === 'late') {
                    $kpiLate++;
                }
            }

            $subParts = [
                $line?->name ?? '-',
                'Plan ' . (int) ($s->cargo_plan ?? 0),
                $vessel?->capacity ? 'Cap ' . $vessel->capacity : null,
            ];

            if ($leadTime !== null) {
                $mode       = $isUrgent ? 'Urgent' : 'Reg';
                $subParts[] = 'LT ' . $leadTime . 'd ' . $mode;
            }

            $baseChip = [
                'short'      => $short,
                'label'      => $vessel?->code ?? strtoupper($vessel?->name ?? $s->vessel_name ?? 'N/A'),
                'head'       => trim(($vessel?->name ?: '-') . ' ' . ($s->voyage_no ?: $voyage?->voyage_no ?? '')),
                'sub'        => implode(' • ', array_filter($subParts)),
                'plan'       => (int) ($s->cargo_plan ?? 0),
                'lead_time'  => $leadTime,
                'sla_status' => $slaStatus,
                'is_urgent'  => $isUrgent,
            ];

            if ($in($etd)) {
                $bucket['plan_etd'][$etd->day][] = $baseChip + [
                    'class' => $laneClass('plan_etd'),
                ];
            }

            if ($in($eta)) {
                $bucket['plan_eta'][$eta->day][] = $baseChip + [
                    'class' => $laneClass('plan_eta'),
                ];
            }

            if ($in($atd)) {
                $bucket['act_atd'][$atd->day][] = $baseChip + [
                    'class' => $laneClass('act_atd', $slaStatus),
                ];
                $sumAtd[$atd->day] += $baseChip['plan'];
            }

            if ($in($ata)) {
                $bucket['act_ata'][$ata->day][] = $baseChip + [
                    'class' => $laneClass('act_ata', $slaStatus),
                ];
            }

            $voyageTable[] = [
                'voyage_id' => $s->id,
                'voyage'    => $s->voyage_no ?? $voyage?->voyage_no ?? '-',
                'vessel'    => $vessel?->name ?? $s->vessel_name ?? '-',
                'line'      => $line?->name ?? '-',
                'etd'       => $etd?->format('d M') ?? '-',
                'eta'       => $eta?->format('d M') ?? '-',
                'atd'       => $atd?->format('d M') ?? '-',
                'ata'       => $ata?->format('d M') ?? '-',
                'lead'      => $leadTime ? $leadTime . ' hari' : '-',
                'sla'       => $slaStatus,
                'urgent'    => $isUrgent,
                'volume'    => $s->cargo_plan ?? 0,
            ];
        }

        for ($i = 1; $i <= $daysCount; $i++) {
            if ($sumAtd[$i] > 0) {
                $bucket['sum_atd'][$i][] = [
                    'short' => (string) $sumAtd[$i],
                    'label' => (string) $sumAtd[$i],
                    'head'  => '',
                    'sub'   => '',
                    'class' => 'mx-auto my-0.5 h-6 w-8 text-center text-[11px] font-semibold bg-orange-50 text-orange-900 rounded-sm border border-gray-200 flex items-center justify-center',
                ];
            }
        }

        $kpiCompletion = $kpiTotal > 0
            ? (int) round(($kpiOnTime / $kpiTotal) * 100)
            : 0;

        $monthOptions = [];
        foreach (range(1, 12) as $mm) {
            $monthOptions[$mm] = \Illuminate\Support\Carbon::createFromDate(null, $mm, 1)->translatedFormat('F');
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
            'kpi'           => [
                'total'      => $kpiTotal,
                'on_time'    => $kpiOnTime,
                'late'       => $kpiLate,
                'completion' => $kpiCompletion,
            ],
            'voyage_table'  => $voyageTable,
        ];
    }
}

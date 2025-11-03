<?php

namespace App\Filament\Widgets;

use App\Enums\ScheduleState;
use App\Models\ShippingSchedule;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ShippingScheduleCalendar extends Widget
{
    protected static string $view = 'filament.widgets.shipping-schedule-calendar';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->startOfMonth();
        if ($m = request('month')) {
            try {
                $start = Carbon::createFromFormat('Y-m', $m)->startOfMonth();
            } catch (\Throwable) {
            }
        }

        $end       = $start->copy()->endOfMonth();
        $daysCount = $start->daysInMonth;

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d = $start->copy()->day($i);
            $days[] = ['n' => $i, 'date' => $d->toDateString(), 'isWeekend' => $d->isWeekend()];
        }

        $rows = ShippingSchedule::query()
            ->where('state', ScheduleState::Final)
            ->with(['voyage.vessel.shippingLine'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('etd', [$start, $end])
                    ->orWhereBetween('eta', [$start, $end])
                    ->orWhere(fn($qq) => $qq->where('etd', '<=', $start)->where('eta', '>=', $end));
            })
            ->orderBy('etd')
            ->get();

        $totalPlan = (clone ShippingSchedule::query())
            ->where('state', ScheduleState::Final)
            ->whereBetween('etd', [$start, $end])
            ->sum('cargo_plan');

        $lanes = [
            'plan_etd' => 'ETD (Plan)',
            'plan_eta' => 'ETA (Plan)',
            'act_atd'  => 'ATD (Actual)',
            'act_ata'  => 'ATA (Actual)',
            'sum_atd'  => 'JSS Volume (ATD)',
        ];

        $bucket = [];
        foreach (array_keys($lanes) as $k) {
            $bucket[$k] = array_fill(1, $daysCount, []);
        }
        $sumAtd = array_fill(1, $daysCount, 0);

        foreach ($rows as $s) {
            $voyage = $s->voyage;
            $vessel = $voyage?->vessel;
            $line   = $vessel?->shippingLine;

            $chip = [
                'label' => $vessel?->code ?? 'N/A',
                'head'  => trim(($vessel?->name ?: '-') . ' ' . ($s->voyage_no ?: $voyage?->voyage_no ?? '')),
                'sub'   => implode(' • ', array_filter([
                    $line?->name ?? '-',
                    'Plan ' . (int) ($s->cargo_plan ?? 0),
                    $vessel?->capacity ? 'Cap ' . $vessel->capacity : null,
                ])),
            ];

            $etd = $s->etd ?? $voyage?->etd;
            $eta = $s->eta ?? $voyage?->eta;
            $atd = $voyage?->atd_at;
            $ata = $voyage?->ata_at;

            if ($etd && $etd->betweenIncluded($start, $end)) $bucket['plan_etd'][$etd->day][] = $chip;
            if ($eta && $eta->betweenIncluded($start, $end)) $bucket['plan_eta'][$eta->day][] = $chip;
            if ($atd && $atd->betweenIncluded($start, $end)) {
                $bucket['act_atd'][$atd->day][] = $chip;
                $sumAtd[$atd->day] += (int) ($s->cargo_plan ?? 0);
            }
            if ($ata && $ata->betweenIncluded($start, $end)) $bucket['act_ata'][$ata->day][] = $chip;
        }

        for ($i = 1; $i <= $daysCount; $i++) {
            if ($sumAtd[$i] > 0) {
                $bucket['sum_atd'][$i][] = ['label' => (string) $sumAtd[$i], 'head' => '', 'sub' => ''];
            }
        }

        return [
            'month_label' => $start->translatedFormat('F Y'),
            'prev'        => $start->copy()->subMonth()->format('Y-m'),
            'next'        => $start->copy()->addMonth()->format('Y-m'),
            'days'        => $days,
            'days_count'  => $daysCount,
            'lanes'       => $lanes,
            'bucket'      => $bucket,
            'total_plan'  => (int) $totalPlan,
        ];
    }
}

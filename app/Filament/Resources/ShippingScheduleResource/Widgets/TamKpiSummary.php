<?php

namespace App\Filament\Resources\ShippingScheduleResource\Widgets;

use App\Models\ShippingSchedule;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class TamKpiSummary extends Widget
{
    protected static string $view = 'filament.widgets.tam-kpi-summary';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxContentWidth = 'full';

    public function getData(): array
    {
        $monthParam = request('month', now()->format('Y-m'));
        [$y, $m] = array_map('intval', explode('-', $monthParam));
        $start = Carbon::create($y ?: now()->year, $m ?: now()->month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $pol = strtoupper(request('tam_pol', config('tam.route.pol_code', 'JKT')));
        $pod = strtoupper(request('tam_pod', config('tam.route.pod_code', 'BTG')));
        $force = (bool) config('tam.route.force', true);

        $base = ShippingSchedule::query()
            ->with(['voyage.vessel.shippingLine', 'voyage.pol', 'voyage.pod', 'voyage'])
            ->whereBetween('shipping_schedules.period_month', [$start->toDateString(), $end->toDateString()]);

        if ($force && $pol !== '' && $pod !== '') {
            $base->whereHas('voyage.pol', fn($q) => $q->whereRaw('upper(code) = ?', [$pol]))
                ->whereHas('voyage.pod', fn($q) => $q->whereRaw('upper(code) = ?', [$pod]));
        }

        $schedules = (clone $base)
            ->leftJoin('voyages', 'shipping_schedules.voyage_id', '=', 'voyages.id')
            ->orderBy('voyages.etd', 'asc')
            ->select('shipping_schedules.*')
            ->get();

        $totalPlan = $schedules->sum(fn($s) => (int) ($s->cargo_plan ?? 0));
        $total = $onTime = $late = $urgent = $totalLead = $countLead = 0;

        foreach ($schedules as $s) {
            $v = $s->voyage;
            $etd = $v?->etd;
            $ata = $v?->ata_at;
            if ($etd && $ata) {
                $total++;
                $lead = $etd->diffInDays($ata);
                $totalLead += $lead;
                $countLead++;
                if ($lead <= ($s->kpi_sailing_days ?? 11)) $onTime++;
                else $late++;
            }
            if ($s->is_urgent) $urgent++;
        }

        $avgLead = $countLead ? round($totalLead / $countLead, 1) : null;
        $completion = $total ? (int) round(($onTime / $total) * 100) : 0;

        $list = $schedules->map(function ($s) {
            $v = $s->voyage;
            $lead = $v?->etd && $v?->ata_at ? $v->etd->diffInDays($v->ata_at) : null;
            $sla = is_null($lead) ? null : ($lead <= ($s->kpi_sailing_days ?? 11) ? 'on-time' : 'late');
            $atdVol = $s->cargo_actual ?? $v?->cargo_actual ?? null;

            return [
                'id' => $s->id,
                'period' => $s->period_month?->format('M Y') ?? null,
                'jss' => $s->jss,
                'shipping_line' => $v?->vessel?->shippingLine?->name,
                'vessel' => $v?->vessel?->name,
                'voyage_no' => $v?->voyage_no,
                'lane' => ($v?->pol?->code ?? '-') . ' → ' . ($v?->pod?->code ?? '-'),
                'etd' => $v?->etd?->toDateTimeString(),
                'eta' => $v?->eta?->toDateTimeString(),
                'atd' => $v?->atd_at?->toDateTimeString(),
                'ata' => $v?->ata_at?->toDateTimeString(),
                'lead_time' => $lead,
                'sla_status' => $sla,
                'plan' => $s->cargo_plan,
                'actual' => $s->cargo_actual ?? $v?->cargo_actual ?? null,
                'vol_atd' => $atdVol,
                'delay' => (bool) optional($v)->is_delayed,
                'delay_reason' => optional($v)->delay_reason,
                'urgent' => (bool) $s->is_urgent,
            ];
        })->values();

        return [
            'month_label' => $start->translatedFormat('F Y'),
            'total_plan' => $totalPlan,
            'kpi' => [
                'total' => $total,
                'on_time' => $onTime,
                'late' => $late,
                'urgent' => $urgent,
                'avg_lead_time' => $avgLead,
                'completion' => $completion,
            ],
            'rows' => $list,
            'pol' => $pol,
            'pod' => $pod,
        ];
    }
}

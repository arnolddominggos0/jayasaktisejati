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
        $end = $start->copy()->endOfMonth();
        $daysCount = $start->daysInMonth;

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d = $start->copy()->day($i);
            $days[] = ['n' => $i, 'date' => $d->toDateString(), 'isWeekend' => $d->isWeekend()];
        }

        $rows = ShippingSchedule::query()
            ->where('state', ScheduleState::Final)
            ->with([
                'vessel.shippingLine',
                'items' => fn($q) => $q->orderBy('etd'),
            ])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('etd', [$start, $end])
                    ->orWhereBetween('eta', [$start, $end])
                    ->orWhere(fn($qq) => $qq->where('etd', '<=', $start)->where('eta', '>=', $end))
                    ->orWhereHas('items', function ($iq) use ($start, $end) {
                        $iq->whereBetween('etd', [$start, $end])
                            ->orWhereBetween('eta', [$start, $end])
                            ->orWhere(fn($qq) => $qq->where('etd', '<=', $start)->where('eta', '>=', $end));
                    });
            })
            ->orderBy('etd')
            ->get();

        $gradients = [
            ['#2563eb', '#1d4ed8'],
            ['#059669', '#047857'],
            ['#ea580c', '#c2410c'],
            ['#7c3aed', '#6d28d9'],
            ['#db2777', '#be185d'],
            ['#0ea5e9', '#0284c7'],
        ];
        $pick = fn(?string $seed) => $gradients[abs(crc32($seed ?? '')) % count($gradients)];

        $items = [];
        foreach ($rows as $s) {
            $line = $s->vessel?->shippingLine?->name ?? '-';
            $vessel = $s->vessel?->name ?? ($s->vessel_name ?? '-');
            [$c1, $c2] = $pick($line);

            if ($s->items->count() > 0) {
                foreach ($s->items as $it) {
                    if (!$it->etd || !$it->eta) continue;
                    if ($it->eta->lt($start) || $it->etd->gt($end)) continue;
                    $startIdx = max(1, $it->etd->lt($start) ? 1 : $it->etd->day);
                    $endIdx = min($daysCount, $it->eta->gt($end) ? $daysCount : $it->eta->day);

                    $items[] = [
                        'title'  => trim(($it->vessel_name ?: $vessel) . ' ' . ($it->voyage_no ?: '')),
                        'sub'    => trim(($line ?: '-') . ' • ETD ' . $it->etd->translatedFormat('d M Y H:i') . ' • ETA ' . $it->eta->translatedFormat('d M Y H:i') . ' • Plan ' . (int) ($it->cargo_plan ?? 0)),
                        'start'  => $startIdx,
                        'length' => max(1, $endIdx - $startIdx + 1),
                        'c1'     => $c1,
                        'c2'     => $c2,
                        'badge'  => 'final',
                    ];
                }
            } else {
                if (!$s->etd || !$s->eta) continue;
                if ($s->eta->lt($start) || $s->etd->gt($end)) continue;
                $startIdx = max(1, $s->etd->lt($start) ? 1 : $s->etd->day);
                $endIdx = min($daysCount, $s->eta->gt($end) ? $daysCount : $s->eta->day);

                $items[] = [
                    'title'  => trim($vessel . ' ' . ($s->voyage_no ?: '')),
                    'sub'    => trim(($line ?: '-') . ' • ETD ' . $s->etd->translatedFormat('d M Y H:i') . ' • ETA ' . $s->eta->translatedFormat('d M Y H:i') . ' • Plan ' . (int) ($s->cargo_plan_total ?? 0)),
                    'start'  => $startIdx,
                    'length' => max(1, $endIdx - $startIdx + 1),
                    'c1'     => $c1,
                    'c2'     => $c2,
                    'badge'  => 'final',
                ];
            }
        }

        usort($items, fn($a, $b) => ($a['start'] <=> $b['start']) ?: ($b['length'] <=> $a['length']));

        return [
            'month_label' => $start->translatedFormat('F Y'),
            'prev'        => $start->copy()->subMonth()->format('Y-m'),
            'next'        => $start->copy()->addMonth()->format('Y-m'),
            'days'        => $days,
            'days_count'  => $daysCount,
            'today_idx'   => now()->betweenIncluded($start, $end) ? now()->day : null,
            'first_w'     => 360,
            'row_h'       => 64,
            'col_min'     => 36,
            'items'       => $items,
        ];
    }
}

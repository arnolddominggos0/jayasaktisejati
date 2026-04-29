<?php

namespace App\Filament\FC\Widgets;

use Filament\Widgets\ChartWidget;

use App\Models\Shipment;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FcStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Status 14 Hari';
    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $u     = Filament::auth()->user();
        $fcCol = (string) config('fc.shipment_fc_column', 'coordinator_id');

        $from  = Carbon::now()->subDays(13)->startOfDay();
        $to    = Carbon::now()->endOfDay();

        $rows = Shipment::query()
            ->selectRaw("date(updated_at) as d, status, count(*) as c")
            ->when($u?->branch_id, fn(Builder $q) => $q->where(function ($w) use ($u) {
                $w->where('branch_id', $u->branch_id)->orWhereNull('branch_id');
            }))
            ->when($u?->office_id ?? null, fn(Builder $q) => $q->where(function ($w) use ($u) {
                $w->where('origin_office_id', $u->office_id)->orWhere('destination_office_id', $u->office_id)
                    ->orWhereNull('origin_office_id');
            }))
            ->where($fcCol, $u->id)
            ->whereBetween('updated_at', [$from, $to])
            ->whereIn('status', ['pickup', 'loading', 'on_transit', 'delivered', 'on_hold'])
            ->groupBy('d', 'status')
            ->orderBy('d')
            ->get();

        $days = collect(range(0, 13))->map(fn($i) => Carbon::now()->subDays(13 - $i)->format('Y-m-d'))->values();
        $seriesKeys = ['pickup', 'loading', 'on_transit', 'delivered', 'on_hold'];

        $matrix = [];
        foreach ($seriesKeys as $k) {
            $matrix[$k] = $days->map(fn($d) => 0)->toArray();
        }

        foreach ($rows as $r) {
            $day = Carbon::parse($r->d)->format('Y-m-d');
            $idx = $days->search($day);
            $status = $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status;

            if ($idx !== false && isset($matrix[$status])) {
                $matrix[$status][$idx] = (int) $r->c;
            }
        }

        return [
            'datasets' => [
                ['label' => 'Pickup',      'data' => $matrix['pickup']],
                ['label' => 'Loading',     'data' => $matrix['loading']],
                ['label' => 'On Transit',  'data' => $matrix['on_transit']],
                ['label' => 'Delivered',   'data' => $matrix['delivered']],
                ['label' => 'On Hold',     'data' => $matrix['on_hold']],
            ],
            'labels' => $days->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected int|string|array $columnSpan = 'full';
}

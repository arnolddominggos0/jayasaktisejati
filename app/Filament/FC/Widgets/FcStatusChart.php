<?php

namespace App\Filament\FC\Widgets;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FcStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Update Status 14 Hari';
    protected static ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $u = Filament::auth()->user();
        $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : ($u?->effectiveBranchId() ?? null);
        $depotId = app()->bound('scope.depot_id') ? app('scope.depot_id') : null;

        $from = Carbon::now()->subDays(13)->startOfDay();
        $to = Carbon::now()->endOfDay();

        $rows = ShipmentTrack::query()
            ->selectRaw("date(tracked_at) as d, status, count(*) as c")
            ->whereNotNull('tracked_at')
            ->whereBetween('tracked_at', [$from, $to])
            ->whereHas('shipment', function (Builder $s) use ($u, $branchId, $depotId) {
                $s->where('mode', 'sea');

                if ($branchId) {
                    $s->where(fn ($w) => $w->where('branch_id', $branchId)->orWhereNull('branch_id'));
                }

                if ($depotId) {
                    $s->where(function ($w) use ($depotId, $u) {
                        $w->where('assigned_depot_id', $depotId)
                            ->orWhere('coordinator_id', $u?->id);
                    });
                } else {
                    $s->where('coordinator_id', $u?->id);
                }
            })
            ->groupBy('d', 'status')
            ->orderBy('d')
            ->get();

        $days = collect(range(0, 13))
            ->map(fn ($i) => Carbon::now()->subDays(13 - $i)->format('Y-m-d'))
            ->values();

        // Group statuses into milestone categories for cleaner chart
        $categories = [
            'pickup' => ['pickup'],
            'handover_stuffing' => ['handover', 'stuffing'],
            'port_terminal' => ['delivery_to_port', 'stacking', 'unit_loading'],
            'voyage' => ['onship', 'vessel_depart', 'vessel_arrival'],
            'unloading_delivery' => ['unloading', 'delivery_to_customer', 'delivered'],
            'hold_cancel' => ['hold', 'cancelled'],
        ];

        $labels = [
            'pickup' => 'Penjemputan',
            'handover_stuffing' => 'Handover / Stuffing',
            'port_terminal' => 'Pelabuhan / Terminal',
            'voyage' => 'Perjalanan Kapal',
            'unloading_delivery' => 'Bongkar / Antar',
            'hold_cancel' => 'Hold / Cancel',
        ];

        $matrix = [];
        foreach (array_keys($categories) as $k) {
            $matrix[$k] = $days->map(fn () => 0)->toArray();
        }

        foreach ($rows as $r) {
            $day = Carbon::parse($r->d)->format('Y-m-d');
            $idx = $days->search($day);
            $status = $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status;

            if ($idx === false) {
                continue;
            }

            foreach ($categories as $cat => $statuses) {
                if (in_array($status, $statuses, true)) {
                    $matrix[$cat][$idx] += (int) $r->c;
                }
            }
        }

        $datasets = [];
        $colors = [
            'pickup' => ['bg' => 'rgba(59, 130, 246, 0.5)', 'border' => 'rgb(59, 130, 246)'],
            'handover_stuffing' => ['bg' => 'rgba(16, 185, 129, 0.5)', 'border' => 'rgb(16, 185, 129)'],
            'port_terminal' => ['bg' => 'rgba(245, 158, 11, 0.5)', 'border' => 'rgb(245, 158, 11)'],
            'voyage' => ['bg' => 'rgba(99, 102, 241, 0.5)', 'border' => 'rgb(99, 102, 241)'],
            'unloading_delivery' => ['bg' => 'rgba(20, 184, 166, 0.5)', 'border' => 'rgb(20, 184, 166)'],
            'hold_cancel' => ['bg' => 'rgba(239, 68, 68, 0.5)', 'border' => 'rgb(239, 68, 68)'],
        ];

        foreach ($categories as $cat => $_) {
            $datasets[] = [
                'label' => $labels[$cat],
                'data' => $matrix[$cat],
                'backgroundColor' => $colors[$cat]['bg'],
                'borderColor' => $colors[$cat]['border'],
                'fill' => true,
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

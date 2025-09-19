<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ShipmentsTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Shipment (12 Minggu)';
    protected int|string|array $columnSpan = ['xl' => 2];
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $start = Carbon::now()->subWeeks(11)->startOfWeek();
        $end   = Carbon::now()->endOfWeek();

        $scope = function ($q) {
            $u = auth_user();
            if (! $u || (method_exists($u, 'hasRole') && $u->hasRole('super_admin'))) return;
            if (Schema::hasColumn('shipments', 'branch_id') && $u->branch_id) {
                $q->where('branch_id', $u->branch_id);
            } elseif (Schema::hasColumn('shipments', 'depot_id') && $u->depot_id) {
                $q->where('depot_id', $u->depot_id);
            }
        };

        $rows = Shipment::query()
            ->tap($scope)
            ->selectRaw("DATE_TRUNC('week', created_at) AS wk, COUNT(*) AS total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('wk')
            ->orderBy('wk')
            ->get();

        $labels = [];
        $data   = [];

        for ($d = $start->copy(); $d <= $end; $d->addWeek()) {
            $labels[] = $d->isoFormat('DD MMM');
            $match = $rows->first(fn ($r) => Carbon::parse($r->wk)->equalTo($d));
            $data[] = $match ? (int) $match->total : 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Shipment dibuat',
                'data'  => $data,
                'tension' => 0.3,
                'fill' => false,
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

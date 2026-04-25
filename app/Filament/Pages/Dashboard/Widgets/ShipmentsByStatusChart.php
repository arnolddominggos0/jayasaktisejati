<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ShipmentsByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Shipment (Bulan Ini)';
    protected int|string|array $columnSpan = ['xl' => 1];
    protected static ?string $pollingInterval = '60s';
    private const LABELS = [
        'draft'      => 'Draft',
        'pending'    => 'Pending',
        'pickup'     => 'Penjemputan',
        'transit'    => 'Transit',
        'delivered'  => 'Terkirim',
        'hold'       => 'Tertahan',
        'cancelled'  => 'Dibatalkan',
    ];

    private const COLORS = [
        'draft'      => '#CBD5E1', 
        'pending'    => '#FBBF24', 
        'pickup'     => '#34D399', 
        'transit'    => '#60A5FA', 
        'delivered'  => '#22C55E', 
        'hold'       => '#F59E0B', 
        'cancelled'  => '#EF4444', 
    ];

    private const ORDER = ['draft', 'pending', 'pickup', 'transit', 'delivered', 'hold', 'cancelled'];

    protected function getData(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $scope = function ($q) {
            $u = auth_user();
            if (! $u || (method_exists($u, 'hasRole') && $u->hasRole('super_admin'))) {
                return;
            }
            if (Schema::hasColumn('shipments', 'branch_id') && $u->effectiveBranchId()) {
                $q->where('branch_id', $u->effectiveBranchId());
            } elseif (Schema::hasColumn('shipments', 'depot_id') && $u->depot_id) {
                $q->where('depot_id', $u->depot_id);
            }
        };

        $raw = Shipment::query()
            ->tap($scope)
            ->selectRaw('status, COUNT(*) AS total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $sum = array_sum($raw);
        $labels = [];
        $data   = [];
        $colors = [];

        foreach (self::ORDER as $key) {
            if (! array_key_exists($key, $raw)) {
                continue;
            }
            $count = (int) $raw[$key];
            $pct   = $sum > 0 ? round(($count / $sum) * 100) : 0;

            $labels[] = sprintf('%s — %d (%d%%)', self::LABELS[$key] ?? ucfirst($key), $count, $pct);
            $data[]   = $count;
            $colors[] = self::COLORS[$key] ?? '#94A3B8'; // fallback slate-400
        }

        if (empty($labels)) {
            $labels = ['Tidak ada data'];
            $data   = [1];
            $colors = ['#E5E7EB']; // gray-200
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Total',
                'data'  => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'cutout' => '62%', 
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'boxWidth' => 8,
                    ],
                ],
                'tooltip' => [
                    'displayColors' => false,
                ],
                'title' => [
                    'display' => false,
                ],
            ],
            'layout' => [
                'padding' => [
                    'bottom' => 8,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

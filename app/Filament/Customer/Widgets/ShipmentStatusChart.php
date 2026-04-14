<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Shipment Status Chart Widget
 * 
 * Display pie chart of shipment statuses
 */
class ShipmentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Pengiriman';

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $user = Auth::user();
        $customerId = $user?->customer_id;

        if (!$customerId) {
            return [
                'datasets' => [
                    [
                        'data' => [0],
                        'backgroundColor' => ['#9CA3AF'],
                    ],
                ],
                'labels' => ['Tidak ada data'],
            ];
        }

        // Get shipment counts by status
        $statusCounts = Shipment::where('customer_id', $customerId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        $colorMap = [
            'Draft' => '#9CA3AF',      // Gray
            'Pickup' => '#3B82F6',     // Blue
            'Transit' => '#F59E0B',    // Amber
            'Delivered' => '#10B981',  // Green
            'Hold' => '#EF4444',       // Red
            'Cancelled' => '#DC2626',  // Dark Red
        ];

        foreach ($statusCounts as $status => $count) {
            $labels[] = $this->formatStatusLabel($status);
            $data[] = $count;
            $colors[] = $colorMap[$status] ?? '#9CA3AF';
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * Format status label for display
     */
    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            'Draft' => 'Draft',
            'Pickup' => 'Pickup',
            'Transit' => 'Dalam Perjalanan',
            'Delivered' => 'Terkirim',
            'Hold' => 'Tertahan',
            'Cancelled' => 'Dibatalkan',
            default => $status,
        };
    }

    /**
     * Get chart options
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}

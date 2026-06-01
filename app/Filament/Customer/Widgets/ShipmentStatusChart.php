<?php

namespace App\Filament\Customer\Widgets;

use App\Enums\ShipmentStatus;
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

        foreach ($statusCounts as $statusValue => $count) {
            $enum = ShipmentStatus::tryFrom($statusValue);
            $labels[] = $enum?->label() ?? $statusValue;
            $data[] = $count;
            $colors[] = $this->getHexColor($enum);
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
     * Get hex color for status
     */
    private function getHexColor(?ShipmentStatus $status): string
    {
        return match ($status) {
            ShipmentStatus::Draft => '#9CA3AF',
            ShipmentStatus::Pending => '#F59E0B',
            ShipmentStatus::Pickup => '#3B82F6',
            ShipmentStatus::Transit => '#F59E0B',
            ShipmentStatus::Delivered => '#10B981',
            ShipmentStatus::Hold => '#EF4444',
            ShipmentStatus::Cancelled => '#DC2626',
            null => '#9CA3AF',
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

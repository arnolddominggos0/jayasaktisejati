<?php

namespace App\Filament\Customer\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Customer Stats Overview Widget
 * 
 * Display key metrics for customer dashboard
 */
class CustomerStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $customerId = $user?->customer_id;

        if (!$customerId) {
            return [
                Stat::make('Total Pengiriman', 0)
                    ->description('Tidak ada data customer')
                    ->icon('heroicon-o-truck')
                    ->color('gray'),
            ];
        }

        // Query shipments for this customer
        $query = Shipment::where('customer_id', $customerId);

        $totalShipments = (clone $query)->count();
        $activeShipments = (clone $query)
            ->whereNotIn('status', ShipmentStatus::completed())
            ->count();
        $deliveredThisMonth = (clone $query)
            ->where('status', ShipmentStatus::Delivered->value)
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->count();
        $inTransit = (clone $query)
            ->where('status', ShipmentStatus::Transit->value)
            ->count();

        return [
            Stat::make('Total Pengiriman', $totalShipments)
                ->description('Semua pengiriman Anda')
                ->descriptionIcon('heroicon-o-truck')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Sedang Dikirim', $activeShipments)
                ->description($inTransit > 0 ? "{$inTransit} dalam perjalanan" : 'Tidak ada pengiriman aktif')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Terkirim Bulan Ini', $deliveredThisMonth)
                ->description('Pengiriman sukses bulan ini')
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}

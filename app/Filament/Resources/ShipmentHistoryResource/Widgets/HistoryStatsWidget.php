<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HistoryStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        $base = Shipment::query()->history();

        if ($user && ! $user->isSuperAdmin()) {
            $branchId = $user->effectiveBranchId();
            if ($branchId) {
                $base->where(function ($w) use ($branchId) {
                    $w->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            } elseif ($user->isOfficeAdmin()) {
                $base->whereRaw('1 = 0');
            }
            if ($user->isFieldCoordinator()) {
                $base->where(function ($q) use ($user) {
                    $q->where('coordinator_id', $user->id)->orWhereNull('coordinator_id');
                });
            }
        }

        $total     = (clone $base)->count();
        $delivered = (clone $base)->where('status', ShipmentStatus::Delivered->value)->count();
        $cancelled = (clone $base)->where('status', ShipmentStatus::Cancelled->value)->count();

        $deliveredPct = $total > 0 ? round($delivered / $total * 100, 1) . '% dari total' : '—';
        $cancelledPct = $total > 0 ? round($cancelled / $total * 100, 1) . '% dari total' : '—';

        return [
            Stat::make('Total Arsip', number_format($total))
                ->description('Semua pengiriman selesai')
                ->icon('heroicon-m-archive-box')
                ->color('gray'),

            Stat::make('Terkirim', number_format($delivered))
                ->description($deliveredPct)
                ->icon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Dibatalkan', number_format($cancelled))
                ->description($cancelledPct)
                ->icon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}

<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use App\Models\Shipment;
use App\Models\Armada;
use App\Models\ShipmentTrack;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class KpiOverview extends BaseWidget
{
    protected int|string|array $columnSpan = ['xl' => 3];
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        [$from, $to] = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];

        $scope = function ($q) {
            $u = auth_user();
            if (! $u || (method_exists($u, 'hasRole') && $u->hasRole('super_admin'))) return;

            if (Schema::hasColumn('shipments', 'branch_id') && $u->branch_id) {
                $q->where('branch_id', $u->branch_id);
            } elseif (Schema::hasColumn('shipments', 'depot_id') && $u->depot_id) {
                $q->where('depot_id', $u->depot_id);
            }
        };

        $activeShipments = Cache::remember('kpi_active_shipments_'.md5(json_encode($scope)), 60, function () use ($scope) {
            return Shipment::query()
                ->tap($scope)
                ->whereIn('status', ['pending','pickup','transit'])
                ->count();
        });

        $pendingOps = Cache::remember('kpi_pending_ops_'.md5(json_encode($scope)), 60, function () use ($scope) {
            return Shipment::query()
                ->tap($scope)
                ->whereIn('status', ['pending','pickup'])
                ->count();
        });

        $armadaAktif = class_exists(Armada::class)
            ? Cache::remember('kpi_armada_aktif', 60, fn () => Armada::query()->whereIn('status', ['on_duty','operational'])->count())
            : 0;

        $tracksToday = Cache::remember('kpi_tracks_today_'.now()->format('Ymd'), 60, function () use ($from, $to, $scope) {
            return ShipmentTrack::query()
                ->whereBetween('tracked_at', [$from, $to])
                ->whereHas('shipment', $scope)
                ->count();
        });

        return [
            Stat::make('Total Shipment Aktif', number_format($activeShipments))
                ->description('Status: pending/pickup/transit')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Pending Pickup/Stuffing', number_format($pendingOps))
                ->description('Butuh eksekusi lapangan')
                ->icon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Armada Aktif', number_format($armadaAktif))
                ->description('On duty / operational')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('success'),

            Stat::make('Aktivitas Tracking Hari Ini', number_format($tracksToday))
                ->description('Update lokasi/status per hari')
                ->icon('heroicon-m-bolt')
                ->color('info'),
        ];
    }
}

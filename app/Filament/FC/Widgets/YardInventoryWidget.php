<?php

namespace App\Filament\FC\Widgets;

use App\Models\Depot;
use App\Services\YardInventoryService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Yard Inventory KPI — hari ini, depot aktif.
 *
 * Source of truth: shipment_tracks + unit_inspections + units + shipments.
 * briefing_sessions is NOT used here.
 * Polling: 30 detik.
 */
class YardInventoryWidget extends Widget
{
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan    = 'full';

    protected function getStats(): array
    {
        $depotId = $this->resolveDepotId();
        $today   = now()->toDateString();

        if (! $depotId) {
            return [
                Stat::make('Yard Inventory', '—')
                    ->description('Depot tidak ditemukan. Hubungi admin.')
                    ->color('gray'),
            ];
        }

        $svc  = app(YardInventoryService::class);
        $date = now()->startOfDay();
        $snap = $svc->snapshot($date, $depotId);

        $depot = Depot::select('id', 'name')->find($depotId);
        $desc  = ($depot?->name ?? 'Depot') . ' · ' . now()->translatedFormat('d F Y');

        return [
            Stat::make('Actual Unit Handover', $snap['masuk'] . ' unit')
                ->description($desc)
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('info'),

            Stat::make('Unit Saat Ini di Yard', $snap['dalam'] . ' unit')
                ->description('Masuk − Keluar (tidak pernah negatif)')
                ->descriptionIcon('heroicon-m-cube')
                ->color($snap['dalam'] > 0 ? 'primary' : 'gray'),

            Stat::make('Unit Ready Loading', $snap['siap'] . ' unit')
                ->description('Inspeksi accept / allow_with_remark')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($snap['siap'] > 0 ? 'success' : 'gray'),

            Stat::make('Unit NG', $snap['masalah'] . ' unit')
                ->description('Gate decision: return_to_pdc')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($snap['masalah'] > 0 ? 'danger' : 'gray'),

            Stat::make('Unit Sudah Loading', $snap['keluar'] . ' unit')
                ->description('Non-rack: stuffing · Rack: delivery_to_port')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color($snap['keluar'] > 0 ? 'warning' : 'gray'),
        ];
    }

    private function resolveDepotId(): ?int
    {
        $user = Filament::auth()->user();
        if (! $user) return null;

        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        if (isset($user->scope_unit_type) && $user->scope_unit_type === 'depot' && $user->scope_unit_id) {
            return (int) $user->scope_unit_id;
        }

        $raw = DB::table('depots')->where('coordinator_user_id', $user->id)->value('id');
        return $raw ? (int) $raw : null;
    }
}

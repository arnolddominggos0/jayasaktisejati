<?php

namespace App\Filament\FC\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Services\ShipmentOperationalGateResolver;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class FcKpiStats extends Widget
{
    protected ?string $heading = 'Ringkasan Harian';
    protected static ?string $pollingInterval = '60s';

    protected function getSeaBaseQuery(): Builder
    {
        $u       = Filament::auth()->user();
        $depotId = app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
        $userId  = (int) ($u?->id ?? 0);

        $query = Shipment::query()->where('mode', 'sea');

        return $depotId
            ? ShipmentOperationalGateResolver::scopeForDepot($query, $depotId, $userId)
            : $query->where('coordinator_id', $userId);
    }

    protected function getStats(): array
    {
        $base = $this->getSeaBaseQuery();

        // Aktif = pending, transit, hold only — delivered/cancelled excluded
        $activeStatuses = [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value];

        $aktif      = (clone $base)->whereNotIn('status', $activeStatuses)->count();
        $inProgress = (clone $base)->where('status', ShipmentStatus::Transit->value)->count();
        $onHold     = (clone $base)->where('status', ShipmentStatus::Hold->value)->count();
        $urgent     = (clone $base)
            ->where('priority', 'urgent')
            ->whereNotIn('status', $activeStatuses)
            ->count();

        // ETA Dekat: shipment dengan ETA yang terdefinisi (non-NULL) dan ≤ 24 jam ke depan
        // Shipment tanpa ETA tidak dianggap ETA dekat
        $nearEta = (clone $base)
            ->whereNotIn('status', $activeStatuses)
            ->whereNotNull('eta')
            ->where('eta', '<=', now()->addDay())
            ->count();

        return [
            Stat::make('Urgent', $urgent)
                ->description('Prioritas tinggi')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($urgent > 0 ? 'danger' : 'gray'),

            Stat::make('On Hold', $onHold)
                ->description('Butuh tindak lanjut')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color($onHold > 0 ? 'warning' : 'gray'),

            Stat::make('ETA Dekat', $nearEta)
                ->description('ETA ≤ 24 jam')
                ->descriptionIcon('heroicon-m-clock')
                ->color($nearEta > 0 ? 'warning' : 'success'),

            Stat::make('Berjalan', $inProgress)
                ->description('Dalam perjalanan')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Aktif', $aktif)
                ->description('Pending / transit / hold')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($aktif > 0 ? 'primary' : 'gray'),
        ];
    }

    protected int|string|array $columnSpan = 'full';
}

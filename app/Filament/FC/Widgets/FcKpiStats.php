<?php

namespace App\Filament\FC\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
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
        $u = Filament::auth()->user();
        $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : ($u?->effectiveBranchId() ?? null);
        $depotId = app()->bound('scope.depot_id') ? app('scope.depot_id') : null;

        return Shipment::query()
            ->where('mode', 'sea')
            ->when($branchId, fn (Builder $query) => $query->where(fn ($w) => $w->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->when($depotId, fn (Builder $query) => $query->where(function ($w) use ($depotId, $u) {
                $w->where('assigned_depot_id', $depotId)
                    ->orWhere('coordinator_id', $u?->id);
            }), fn (Builder $query) => $query->where('coordinator_id', $u?->id));
    }

    protected function getStats(): array
    {
        $base = $this->getSeaBaseQuery();

        $assigned = (clone $base)->count();
        $inProgress = (clone $base)->where('status', ShipmentStatus::Transit->value)->count();
        $onHold = (clone $base)->where('status', ShipmentStatus::Hold->value)->count();
        $urgent = (clone $base)
            ->where('priority', 'urgent')
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->count();
        $nearEta = (clone $base)
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->where(function (Builder $q) {
                $q->whereNull('eta')
                    ->orWhere('eta', '<=', now()->addDay());
            })
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

            Stat::make('Ditugaskan', $assigned)
                ->description('Total shipment laut')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('gray'),
        ];
    }

    protected int|string|array $columnSpan = 'full';
}

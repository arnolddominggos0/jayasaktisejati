<?php

namespace App\Filament\FC\Widgets;

use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class FcKpiStats extends Widget
{
    protected ?string $heading = 'Ringkasan Tugas';
    protected static ?string $pollingInterval = '60s';
    protected function getStats(): array
    {
        $u     = Filament::auth()->user();
        $fcCol = (string) config('fc.shipment_fc_column', 'coordinator_id');

        $base = Shipment::query()
            ->when($u?->branch_id, fn(Builder $query) => $query->where(function ($w) use ($u) {
                $w->where('branch_id', $u->branch_id)->orWhereNull('branch_id');
            }))
            ->when($u?->office_id ?? null, fn(Builder $query) => $query->where(function ($w) use ($u) {
                $w->where('origin_office_id', $u->office_id)->orWhere('destination_office_id', $u->office_id)
                    ->orWhereNull('origin_office_id');
            }))
            ->where($fcCol, $u->id);

        $assigned   = (clone $base)->count();
        $inProgress = (clone $base)->whereIn('status', ['pickup', 'loading', 'on_transit'])->count();
        $delivered  = (clone $base)->where('status', 'delivered')->count();

        return [
            Stat::make('Ditugaskan', $assigned)
                ->description('Total pekerjaan')
                ->descriptionIcon('heroicon-m-clipboard-document-check'),

            Stat::make('Berjalan', $inProgress)
                ->description('Pickup / loading / on transit')
                ->descriptionIcon('heroicon-m-truck'),

            Stat::make('Selesai', $delivered)
                ->description('Sudah terkirim')
                ->descriptionIcon('heroicon-m-check-badge'),
        ];
    }

    protected int|string|array $columnSpan = 'full';
}

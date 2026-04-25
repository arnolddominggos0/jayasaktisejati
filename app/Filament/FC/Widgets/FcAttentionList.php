<?php

namespace App\Filament\FC\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class FcAttentionList extends BaseWidget
{
    protected static ?string $heading = 'Butuh Perhatian Hari Ini';
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $u = Filament::auth()->user();
        $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : ($u?->effectiveBranchId() ?? null);
        $depotId = app()->bound('scope.depot_id') ? app('scope.depot_id') : null;

        return $table
            ->query(function () use ($u, $branchId, $depotId): Builder {
                return Shipment::query()
                    ->with([
                        'latestTrack',
                        'originCity:id,name',
                        'destinationCity:id,name',
                    ])
                    ->where('mode', 'sea')
                    ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
                    ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                    ->when($depotId, fn (Builder $q) => $q->where(function ($w) use ($depotId, $u) {
                        $w->where('assigned_depot_id', $depotId)
                            ->orWhere('coordinator_id', $u?->id);
                    }), fn (Builder $q) => $q->where('coordinator_id', $u?->id))
                    ->where(function (Builder $q) {
                        $q->where('priority', 'urgent')
                            ->orWhere('status', ShipmentStatus::Hold->value)
                            ->orWhere(function (Builder $q2) {
                                $q2->whereNotNull('eta')
                                    ->where('eta', '<=', now()->addDay());
                            });
                    })
                    ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 ELSE 1 END")
                    ->orderBy('eta', 'asc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'urgent' ? 'danger' : 'gray')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'urgent' => 'Urgent',
                        'normal' => 'Normal',
                        default => $state ?: '—',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state)
                    ->color(fn ($state) => match ($state?->value ?? (string) $state) {
                        'hold' => 'warning',
                        'transit' => 'info',
                        'pending' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('latest_track_status')
                    ->label('Track Terakhir')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-')
                    ->color(fn ($state) => match ($state?->value ?? '') {
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'hold' => 'warning',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('route_short')
                    ->label('Rute')
                    ->getStateUsing(function ($record) {
                        $o = $record->originCity->name ?? '-';
                        $d = $record->destinationCity->name ?? '-';
                        return "{$o} → {$d}";
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->color(function ($state) {
                        if (! $state) {
                            return 'gray';
                        }
                        return $state->isPast() ? 'danger' : 'warning';
                    }),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada shipment yang butuh perhatian')
            ->emptyStateDescription('Semua shipment dalam kondisi normal.');
    }
}

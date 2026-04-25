<?php

namespace App\Filament\FC\Widgets;

use App\Models\ShipmentTrack;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class FcRecentActivities extends BaseWidget
{
    protected static ?string $heading = 'Aktivitas Tracking Terbaru';
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $u = Filament::auth()->user();
        $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : ($u?->effectiveBranchId() ?? null);
        $depotId = app()->bound('scope.depot_id') ? app('scope.depot_id') : null;

        return $table
            ->query(function () use ($u, $branchId, $depotId): Builder {
                $q = ShipmentTrack::query()
                    ->with([
                        'shipment:id,code,origin_city_id,destination_city_id,mode,assigned_depot_id,coordinator_id,branch_id',
                        'shipment.originCity:id,name',
                        'shipment.destinationCity:id,name',
                        'user:id,name',
                    ])
                    ->whereNotNull('tracked_at')
                    ->latest('tracked_at');

                $q->whereHas('shipment', function (Builder $s) use ($u, $branchId, $depotId) {
                    $s->where('mode', 'sea');

                    if ($branchId) {
                        $s->where('branch_id', $branchId);
                    }

                    if ($depotId) {
                        $s->where(function ($w) use ($depotId, $u) {
                            $w->where('assigned_depot_id', $depotId)
                                ->orWhere('coordinator_id', $u?->id);
                        });
                    } else {
                        $s->where('coordinator_id', $u?->id);
                    }
                });

                return $q;
            })
            ->columns([
                Tables\Columns\TextColumn::make('tracked_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipment.code')
                    ->label('Kode')
                    ->badge()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state)
                    ->color(fn ($state) => match ($state?->value ?? (string) $state) {
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'hold' => 'warning',
                        'pickup', 'handover', 'stuffing', 'delivery_to_port', 'stacking', 'unit_loading' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('route_text')
                    ->label('Rute')
                    ->getStateUsing(function ($record) {
                        $o = $record->shipment->originCity->name ?? null;
                        $d = $record->shipment->destinationCity->name ?? null;
                        return $o && $d ? "{$o} → {$d}" : '—';
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Oleh')
                    ->placeholder('—'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }
}

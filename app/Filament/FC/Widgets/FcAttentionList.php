<?php

namespace App\Filament\FC\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Branch;
use App\Models\Shipment;
use App\Services\ShipmentOperationalGateResolver;
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

    private function getBranchName(): string
    {
        $u = Filament::auth()->user();
        $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : ($u?->effectiveBranchId() ?? null);

        if (! $branchId) {
            return 'branch ini';
        }

        $branch = Branch::find($branchId);

        return $branch?->name ?? 'branch ini';
    }

    private function formatEtaCountdown(?\Illuminate\Support\Carbon $eta): ?string
    {
        if (! $eta) {
            return null;
        }

        $diff = now()->diffInMinutes($eta, false);

        if ($diff <= 0) {
            $ago = now()->diffForHumans($eta, true);
            return "Lewat {$ago}";
        }

        if ($diff < 60) {
            return (int) $diff . ' menit lagi';
        }

        if ($diff < 1440) {
            $hours = (int) ($diff / 60);
            return "{$hours} jam lagi";
        }

        $days = (int) ($diff / 1440);
        return "{$days} hari lagi";
    }

    public function table(Table $table): Table
    {
        $u       = Filament::auth()->user();
        $depotId = app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
        $userId  = (int) ($u?->id ?? 0);

        return $table
            ->query(function () use ($depotId, $userId): Builder {
                $query = Shipment::query()
                    ->with([
                        'latestTrack',
                        'originCity:id,name',
                        'destinationCity:id,name',
                    ])
                    ->where('mode', 'sea')
                    ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value]);

                if ($depotId) {
                    ShipmentOperationalGateResolver::scopeForDepot($query, $depotId, $userId);
                } else {
                    $query->where('coordinator_id', $userId);
                }

                return $query->where(function (Builder $q) {
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
                    ->color('gray')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('severity')
                    ->label('Keparahan')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->priority === 'urgent') {
                            return 'Urgent';
                        }
                        if ($record->status?->value === ShipmentStatus::Hold->value) {
                            return 'Ditahan';
                        }
                        return 'ETA Dekat';
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Urgent' => 'danger',
                        'Ditahan' => 'warning',
                        'ETA Dekat' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'Urgent' => 'heroicon-m-exclamation-triangle',
                        'Ditahan' => 'heroicon-m-pause-circle',
                        'ETA Dekat' => 'heroicon-m-clock',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state)
                    ->color(fn ($state) => match ($state?->value ?? (string) $state) {
                        'hold' => 'warning',
                        'transit' => 'info',
                        'pending' => 'gray',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
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
                        'urgent' => 'danger',
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
                    ->description(fn ($record) => $this->formatEtaCountdown($record->eta))
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
            ->emptyStateDescription(fn () => 'Semua shipment dalam kondisi normal di '.$this->getBranchName().'.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
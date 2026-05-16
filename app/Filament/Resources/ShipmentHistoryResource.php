<?php

namespace App\Filament\Resources;

use App\Enums\{ShipmentMode, ShipmentStatus};
use App\Filament\Resources\ShipmentHistoryResource\Pages\ListShipmentHistories;
use App\Filament\Resources\ShipmentHistoryResource\Pages\ViewShipmentHistory;
use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Actions\{Action, ActionGroup};
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ShipmentHistoryResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup   = 'Riwayat';
    protected static ?string $navigationLabel   = 'Riwayat Pengiriman';
    protected static ?string $modelLabel        = 'Riwayat Pengiriman';
    protected static ?string $pluralModelLabel  = 'Riwayat Pengiriman';
    protected static ?string $navigationIcon    = 'heroicon-m-archive-box';
    protected static ?int    $navigationSort    = 30;
    protected static ?string $slug              = 'shipment-histories';

    public static function shouldRegisterNavigation(): bool
    {
        $u = Filament::auth()->user();
        return $u?->hasAnyRole(['super_admin', 'office_admin', 'field_coordinator']) === true;
    }

    public static function canViewAny(): bool
    {
        $u = Filament::auth()->user();
        return $u?->hasAnyRole(['super_admin', 'office_admin', 'field_coordinator']) ?? false;
    }

    public static function canView($record): bool
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check branch ownership
        if ($user->effectiveBranchId() && $record->branch_id !== null) {
            return (int) $record->branch_id === (int) $user->effectiveBranchId();
        }

        // Field coordinator check
        if ($user->hasRole('field_coordinator')) {
            return $record->coordinator_id === $user->id || $record->coordinator_id === null;
        }

        return true;
    }

    public static function canEdit($record): bool
    {
        // Only super admin can edit historical shipments
        $user = Filament::auth()->user();
        return $user?->hasRole('super_admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        // Only super admin can delete historical shipments
        $user = Filament::auth()->user();
        return $user?->hasRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()
            ->with(['customer', 'receiver', 'originCity', 'destinationCity', 'tracks'])
            ->history();

        $user = Filament::auth()->user();

        if (!$user?->hasRole('super_admin')) {
            if ($user?->effectiveBranchId()) {
                $q->where(function ($w) use ($user) {
                    $w->where('branch_id', $user->effectiveBranchId())->orWhereNull('branch_id');
                });
            }

            if ($user?->hasRole('field_coordinator')) {
                $q->where(function ($qq) use ($user) {
                    $qq->where('coordinator_id', $user->id)->orWhereNull('coordinator_id');
                });
            }
        }

        return $q;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                IconColumn::make('mode')
                    ->label('Moda')
                    ->icon(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'primary' : 'warning')
                    ->tooltip(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'Laut' : 'Darat'),

                TextColumn::make('customer.name')
                    ->label('Pengirim')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('receiver.name')
                    ->label('Penerima')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('route_summary')
                    ->label('Rute')
                    ->html()
                    ->getStateUsing(
                        fn(Shipment $record) =>
                        "<div class='font-medium'>" . ($record->originCity->name ?? '-') . " &rarr; " . ($record->destinationCity->name ?? '-') . "</div>"
                    )
                    ->toggleable(),

                TextColumn::make('kpi_summary')
                    ->label('KPI Manado')
                    ->state(fn(Shipment $r) => $r->kpiManadoSummaryText() ?? '-')
                    ->tooltip('KPI Manado: Normal ≤19 hari, Urgent ≤17 hari')
                    ->wrap(),

                TextColumn::make('kpi_status')
                    ->label('Status KPI')
                    ->badge()
                    ->state(function (Shipment $r) {
                        $ev = $r->evaluateKpiForManado();
                        return $ev['applies'] ? ($ev['badge'] ?? 'Menunggu') : '—';
                    })
                    ->color(function (Shipment $r) {
                        $ev = $r->evaluateKpiForManado();
                        if (!($ev['applies'] ?? false)) return 'gray';
                        return match ($ev['badge'] ?? 'Pending') {
                            'On Time', 'Tepat Waktu' => 'success',
                            'Late', 'Terlambat'    => 'danger',
                            default   => 'gray',
                        };
                    }),

                TextColumn::make('status')
                    ->label('Status Akhir')
                    ->badge()
                    ->tooltip(fn(Shipment $r) => $r->status === ShipmentStatus::Delivered ? 'Terkirim ke penerima (ATA)' : 'Dibatalkan oleh admin/koordinator')
                    ->getStateUsing(fn(Shipment $record) => $record->status?->label() ?? (is_string($record->status) ? $record->status : '-'))
                    ->colors([
                        'success' => ['Terkirim'],
                        'danger'  => ['Dibatalkan'],
                    ])
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->getStateUsing(function (Shipment $record) {
                        if ($record->status === ShipmentStatus::Delivered) {
                            return $record->delivered_at
                                ?? $record->tracks()
                                ->where('status', 'delivered')
                                ->latest('tracked_at')
                                ->value('tracked_at');
                        }

                        if ($record->status === ShipmentStatus::Cancelled) {
                            return $record->cancelled_at
                                ?? $record->tracks()
                                ->where('status', 'cancelled')
                                ->latest('tracked_at')
                                ->value('tracked_at');
                        }

                        return null;
                    })
                    ->placeholder('—')
                    ->dateTime('d M Y H:i')
                    ->badge()
                    ->color(fn(Shipment $record) => $record->status === ShipmentStatus::Delivered ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('eta')
                    ->label('ETA (Terakhir)')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Status Akhir')
                    ->options([
                        ShipmentStatus::Delivered->value => ShipmentStatus::Delivered->label(),
                        ShipmentStatus::Cancelled->value => ShipmentStatus::Cancelled->label(),
                    ])
                    ->native(false),

                SelectFilter::make('mode')
                    ->label('Moda')
                    ->options([
                        ShipmentMode::Sea->value  => 'Laut',
                        ShipmentMode::Land->value => 'Darat',
                    ])
                    ->native(false),

                SelectFilter::make('customer_id')
                    ->label('Pengirim')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('receiver_id')
                    ->label('Penerima')
                    ->relationship('receiver', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('completed_range')
                    ->label('Rentang Selesai')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
                        $to   = isset($data['to'])   ? Carbon::parse($data['to'])->endOfDay()   : null;

                        if (!$from && !$to) return;

                        $q->where(function ($w) use ($from, $to) {
                            if ($from) $w->where('updated_at', '>=', $from);
                            if ($to)   $w->where('updated_at', '<=', $to);
                        });
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)

            ->defaultSort(
                fn($query) =>
                $query->orderByRaw("
                    COALESCE(
                        delivered_at,
                        cancelled_at,
                        (SELECT MAX(tracked_at) FROM shipment_tracks WHERE shipment_id = shipments.id AND status IN ('delivered','cancelled')),
                        updated_at
                    ) DESC
                ")
            )

            ->actions([
                ActionGroup::make([
                    Action::make('detail')
                        ->label('Detail')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->url(fn($record) => static::getUrl('view', ['record' => $record])),

                    Action::make('edit_direct')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn() => auth_user()?->hasRole('super_admin') === true)
                        ->url(fn($record) => ShipmentResource::getUrl('edit', ['record' => $record])),
                ])->label('Aksi'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipmentHistories::route('/'),
            'view'  => ViewShipmentHistory::route('/{record}'),
        ];
    }
}

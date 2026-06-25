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

    protected static ?string $navigationGroup   = 'Pengiriman';
    protected static ?string $navigationLabel   = 'Riwayat Pengiriman';
    protected static ?string $modelLabel        = 'Riwayat Pengiriman';
    protected static ?string $pluralModelLabel  = 'Riwayat Pengiriman';
    protected static ?string $navigationIcon    = 'heroicon-m-archive-box';
    protected static ?int    $navigationSort    = 30;
    protected static ?string $slug              = 'shipment-histories';

    public static function shouldRegisterNavigation(): bool
    {
        // Admin panel only — Office Admin & Super Admin.
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canView($record): bool
    {
        $user = Filament::auth()->user();
        if (! $user) return false;

        if ($user->isSuperAdmin()) return true;

        // office_admin: branch-scoped
        if ($user->effectiveBranchId() && $record->branch_id !== null) {
            return (int) $record->branch_id === (int) $user->effectiveBranchId();
        }

        return $user->isOfficeAdmin();
    }

    public static function canEdit($record): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()
            ->with(['customer', 'receiver', 'originCity', 'destinationCity', 'tracks'])
            ->history();

        $user = Filament::auth()->user();

        if ($user && ! $user->isSuperAdmin()) {
            $branchId = $user->effectiveBranchId();

            if ($branchId) {
                $q->where(function ($w) use ($branchId) {
                    $w->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            } elseif ($user->isOfficeAdmin()) {
                // office_admin without branch is misconfiguration — deny all
                $q->whereRaw('1 = 0');
            }
        }

        return $q;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ── Primary identifier ────────────────────────────────────
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                // ── SPPB / Document number (secondary identifier) ─────────
                TextColumn::make('doc_number')
                    ->label('No. SPPB / Dok')
                    ->placeholder('—')
                    ->searchable()
                    ->copyable()
                    ->extraAttributes(['class' => 'text-xs']),

                TextColumn::make('customer.name')
                    ->label('Pengirim')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                // ── Route ─────────────────────────────────────────────────
                TextColumn::make('route_label')
                    ->label('Rute')
                    ->getStateUsing(fn(Shipment $record) =>
                        ($record->originCity?->name ?? '—') . ' → ' . ($record->destinationCity?->name ?? '—')
                    )
                    ->wrap(),

                // ── Voyage (booking snapshot string) ──────────────────────
                TextColumn::make('voyage_snapshot')
                    ->label('Voyage')
                    ->getStateUsing(fn(Shipment $record) =>
                        data_get($record->getAttributes(), 'voyage') ?? '—'
                    )
                    ->placeholder('—')
                    ->searchable(query: fn($query, $search) =>
                        $query->where('voyage', 'like', "%{$search}%")
                    )
                    ->toggleable(),

                // ── Final status ──────────────────────────────────────────
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn(Shipment $record) =>
                        $record->status?->label() ?? (is_string($record->status) ? $record->status : '-')
                    )
                    ->colors([
                        'success' => ['Terkirim'],
                        'danger'  => ['Dibatalkan'],
                    ])
                    ->sortable(),

                // ── Completion date ───────────────────────────────────────
                TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->getStateUsing(function (Shipment $record) {
                        if ($record->status === ShipmentStatus::Delivered) {
                            return $record->delivered_at
                                ?? $record->tracks()->where('status', 'delivered')->latest('tracked_at')->value('tracked_at');
                        }
                        if ($record->status === ShipmentStatus::Cancelled) {
                            return $record->cancelled_at
                                ?? $record->tracks()->where('status', 'cancelled')->latest('tracked_at')->value('tracked_at');
                        }
                        return null;
                    })
                    ->placeholder('—')
                    ->dateTime('d M Y')
                    ->badge()
                    ->color(fn(Shipment $record) => $record->status === ShipmentStatus::Delivered
                        ? 'success'
                        : 'danger')
                    ->sortable(),

                // ── Toggleable ────────────────────────────────────────────
                TextColumn::make('mode')
                    ->label('Moda')
                    ->formatStateUsing(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'Laut' : 'Darat')
                    ->badge()
                    ->color(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'primary' : 'warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('receiver.name')
                    ->label('Penerima')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->since()
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
                // Primary action — always visible
                Action::make('detail')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn($record) => static::getUrl('view', ['record' => $record])),

                // Secondary actions grouped
                ActionGroup::make([
                    Action::make('print_resi')
                        ->label('Print Resi')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->url(fn($record) => route('shipments.resi', $record) . '?download=1')
                        ->openUrlInNewTab(),

                    Action::make('print_waybill')
                        ->label('Waybill')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn($record) => route('shipments.print.waybill', $record))
                        ->openUrlInNewTab(),

                    Action::make('print_packing')
                        ->label('Packing List')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('gray')
                        ->url(fn($record) => route('shipments.print.packing', $record))
                        ->openUrlInNewTab(),

                    Action::make('correction')
                        ->label('Koreksi Data')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn() => auth_user()?->isSuperAdmin() === true)
                        ->url(fn($record) => ShipmentResource::getUrl('edit', ['record' => $record])),
                ])->icon('heroicon-m-ellipsis-horizontal')->color('gray'),
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

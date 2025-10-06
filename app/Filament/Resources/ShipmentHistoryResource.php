<?php

namespace App\Filament\Resources;

use App\Enums\{ContainerSize, DeliveryScope, RequestType, ServiceType, ShipmentMode, ShipmentStatus};
use App\Filament\Resources\ShipmentHistoryResource\Pages\ListShipmentHistories;
use App\Filament\Resources\ShipmentHistoryResource\Pages\ViewShipmentHistory;
use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn, ViewColumn};
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShipmentHistoryResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup   = 'Pengiriman';
    protected static ?string $navigationLabel   = 'Riwayat Pengiriman';
    protected static ?string $modelLabel        = 'Riwayat Pengiriman';
    protected static ?string $pluralModelLabel  = 'Riwayat Pengiriman';
    protected static ?string $navigationIcon    = 'heroicon-m-archive-box';
    protected static ?int    $navigationSort    = 30;
    protected static ?string $slug = 'shipment-histories';

    public static function shouldRegisterNavigation(): bool
    {
        $u = Filament::auth()->user();
        return $u?->hasAnyRole(['super_admin', 'office_admin', 'field_coordinator']) === true;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()
            ->with(['customer', 'receiver', 'originCity', 'destinationCity', 'latestTrack', 'tracks'])
            ->history();

        $user = Filament::auth()->user();

        if (!$user?->hasRole('super_admin')) {
            if ($user?->branch_id) {
                $q->where(function ($w) use ($user) {
                    $w->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
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

                TextColumn::make('customer.name')->label('Pengirim')->badge()->sortable()->searchable(),
                TextColumn::make('receiver.name')->label('Penerima')->badge()->sortable()->searchable(),

                TextColumn::make('route_summary')
                    ->label('Rute')
                    ->html()
                    ->getStateUsing(fn(Shipment $record) => "<div class='font-medium'>" . ($record->originCity->name ?? '-') . " &rarr; " . ($record->destinationCity->name ?? '-') . "</div>")
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status Akhir')
                    ->badge()
                    ->getStateUsing(fn(Shipment $record) => $record->status?->label() ?? (is_string($record->status) ? $record->status : '-'))
                    ->colors([
                        'success' => ['Terkirim'],
                        'danger'  => ['Dibatalkan'],
                    ])
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->state(fn(Shipment $record) => $record->completed_at)
                    ->dateTime('d M Y H:i')
                    ->badge()
                    ->color(fn(Shipment $record) => $record->status === \App\Enums\ShipmentStatus::Delivered ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('eta')->label('ETA (Terakhir)')->dateTime('d M Y H:i')->placeholder('—')->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
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

                SelectFilter::make('customer_id')->label('Pengirim')->relationship('customer', 'name')->searchable()->preload(),
                SelectFilter::make('receiver_id')->label('Penerima')->relationship('receiver', 'name')->searchable()->preload(),

                Filter::make('completed_range')
                    ->label('Rentang Selesai')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
                        $to   = isset($data['to'])   ? Carbon::parse($data['to'])->endOfDay()   : null;

                        if (!$from && !$to) return;

                        $q->where(function ($w) use ($from, $to) {
                            $w->where(function ($q1) use ($from, $to) {
                                $q1->where('status', ShipmentStatus::Delivered->value);
                                if ($from) $q1->where('updated_at', '>=', $from);
                                if ($to)   $q1->where('updated_at', '<=', $to);
                            })
                                ->orWhere(function ($q2) use ($from, $to) {
                                    $q2->where('status', ShipmentStatus::Cancelled->value);
                                    if ($from) $q2->where(function ($x) use ($from) {
                                        $x->whereNotNull('cancelled_at')->where('cancelled_at', '>=', $from)
                                            ->orWhere(function ($y) use ($from) {
                                                $y->whereNull('cancelled_at')->where('updated_at', '>=', $from);
                                            });
                                    });
                                    if ($to) $q2->where(function ($x) use ($to) {
                                        $x->whereNotNull('cancelled_at')->where('cancelled_at', '<=', $to)
                                            ->orWhere(function ($y) use ($to) {
                                                $y->whereNull('cancelled_at')->where('updated_at', '<=', $to);
                                            });
                                    });
                                });
                        });
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->defaultSort(function ($query) {
                $query->orderByRaw("
        CASE 
            WHEN status = 'cancelled' THEN COALESCE(cancelled_at, updated_at)
            ELSE updated_at
        END DESC
    ");
            })
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
                        ->url(fn($record) => \App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $record])),
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

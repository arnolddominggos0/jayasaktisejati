<?php

namespace App\Filament\FC\Resources;

use App\Enums\{ServiceType, ShipmentMode, ShipmentStatus, ContainerSize, DeliveryScope, CargoType, TrackStatus};
use App\Filament\FC\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\FC\Resources\ShipmentResource\Pages\ListShipments;
use App\Filament\FC\Resources\ShipmentResource\Pages\ViewShipment;
use App\Models\Shipment;
use App\Models\City;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup  = 'Manajemen Pengiriman';
    protected static ?string $navigationLabel  = 'Pengiriman Ditugaskan';
    protected static ?string $modelLabel       = 'Pengiriman';
    protected static ?string $pluralModelLabel = 'Pengiriman Ditugaskan';
    protected static ?string $navigationIcon   = 'heroicon-m-truck';
    protected static ?int    $navigationSort   = 10;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        $u = Filament::auth()->user();
        if (! $u) return $q->whereRaw('1=0');

        $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : ($u->branch_id ?? null);
        $depotId  = app()->bound('scope.depot_id')  ? app('scope.depot_id')  : null;

        $q->where('mode', ShipmentMode::Sea->value);

        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        if (! $depotId) {
            $depotId = Depot::where('coordinator_user_id', $u->id)->value('id');
        }

        if ($depotId) {
            $q->where('assigned_depot_id', $depotId);
        } else {
            return $q->whereRaw('1=0');
        }

        return $q->with([
            'customer:id,name',
            'receiver:id,name',
            'originCity:id,name',
            'destinationCity:id,name',
            'latestTrack',
        ]);
    }

    protected static function trackUpdateForm(): array
    {
        return [
            Forms\Components\Select::make('track_status')
                ->label('Status Lapangan')
                ->options(collect(TrackStatus::order())->mapWithKeys(fn($e) => [$e->value => $e->label()]))
                ->default(fn(?Shipment $record) => $record?->latest_track_status?->value)
                ->helperText(fn(?Shipment $record) => $record?->latest_track_status
                    ? 'Terakhir: ' . $record->latest_track_status->label()
                    : 'Belum ada track.')
                ->searchable()
                ->preload()
                ->required()
                ->native(false)
                ->columnSpan(6),

            Forms\Components\Textarea::make('note')
                ->label('Catatan Lapangan')
                ->rows(4)
                ->maxLength(1000)
                ->columnSpan(12),
        ];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pengiriman')
                ->columns(12)
                ->schema([
                    Forms\Components\Placeholder::make('code')
                        ->label('Kode')
                        ->content(fn(Shipment $record) => $record->code)
                        ->columnSpan(4),

                    Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(fn(Shipment $record) => $record->status?->label() ?? '-')
                        ->columnSpan(4),

                    Forms\Components\Placeholder::make('route')
                        ->label('Rute')
                        ->content(fn(Shipment $record) => ($record->originCity->name ?? '-') . ' → ' . ($record->destinationCity->name ?? '-'))
                        ->columnSpan(12),
                ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color(fn(Shipment $record) => $record->mode === ShipmentMode::Sea ? 'primary' : 'warning')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                IconColumn::make('mode')
                    ->label('Moda')
                    ->icon(fn($state) => ($state instanceof ShipmentMode ? $state->value : (string) $state) === ShipmentMode::Sea->value
                        ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn($state) => ($state instanceof ShipmentMode ? $state->value : (string) $state) === ShipmentMode::Sea->value
                        ? 'primary' : 'warning')
                    ->tooltip(fn($state) => ($state instanceof ShipmentMode ? $state->value : (string) $state) === ShipmentMode::Sea->value
                        ? 'Laut' : 'Darat'),

                TextColumn::make('customer.name')->label('Pengirim')->badge()->searchable()->toggleable(),
                TextColumn::make('receiver.name')->label('Penerima')->badge()->searchable()->toggleable(),

                TextColumn::make('route')
                    ->label('Rute')
                    ->html()
                    ->getStateUsing(function (Shipment $record): string {
                        $o = $record->originCity->name ?? '-';
                        $d = $record->destinationCity->name ?? '-';
                        return "<div class='font-medium'>{$o} &rarr; {$d}</div>";
                    })
                    ->toggleable(),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(fn(Shipment $record) => $record->service_type?->label() ?? (string) $record->service_type ?: '-')
                    ->badge()
                    ->colors([
                        'info'    => [ServiceType::SeaFreight->label()],
                        'warning' => [ServiceType::LandTrucking->label(), ServiceType::CarCarrier->label()],
                    ])
                    ->toggleable(),

                TextColumn::make('service_option')
                    ->label('Opsi')
                    ->formatStateUsing(function (?string $state, Shipment $record) {
                        $label = match ($state) {
                            'fcl' => 'FCL',
                            'lcl' => 'LCL',
                            'truck' => 'Truck',
                            'towing' => 'Towing',
                            'car_carrier' => 'Car Carrier',
                            default => $state ?: '-',
                        };

                        if ($record->mode === ShipmentMode::Sea && $state === 'fcl') {
                            $size = $record->container_size instanceof ContainerSize
                                ? $record->container_size->label()
                                : ContainerSize::tryFrom((string) $record->container_size)?->label();
                            if ($size) {
                                $qty = $record->container_qty ? " × {$record->container_qty}" : '';
                                $label .= " • {$size}{$qty}";
                            }
                        }
                        return $label;
                    })
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'fcl' => 'primary',
                        'lcl' => 'info',
                        'car_carrier', 'towing' => 'warning',
                        'truck' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('delivery_scope')
                    ->label('Cakupan')
                    ->getStateUsing(fn(Shipment $record) => $record->delivery_scope?->label() ?? (string) $record->delivery_scope ?: '-')
                    ->badge()
                    ->colors([
                        'primary' => [DeliveryScope::PortToPort->label()],
                        'success' => [DeliveryScope::DoorToDoor->label()],
                        'warning' => [DeliveryScope::DoorToPort->label(), DeliveryScope::PortToDoor->label()],
                    ])
                    ->toggleable(),

                TextColumn::make('cargo_type')
                    ->label('Muatan')
                    ->getStateUsing(fn(Shipment $record) => $record->cargo_type?->label() ?? (string) $record->cargo_type ?: '-')
                    ->badge()
                    ->colors([
                        'info' => ['General Cargo'],
                        'warning' => ['Unit Kendaraan'],
                    ])
                    ->toggleable(),

                TextColumn::make('packages_total')->label('Koli')->toggleable(),
                TextColumn::make('cbm_total')->label('CBM')->numeric(3)->placeholder('—')->toggleable(),
                TextColumn::make('weight_total')->label('Berat (kg)')->numeric(2)->placeholder('—')->toggleable(),

                TextColumn::make('latest_track_status')
                    ->label('Track Status')
                    ->badge()
                    ->formatStateUsing(fn(Shipment $record) => $record->latest_track_status?->label() ?? '-')
                    ->color(fn(Shipment $record) => match ($record->latest_track_status) {
                        TrackStatus::Delivered => 'success',
                        TrackStatus::Cancelled => 'danger',
                        TrackStatus::Hold      => 'warning',
                        null                              => 'gray',
                        default                           => 'info',
                    })
                    ->sortable(false)
                    ->searchable(false),

                TextColumn::make('latest_tracked_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('eta')->label('ETA')->badge()->dateTime('d M Y H:i')->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('origin_city_id')
                    ->label('Asal')
                    ->options(fn() => City::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('destination_city_id')
                    ->label('Tujuan')
                    ->options(fn() => City::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('updateTrackGeneric')
                    ->label('Update Lapangan')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->form(static::trackUpdateForm())
                    ->action(function (Shipment $record, array $data) {
                        $status = TrackStatus::tryFrom($data['track_status'] ?? '');
                        if (! $status) {
                            \Filament\Notifications\Notification::make()
                                ->title('Status tidak dikenal')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Jika status tidak berubah, jangan buat log baru.
                        if ($record->latest_track_status?->value === $status->value) {
                            $record->latestTrack?->update([
                                'note'       => $data['note'] ?? $record->latestTrack?->note,
                                'tracked_at' => now(),
                            ]);
                        } else {
                            $record->appendTrack($status, $data['note'] ?? null);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Update lapangan tersimpan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([

                        Tables\Actions\Action::make('toPending')
                            ->label('Set Menunggu')
                            ->icon('heroicon-m-clock')
                            ->color('gray')
                            ->visible(fn(Shipment $record) => in_array($record->status?->value ?? (string)$record->status, ['draft', 'hold'], true))
                            ->requiresConfirmation()
                            ->action(function (Shipment $record) {
                                $record->update(['status' => ShipmentStatus::Pending]);
                                \Filament\Notifications\Notification::make()->title('Status di-set ke Menunggu')->success()->send();
                            }),

                        Tables\Actions\Action::make('startPickup')
                            ->label('Mulai Penjemputan')
                            ->icon('heroicon-m-truck')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'pending')
                            ->form([
                                \Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3),
                            ])
                            ->action(function (Shipment $record, array $data) {
                                $record->appendTrack(TrackStatus::Pickup, $data['note'] ?? null);
                            }),

                        Tables\Actions\Action::make('handover')
                            ->label('Handover Depo')
                            ->icon('heroicon-m-building-office')
                            ->color('info')
                            ->visible(fn(Shipment $record) => in_array($record->status?->value ?? (string)$record->status, ['pickup', 'transit'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Handover, $data['note'] ?? null)),

                        Tables\Actions\Action::make('stuffing')
                            ->label('Stuffing & Segel')
                            ->icon('heroicon-m-wrench-screwdriver')
                            ->color('info')
                            ->visible(fn(Shipment $record) => in_array($record->status?->value ?? (string)$record->status, ['pickup', 'transit'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Stuffing, $data['note'] ?? null)),

                        Tables\Actions\Action::make('deliveryToPort')
                            ->label('Antar ke Pelabuhan')
                            ->icon('heroicon-m-arrow-up-right')
                            ->color('info')
                            ->visible(fn(Shipment $record) => in_array($record->status?->value ?? (string)$record->status, ['pickup', 'transit'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::DeliveryToPort, $data['note'] ?? null)),

                        Tables\Actions\Action::make('stacking')
                            ->label('Stacking (Terminal)')
                            ->icon('heroicon-m-rectangle-group')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Stacking, $data['note'] ?? null)),

                        Tables\Actions\Action::make('unitLoading')
                            ->label('Dimuat di Kapal')
                            ->icon('heroicon-m-arrow-up-tray')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::UnitLoading, $data['note'] ?? null)),

                        Tables\Actions\Action::make('onShip')
                            ->label('On Ship')
                            ->icon('heroicon-m-rocket-launch')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::OnShip, $data['note'] ?? null)),

                        Tables\Actions\Action::make('vesselDepart')
                            ->label('Kapal Berangkat')
                            ->icon('heroicon-m-paper-airplane')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::VesselDepart, $data['note'] ?? null)),

                        Tables\Actions\Action::make('vesselArrival')
                            ->label('Kapal Tiba')
                            ->icon('heroicon-m-flag')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::VesselArrival, $data['note'] ?? null)),

                        Tables\Actions\Action::make('unloading')
                            ->label('Pembongkaran')
                            ->icon('heroicon-m-arrow-down-tray')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Unloading, $data['note'] ?? null)),

                        Tables\Actions\Action::make('deliveryToCustomer')
                            ->label('Antar ke Customer')
                            ->icon('heroicon-m-user')
                            ->color('info')
                            ->visible(fn(Shipment $record) => ($record->status?->value ?? (string)$record->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::DeliveryToCustomer, $data['note'] ?? null)),

                        Tables\Actions\Action::make('markDelivered')
                            ->label('Tandai Terkirim')
                            ->icon('heroicon-m-check-badge')
                            ->color('success')
                            ->visible(fn(Shipment $record) => in_array($record->status?->value ?? (string)$record->status, ['transit', 'pickup', 'pending'], true))
                            ->form([
                                \Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3),
                            ])
                            ->action(function (Shipment $record, array $data) {
                                $record->appendTrack(TrackStatus::Delivered, $data['note'] ?? 'Terkirim');
                            }),

                        Tables\Actions\Action::make('hold')
                            ->label('Tahan')
                            ->icon('heroicon-m-pause-circle')
                            ->color('warning')
                            ->visible(fn(Shipment $record) => !in_array($record->status?->value ?? (string)$record->status, ['delivered', 'cancelled'], true))
                            ->form([
                                \Filament\Forms\Components\Textarea::make('note')
                                    ->label('Alasan')
                                    ->rows(3)
                                    ->required(),
                            ])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(TrackStatus::Hold, $data['note'])),

                        Tables\Actions\Action::make('cancel')
                            ->label('Batalkan')
                            ->icon('heroicon-m-x-circle')
                            ->color('danger')
                            ->visible(fn(Shipment $record) => $record->canCancel())
                            ->form([
                                \Filament\Forms\Components\Textarea::make('note')
                                    ->label('Alasan')
                                    ->rows(3)
                                    ->required(),
                            ])
                            ->requiresConfirmation()
                            ->action(function (Shipment $record, array $data) {
                                $record->appendTrack(TrackStatus::Cancelled, $data['note']);
                                $record->forceFill([
                                    'cancelled_at' => now(),
                                    'cancelled_by' => Filament::auth()->id(),
                                ])->save();
                            }),

                    ])->label('Aksi Status')->icon('heroicon-m-cog')->color('secondary'),
                ]),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'view'  => ViewShipment::route('/{record}'),
            'edit'  => EditShipment::route('/{record}/edit'),
        ];
    }
}

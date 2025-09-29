<?php

namespace App\Filament\FC\Resources;

use App\Enums\{ServiceType, ShipmentMode, ShipmentStatus, ContainerSize, DeliveryScope, CargoType};
use App\Models\Shipment;
use App\Models\City;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

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

        return $q
            ->with([
                'customer:id,name',
                'receiver:id,name',
                'originCity:id,name',
                'destinationCity:id,name',
            ])
            ->when($u->branch_id, function (Builder $w) use ($u) {
                $w->where(function ($x) use ($u) {
                    $x->where('branch_id', $u->branch_id)
                        ->orWhereNull('branch_id');
                });
            })
            ->when($u->office_id, function (Builder $w) use ($u) {
                $w->where(function ($x) use ($u) {
                    $x->where('origin_office_id', $u->office_id)
                        ->orWhere('destination_office_id', $u->office_id)
                        ->orWhereNull('origin_office_id')
                        ->orWhereNull('destination_office_id');
                });
            });
    }


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Update Lapangan')
                ->columns(12)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(
                            collect(ShipmentStatus::inProgress())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->merge([
                                    ShipmentStatus::Delivered->value => ShipmentStatus::Delivered->label(),
                                    ShipmentStatus::Pending->value   => ShipmentStatus::Pending->label(),
                                    ShipmentStatus::Cancelled->value => ShipmentStatus::Cancelled->label(),
                                ])
                        )
                        ->required()
                        ->native(false)
                        ->columnSpan(6),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Lapangan')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpan(12),

                    Forms\Components\FileUpload::make('attachments')
                        ->label('Bukti/Foto')
                        ->multiple()
                        ->directory('shipment-proofs')
                        ->downloadable()
                        ->openable()
                        ->maxSize(10 * 1024)
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
                    ->color(fn(Shipment $r) => $r->mode === ShipmentMode::Sea ? 'primary' : 'warning')
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
                    ->getStateUsing(function (Shipment $r): string {
                        $o = $r->originCity->name ?? '-';
                        $d = $r->destinationCity->name ?? '-';
                        return "<div class='font-medium'>{$o} &rarr; {$d}</div>";
                    })
                    ->toggleable(),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(fn(Shipment $r) => $r->service_type?->label() ?? (string) $r->service_type ?: '-')
                    ->badge()
                    ->colors([
                        'info'    => [ServiceType::SeaFreight->label()],
                        'warning' => [ServiceType::LandTrucking->label(), ServiceType::CarCarrier->label()],
                    ])
                    ->toggleable(),

                TextColumn::make('service_option')
                    ->label('Opsi')
                    ->formatStateUsing(function (?string $state, Shipment $r) {
                        $label = match ($state) {
                            'fcl' => 'FCL',
                            'lcl' => 'LCL',
                            'truck' => 'Truck',
                            'towing' => 'Towing',
                            'car_carrier' => 'Car Carrier',
                            default => $state ?: '-',
                        };

                        if ($r->mode === ShipmentMode::Sea && $state === 'fcl') {
                            $size = $r->container_size instanceof ContainerSize
                                ? $r->container_size->label()
                                : \App\Enums\ContainerSize::tryFrom((string) $r->container_size)?->label();
                            if ($size) {
                                $qty = $r->container_qty ? " × {$r->container_qty}" : '';
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
                    ->getStateUsing(fn(Shipment $r) => $r->delivery_scope?->label() ?? (string) $r->delivery_scope ?: '-')
                    ->badge()
                    ->colors([
                        'primary' => [DeliveryScope::PortToPort->label()],
                        'success' => [DeliveryScope::DoorToDoor->label()],
                        'warning' => [DeliveryScope::DoorToPort->label(), DeliveryScope::PortToDoor->label()],
                    ])
                    ->toggleable(),

                TextColumn::make('cargo_type')
                    ->label('Muatan')
                    ->getStateUsing(fn(Shipment $r) => $r->cargo_type?->label() ?? (string) $r->cargo_type ?: '-')
                    ->badge()
                    ->colors([
                        'info' => ['General Cargo'],
                        'warning' => ['Unit Kendaraan'],
                    ])
                    ->toggleable(),

                TextColumn::make('packages_total')->label('Koli')->toggleable(),
                TextColumn::make('cbm_total')->label('CBM')->numeric(3)->placeholder('—')->toggleable(),
                TextColumn::make('weight_total')->label('Berat (kg)')->numeric(2)->placeholder('—')->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn(Shipment $r) => $r->status?->label() ?? (string) $r->status ?: '-')
                    ->colors([
                        'gray'    => ['Draf'],
                        'warning' => ['Menunggu', 'Ditahan'],
                        'info'    => ['Penjemputan', 'Dalam Perjalanan'],
                        'success' => ['Terkirim'],
                        'danger'  => ['Dibatalkan'],
                    ])
                    ->sortable(),

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
                Tables\Actions\EditAction::make()->label('Update'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([

                        Tables\Actions\Action::make('toPending')
                            ->label('Set Menunggu')
                            ->icon('heroicon-m-clock')
                            ->color('gray')
                            ->visible(fn(Shipment $r) => in_array($r->status?->value ?? (string)$r->status, ['draft', 'hold'], true))
                            ->requiresConfirmation()
                            ->action(function (Shipment $record) {
                                $record->update(['status' => \App\Enums\ShipmentStatus::Pending]);
                                \Filament\Notifications\Notification::make()->title('Status di-set ke Menunggu')->success()->send();
                            }),

                        Tables\Actions\Action::make('startPickup')
                            ->label('Mulai Penjemputan')
                            ->icon('heroicon-m-truck')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'pending')
                            ->form([
                                \Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3),
                                \Filament\Forms\Components\FileUpload::make('photos')
                                    ->label('Bukti/Foto')->multiple()->directory('shipment-proofs')->maxSize(10 * 1024),
                            ])
                            ->action(function (Shipment $record, array $data) {
                                $record->appendTrack(\App\Enums\TrackStatus::Pickup, $data['note'] ?? null, $data['photos'] ?? []);
                            }),

                        Tables\Actions\Action::make('handover')
                            ->label('Handover Depo')
                            ->icon('heroicon-m-building-office')
                            ->color('info')
                            ->visible(fn(Shipment $r) => in_array($r->status?->value ?? (string)$r->status, ['pickup', 'transit'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::Handover, $data['note'] ?? null)),

                        Tables\Actions\Action::make('stuffing')
                            ->label('Stuffing & Segel')
                            ->icon('heroicon-m-wrench-screwdriver')
                            ->color('info')
                            ->visible(fn(Shipment $r) => in_array($r->status?->value ?? (string)$r->status, ['pickup', 'transit'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::Stuffing, $data['note'] ?? null)),

                        Tables\Actions\Action::make('deliveryToPort')
                            ->label('Antar ke Pelabuhan')
                            ->icon('heroicon-m-arrow-up-right')
                            ->color('info')
                            ->visible(fn(Shipment $r) => in_array($r->status?->value ?? (string)$r->status, ['pickup', 'transit'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::DeliveryToPort, $data['note'] ?? null)),

                        Tables\Actions\Action::make('stacking')
                            ->label('Stacking (Terminal)')
                            ->icon('heroicon-m-rectangle-group')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::Stacking, $data['note'] ?? null)),

                        Tables\Actions\Action::make('unitLoading')
                            ->label('Dimuat di Kapal')
                            ->icon('heroicon-m-arrow-up-tray')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::UnitLoading, $data['note'] ?? null)),

                        Tables\Actions\Action::make('onShip')
                            ->label('On Ship')
                            ->icon('heroicon-m-rocket-launch')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::OnShip, $data['note'] ?? null)),

                        Tables\Actions\Action::make('vesselDepart')
                            ->label('Kapal Berangkat')
                            ->icon('heroicon-m-paper-airplane')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::VesselDepart, $data['note'] ?? null)),

                        Tables\Actions\Action::make('vesselArrival')
                            ->label('Kapal Tiba')
                            ->icon('heroicon-m-flag')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::VesselArrival, $data['note'] ?? null)),

                        Tables\Actions\Action::make('unloading')
                            ->label('Pembongkaran')
                            ->icon('heroicon-m-arrow-down-tray')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::Unloading, $data['note'] ?? null)),

                        Tables\Actions\Action::make('deliveryToCustomer')
                            ->label('Antar ke Customer')
                            ->icon('heroicon-m-user')
                            ->color('info')
                            ->visible(fn(Shipment $r) => ($r->status?->value ?? (string)$r->status) === 'transit')
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3)])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::DeliveryToCustomer, $data['note'] ?? null)),

                        Tables\Actions\Action::make('markDelivered')
                            ->label('Tandai Terkirim')
                            ->icon('heroicon-m-check-badge')
                            ->color('success')
                            ->visible(fn(Shipment $r) => in_array($r->status?->value ?? (string)$r->status, ['transit', 'pickup', 'pending'], true))
                            ->form([
                                \Filament\Forms\Components\Textarea::make('note')->label('Catatan')->rows(3),
                                \Filament\Forms\Components\FileUpload::make('pod')
                                    ->label('Proof of Delivery')->multiple()->directory('shipment-proofs')->maxSize(10 * 1024)->required(),
                            ])
                            ->action(function (Shipment $record, array $data) {
                                $record->appendTrack(\App\Enums\TrackStatus::Delivered, $data['note'] ?? 'Terkirim', $data['pod'] ?? []);
                            }),

                        // HOLD
                        Tables\Actions\Action::make('hold')
                            ->label('Tahan')
                            ->icon('heroicon-m-pause-circle')
                            ->color('warning')
                            ->visible(fn(Shipment $r) => !in_array($r->status?->value ?? (string)$r->status, ['delivered', 'cancelled'], true))
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Alasan')->rows(3)->required()])
                            ->action(fn(Shipment $record, array $data) => $record->appendTrack(\App\Enums\TrackStatus::Hold, $data['note'])),

                        // CANCEL
                        Tables\Actions\Action::make('cancel')
                            ->label('Batalkan')
                            ->icon('heroicon-m-x-circle')
                            ->color('danger')
                            ->visible(fn(Shipment $r) => $r->canCancel())
                            ->form([\Filament\Forms\Components\Textarea::make('note')->label('Alasan')->rows(3)->required()])
                            ->requiresConfirmation()
                            ->action(function (Shipment $record, array $data) {
                                $record->appendTrack(\App\Enums\TrackStatus::Cancelled, $data['note']);
                                $record->forceFill([
                                    'cancelled_at' => now(),
                                    'cancelled_by' => \Filament\Facades\Filament::auth()->id(),
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
            'index' => \App\Filament\FC\Resources\ShipmentResource\Pages\ListShipments::route('/'),
            'view'  => \App\Filament\FC\Resources\ShipmentResource\Pages\ViewShipment::route('/{record}'),
            'edit'  => \App\Filament\FC\Resources\ShipmentResource\Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}

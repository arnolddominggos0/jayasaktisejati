<?php

namespace App\Filament\Resources;

use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\CargoType;
use App\Enums\RequestType;
use App\Filament\Resources\ShipmentResource\Pages;
use App\Filament\Resources\ShipmentResource\Widgets\RecentShipmentActivities;
use App\Filament\Resources\ShipmentResource\Widgets\ShipmentStats;
use App\Models\Shipment;
use Filament\Tables\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup = 'Pengiriman';
    protected static ?string $navigationLabel = 'Permintaan Pengiriman';
    protected static ?string $modelLabel = 'Permintaan Pengiriman';
    protected static ?string $pluralModelLabel = 'Permintaan Pengiriman';
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        $hasFleetSchedules = Schema::hasTable('fleet_schedules');
        $hasDrivers        = Schema::hasTable('drivers');

        return $form
            ->schema([
                Section::make('A. Data Customer & Dokumen')
                    ->columns(12)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Pengirim *')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),

                        Select::make('receiver_id')
                            ->label('Penerima *')
                            ->relationship('receiver', 'name')
                            ->searchable()->preload()->required()->native(false)->columnSpan(4),


                        TextInput::make('pic_name')
                            ->label('PIC / Contact *')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(4),

                        TextInput::make('pic_phone')
                            ->label('No. Telp/WA *')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(2),

                        Select::make('request_type')
                            ->label('Tipe Permintaan')
                            ->options(
                                collect(RequestType::cases())
                                    ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                            )
                            ->default(RequestType::SPPB_DO->value)
                            ->required()
                            ->live()
                            ->selectablePlaceholder(false)
                            ->columnSpan(2),

                        TextInput::make('doc_number')
                            ->label('No. Dokumen (SPPB/DO)')
                            ->required(fn(Forms\Get $get) => $get('request_type') === RequestType::SPPB_DO->value)
                            ->hidden(fn(Forms\Get $get) => $get('request_type') !== RequestType::SPPB_DO->value)
                            ->placeholder('Input No Dokumen SPPB/DO')
                            ->maxLength(64)
                            ->columnSpan(3),

                        Select::make('priority')
                            ->label('Prioritas')
                            ->options(['normal' => 'Normal', 'urgent' => 'Urgent'])
                            ->default('normal')
                            ->native(false)
                            ->columnSpan(2),

                        DatePicker::make('requested_at')
                            ->label('Tanggal Permintaan *')
                            ->native(false)
                            ->required()
                            ->columnSpan(3),

                        FileUpload::make('attachments')
                            ->label('Lampiran Dokumen')
                            ->multiple()
                            ->downloadable()
                            ->openable()
                            ->maxSize(10 * 1024)
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->columnSpan(7),

                        Textarea::make('notes')
                            ->label('Keterangan tambahan')
                            ->rows(5)
                            ->columnSpan(5),
                    ]),

                Section::make('B. Informasi Rute & Moda')
                    ->columns(12)
                    ->schema([
                        ToggleButtons::make('mode')
                            ->label('Moda Pengiriman *')
                            ->options([
                                ShipmentMode::Sea->value  => 'Laut',
                                ShipmentMode::Land->value => 'Darat (CC/Towing)',
                            ])
                            ->colors([
                                ShipmentMode::Sea->value  => 'primary',
                                ShipmentMode::Land->value => 'warning',
                            ])
                            ->icons([
                                ShipmentMode::Sea->value  => 'heroicon-m-cog-8-tooth',
                                ShipmentMode::Land->value => 'heroicon-m-truck',
                            ])
                            ->inline()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('vessel_name', null);
                                $set('voyage', null);
                                $set('pol', null);
                                $set('pod', null);
                                $set('etd', null);
                                $set('eta', null);
                                $set('vehicle_type', null);
                                $set('vehicle_plate', null);
                                $set('driver_name', null);
                                $set('driver_phone', null);
                                $set('pickup_date', null);
                                $set('service_option', null);
                                $set('schedule_id', null);
                                $set('driver_id', null);
                            })
                            ->columnSpan(12),

                        Select::make('origin_office_id')
                            ->label('Asal (Depo/Kota Asal) *')
                            ->relationship('originOffice', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('cth: Jakarta')
                            ->columnSpan(6),

                        Select::make('destination_office_id')
                            ->label('Tujuan (Depo/Kota Tujuan) *')
                            ->relationship('destinationOffice', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('cth: Manado')
                            ->columnSpan(6),

                        ToggleButtons::make('cargo_type')
                            ->label('Jenis Muatan')
                            ->options([
                                CargoType::Vehicle->value => 'Unit Kendaraan',
                                CargoType::General->value => 'General Cargo',
                            ])
                            ->inline()
                            ->required()
                            ->columnSpan(12),

                        // SEA
                        Group::make()
                            ->columnSpan(12)
                            ->columns(12)
                            ->visible(fn(Forms\Get $get) => $get('mode') === ShipmentMode::Sea->value)
                            ->schema([
                                Placeholder::make('mode_badge_sea')
                                    ->content('Moda: Laut')
                                    ->extraAttributes(['class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700'])
                                    ->columnSpan(12),

                                ToggleButtons::make('service_option')
                                    ->label('Opsi Layanan Laut')
                                    ->options(['fcl' => 'FCL', 'lcl' => 'LCL'])
                                    ->inline()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state !== 'fcl') {
                                            $set('container_size', null);
                                            $set('container_qty', null);
                                        }
                                    })
                                    ->columnSpan(12),

                                Select::make('container_size')
                                    ->label('Ukuran Kontainer')
                                    ->options([
                                        '20'   => "20'",
                                        '40'   => "40'",
                                        '40hc' => "40' HC",
                                        '45hc' => "45' HC",
                                    ])
                                    ->native(false)
                                    ->visible(fn(Forms\Get $get) => $get('service_option') === 'fcl')
                                    ->required(fn(Forms\Get $get) => $get('service_option') === 'fcl')
                                    ->columnSpan(3),

                                TextInput::make('container_qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->visible(fn(Forms\Get $get) => $get('service_option') === 'fcl')
                                    ->required(fn(Forms\Get $get) => $get('service_option') === 'fcl')
                                    ->columnSpan(2),

                                Select::make('schedule_id')
                                    ->label('Jadwal Kapal')
                                    ->searchable()
                                    ->preload()
                                    ->options(function () use ($hasFleetSchedules) {
                                        if (! $hasFleetSchedules) return [];
                                        return DB::table('fleet_schedules')
                                            ->orderByDesc('etd')
                                            ->limit(200)
                                            ->get()
                                            ->mapWithKeys(function ($s) {
                                                $label = sprintf(
                                                    '%s / %s — %s (%s → %s)',
                                                    $s->vessel_name,
                                                    $s->voyage ?? '-',
                                                    $s->etd ? Carbon::parse($s->etd)->format('d M Y') : '-',
                                                    $s->pol ?? '-',
                                                    $s->pod ?? '-',
                                                );
                                                return [$s->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->required(fn() => $hasFleetSchedules)
                                    ->hidden(fn() => ! $hasFleetSchedules)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($hasFleetSchedules) {
                                        if (! $hasFleetSchedules || ! $state) {
                                            foreach (['vessel_name', 'voyage', 'pol', 'pod'] as $f) $set($f, null);
                                            $set('etd', null);
                                            $set('eta', null);
                                            return;
                                        }
                                        $s = DB::table('fleet_schedules')->where('id', $state)->first();
                                        foreach (['vessel_name', 'voyage', 'pol', 'pod'] as $f) $set($f, $s?->$f);
                                        $set('etd', $s?->etd ? \Illuminate\Support\Carbon::parse($s->etd)->toDateTimeString() : null);
                                        $set('eta', $s?->eta ? \Illuminate\Support\Carbon::parse($s->eta)->toDateTimeString() : null);
                                    })
                                    ->columnSpan(12),

                                // Snapshot fields – readonly kalau dropdown aktif, editable kalau manual
                                TextInput::make('vessel_name')->label('Nama Kapal')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                TextInput::make('voyage')->label('Voyage/Trip')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                DatePicker::make('etd')->label('ETD')
                                    ->native(false)->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                DatePicker::make('eta')->label('ETA')
                                    ->native(false)->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                TextInput::make('pol')->label('POL — Pelabuhan Muat')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                TextInput::make('pod')->label('POD — Pelabuhan Bongkar')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),
                            ]),
                            
                        // LAND
                        Group::make()
                            ->columnSpan(12)
                            ->columns(12)
                            ->visible(fn(Forms\Get $get) => $get('mode') === ShipmentMode::Land->value)
                            ->schema([
                                Placeholder::make('mode_badge_land')
                                    ->content('Moda: Darat (CC/Towing)')
                                    ->extraAttributes(['class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-amber-100 text-amber-700'])
                                    ->columnSpan(12),

                                Select::make('vehicle_type')
                                    ->label('Jenis Armada')
                                    ->options([
                                        'car_carrier' => 'Car Carrier (CC)',
                                        'towing'      => 'Towing',
                                        'truck'       => 'Truck',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn(Forms\Set $set, $state) =>
                                        $set('service_option', match ($state) {
                                            'car_carrier' => 'car_carrier',
                                            'towing'      => 'towing',
                                            default       => 'truck'
                                        })
                                    )
                                    ->columnSpan(4),

                                TextInput::make('vehicle_plate')->label('No. Polisi Armada')->maxLength(20)->columnSpan(4),
                                DatePicker::make('pickup_date')->label('Tanggal Pickup (estimasi)')->native(false)->columnSpan(4),

                                Select::make('driver_id')
                                    ->label('Nama Supir')
                                    ->options(function () use ($hasDrivers) {
                                        if (! $hasDrivers) return [];
                                        return DB::table('drivers')->orderBy('name')
                                            ->pluck('name', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($hasDrivers) {
                                        if (! $hasDrivers || ! $state) return;
                                        $d = DB::table('drivers')->where('id', $state)->first();
                                        if ($d) {
                                            $set('driver_name',  $d->name ?? null);
                                            $set('driver_phone', $d->phone ?? null);
                                        }
                                    })
                                    ->hidden(fn() => ! $hasDrivers)
                                    ->columnSpan(6),

                                // snapshot supir (manual fallback / tetap tampil)
                                TextInput::make('driver_name')->label('Nama Supir (snapshot)')
                                    ->placeholder($hasDrivers ? 'Otomatis saat pilih supir' : 'Isi manual')
                                    ->columnSpan(3),

                                TextInput::make('driver_phone')->label('HP Supir (snapshot)')
                                    ->tel()
                                    ->placeholder($hasDrivers ? 'Otomatis saat pilih supir' : 'Isi manual')
                                    ->columnSpan(3),
                            ]),
                    ]),

                Hidden::make('service_type')->dehydrated(),
                Hidden::make('route_summary')->dehydrated(),

                Section::make('C. Konfirmasi')
                    ->columns(12)
                    ->schema([
                        Checkbox::make('confirm_is_true')
                            ->label('Data sudah benar & sesuai dokumen.')
                            ->accepted()
                            ->columnSpan(12),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Pengirim')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receiver.name')
                    ->label('Penerima')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('route')
                    ->label('Rute')
                    ->html()
                    ->getStateUsing(function (Shipment $r): string {
                        $oCity = $r->originOffice->city ?? '-';
                        $dCity = $r->destinationOffice->city ?? '-';
                        $oName = $r->originOffice->name ?? null;
                        $dName = $r->destinationOffice->name ?? null;

                        $line1 = "<div class='font-medium'>{$oCity} &rarr; {$dCity}</div>";
                        $sub   = array_filter([$oName ? "Asal: {$oName}" : null, $dName ? "Tujuan: {$dName}" : null]);
                        $line2 = $sub ? "<div class='text-xs opacity-70'>" . implode(' • ', $sub) . "</div>" : "";

                        return $line1 . $line2;
                    })
                    ->toggleable(),

                TextColumn::make('parties')
                    ->label('Pengirim → Penerima')
                    ->html()
                    ->getStateUsing(function (\App\Models\Shipment $r): string {
                        $sender   = $r->customer->name ?? '—';
                        $receiver = $r->receiver?->name ?? '—';
                        $oCity    = $r->originOffice->city ?? '-';
                        $dCity    = $r->destinationOffice->city ?? '-';
                        return "<div class='font-medium'>{$sender} &rarr; {$receiver}</div>"
                            . "<div class='text-xs opacity-70'>{$oCity} &rarr; {$dCity}</div>";
                    })
                    ->toggleable(),

                TextColumn::make('originOffice.name')
                    ->label('Asal')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('destinationOffice.name')
                    ->label('Tujuan')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(
                        fn($record) =>
                        $record->service_type instanceof ServiceType
                            ? $record->service_type->value
                            : (string) $record->service_type
                    )
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        ServiceType::SeaFreight->value   => 'Sea Freight',
                        ServiceType::LandTrucking->value => 'Trucking',
                        ServiceType::CarCarrier->value   => 'Car Carrier / Towing',
                        default => $state,
                    })
                    ->badge()
                    ->colors([
                        'info'    => [ServiceType::SeaFreight->value],
                        'warning' => [ServiceType::LandTrucking->value, ServiceType::CarCarrier->value],
                    ])
                    ->sortable(),

                TextColumn::make('service_option')
                    ->label('Opsi')
                    ->formatStateUsing(function (?string $state, Shipment $r) {
                        $label = match ($state) {
                            'fcl'         => 'FCL',
                            'lcl'         => 'LCL',
                            'truck'       => 'Truck',
                            'towing'      => 'Towing',
                            'car_carrier' => 'Car Carrier',
                            default       => $state ?: '-',
                        };

                        if ($r->mode === ShipmentMode::Sea && $state === 'fcl') {
                            $size = match (strtolower((string)$r->container_size)) {
                                '20'   => "20'",
                                '40'   => "40'",
                                '40hc' => "40' HC",
                                '45hc' => "45' HC",
                                default => null,
                            };
                            if ($size) {
                                $qty = $r->container_qty ? " × {$r->container_qty}" : '';
                                $label .= " • {$size}{$qty}";
                            }
                        }
                        return $label;
                    })
                    ->badge()
                    ->toggleable(),

                TextColumn::make('cargo_type')
                    ->label('Muatan')
                    ->getStateUsing(
                        fn($record) =>
                        $record->cargo_type instanceof CargoType
                            ? $record->cargo_type->value
                            : (string) $record->cargo_type
                    )
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        CargoType::Vehicle->value => 'Unit Kendaraan',
                        CargoType::General->value => 'General Cargo',
                        default => $state ?: '-',
                    })
                    ->badge()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        $record->status instanceof ShipmentStatus
                            ? $record->status->value
                            : (string) $record->status
                    )
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'draft'     => 'Draft',
                        'pending'   => 'Pending',
                        'pickup'    => 'Pickup',
                        'transit'   => 'Transit',
                        'delivered' => 'Delivered',
                        'hold'      => 'Hold',
                        'cancelled' => 'Cancelled',
                        default     => $state ? ucfirst($state) : '-',
                    })
                    ->colors([
                        'gray'    => ['draft'],
                        'warning' => ['pending', 'hold'],
                        'info'    => ['pickup', 'transit'],
                        'success' => ['delivered'],
                        'danger'  => ['cancelled'],
                    ])
                    ->sortable(),

                TextColumn::make('etd')->label('ETD')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i')->sortable(),

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
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'pending'   => 'Pending',
                        'pickup'    => 'Pickup',
                        'transit'   => 'Transit',
                        'delivered' => 'Delivered',
                        'hold'      => 'Hold',
                        'cancelled' => 'Cancelled',
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

                Filter::make('in_progress')
                    ->label('In Progress')
                    ->query(fn(Builder $q) => $q->whereIn('status', ['pending', 'pickup', 'transit']))
                    ->toggle(),

                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        ServiceType::SeaFreight->value   => 'Sea Freight',
                        ServiceType::LandTrucking->value => 'Trucking Darat',
                        ServiceType::CarCarrier->value   => 'Car Carrier / Towing',
                    ])
                    ->native(false),

                SelectFilter::make('origin_office_id')
                    ->label('Kantor Asal')
                    ->relationship('originOffice', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('destination_office_id')
                    ->label('Kantor Tujuan')
                    ->relationship('destinationOffice', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->defaultSort('updated_at', 'desc')
            ->actions([
                EditAction::make()->label('Edit'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            ShipmentStats::class,
            RecentShipmentActivities::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit'   => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}

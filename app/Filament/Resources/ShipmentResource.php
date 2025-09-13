<?php

namespace App\Filament\Resources;

use App\Enums\{ServiceType, ShipmentMode, ShipmentStatus, CargoType, RequestType, ContainerSize, DeliveryScope};
use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Filament\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Components\{Checkbox, DatePicker, FileUpload, Grid, Group, Hidden, Placeholder, Repeater, Section, Select, Textarea, TextInput, ToggleButtons, ViewField};
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\{BulkAction, DeleteBulkAction, EditAction};
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup = 'Pengiriman';
    protected static ?string $navigationLabel = 'Permintaan Pengiriman';
    protected static ?string $modelLabel = 'Permintaan Pengiriman';
    protected static ?string $pluralModelLabel = 'Permintaan Pengiriman';
    protected static ?string $navigationIcon = 'heroicon-m-archive-box';

    public static function form(Form $form): Form
    {
        $hasFleetSchedules = Schema::hasTable('fleet_schedules');
        $hasDrivers        = Schema::hasTable('drivers');

        $recalcLclTotals = function (Get $get, Set $set) {
            $rows = $get('lcl_items') ?? [];
            $sumCbm = 0.0;
            $sumPkg = 0;
            $sumItemKg = 0.0;

            foreach ($rows as $r) {
                $qty = (int)($r['qty'] ?? 0);
                $p = (float)($r['length_cm'] ?? 0);
                $l = (float)($r['width_cm'] ?? 0);
                $t = (float)($r['height_cm'] ?? 0);
                $w = (float)($r['weight_kg'] ?? 0);

                $sumCbm += ($p * $l * $t * $qty) / 1_000_000;
                $sumPkg += $qty;
                $sumItemKg += ($w * $qty);
            }

            $set('cbm_total', round($sumCbm, 3, PHP_ROUND_HALF_UP));
            $set('packages_total', $sumPkg);

            $override = trim((string)($get('weight_total_input') ?? ''));
            if ($override !== '' && is_numeric($override)) {
                $set('weight_total', round((float)$override, 2, PHP_ROUND_HALF_UP));
            } else {
                $set('weight_total', $sumItemKg > 0 ? round($sumItemKg, 2, PHP_ROUND_HALF_UP) : null);
            }
        };

        return $form
            ->schema([
                Section::make('A. Data Customer & Dokumen')
                    ->columns(12)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Pengirim *')
                            ->placeholder('Pilih Customer Pengirim')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),

                        Select::make('receiver_id')
                            ->label('Penerima *')
                            ->placeholder('Pilih Customer Penerima')
                            ->relationship('receiver', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->columnSpan(4),

                        TextInput::make('pic_name')
                            ->label('PIC / Kontak *')
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
                            ->options(collect(RequestType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                            ->default(RequestType::SPPB_DO->value)
                            ->required()
                            ->live()
                            ->selectablePlaceholder(false)
                            ->columnSpan(2),

                        TextInput::make('doc_number')
                            ->label('No. Dokumen (SPPB/DO)')
                            ->required(fn(Get $get) => $get('request_type') === RequestType::SPPB_DO->value)
                            ->hidden(fn(Get $get) => $get('request_type') !== RequestType::SPPB_DO->value)
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
                                ShipmentMode::Land->value => 'Darat',
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
                            ->afterStateUpdated(function (Set $set) {
                                foreach (
                                    [
                                        'vessel_name',
                                        'voyage',
                                        'pol',
                                        'pod',
                                        'etd',
                                        'eta',
                                        'vehicle_type',
                                        'vehicle_plate',
                                        'driver_name',
                                        'driver_phone',
                                        'pickup_date',
                                        'service_option',
                                        'schedule_id',
                                        'driver_id',
                                        'lcl_items',
                                        'cbm_total',
                                        'packages_total',
                                        'weight_total',
                                        'weight_total_input',
                                        'container_size',
                                        'container_qty',
                                        'container_size_vehicle',
                                        'container_qty_vehicle',
                                    ] as $f
                                ) $set($f, null);
                            })
                            ->columnSpan(12),

                        Select::make('origin_city_id')
                            ->label('Asal (Kota Asal) *')
                            ->placeholder('Pilih Kota Asal')
                            ->relationship('originCity', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('cth: Jakarta')
                            ->columnSpan(6),

                        Select::make('destination_city_id')
                            ->label('Tujuan (Kota Tujuan) *')
                            ->placeholder('Pilih Kota Tujuan')
                            ->relationship('destinationCity', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('cth: Manado')
                            ->columnSpan(6),

                        ToggleButtons::make('cargo_type')
                            ->label('Jenis Muatan')
                            ->options([
                                CargoType::Vehicle->value => CargoType::Vehicle->label(),
                                CargoType::General->value => CargoType::General->label(),
                            ])
                            ->inline()
                            ->required()
                            ->columnSpan(12),

                        // === LAUT ===
                        Group::make()
                            ->columnSpan(12)
                            ->columns(12)
                            ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value)
                            ->schema([
                                ViewField::make('mode_badge_sea')
                                    ->view('filament.forms.fields.mode-badge-sea')
                                    ->columnSpan(12),

                                ToggleButtons::make('service_option')
                                    ->label('Opsi Layanan Laut')
                                    ->options(['fcl' => 'FCL', 'lcl' => 'LCL'])
                                    ->inline()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state === 'fcl') {
                                            foreach (['lcl_items', 'cbm_total', 'packages_total', 'weight_total', 'weight_total_input'] as $f) {
                                                $set($f, null);
                                            }
                                        } else {
                                            foreach (['container_size', 'container_qty', 'container_size_vehicle', 'container_qty_vehicle'] as $f) {
                                                $set($f, null);
                                            }
                                        }
                                    })
                                    ->columnSpan(12),

                                ToggleButtons::make('delivery_scope')
                                    ->label('Cakupan Layanan')
                                    ->options([
                                        DeliveryScope::PortToPort->value => DeliveryScope::PortToPort->label(),
                                        DeliveryScope::DoorToDoor->value => DeliveryScope::DoorToDoor->label(),
                                        DeliveryScope::DoorToPort->value => DeliveryScope::DoorToPort->label(),
                                        DeliveryScope::PortToDoor->value => DeliveryScope::PortToDoor->label(),
                                    ])
                                    ->inline()
                                    ->required()
                                    ->columnSpan(12),

                                Select::make('container_size')
                                    ->label("Ukuran Kontainer (FCL • General)")
                                    ->options(ContainerSize::options())
                                    ->native(false)
                                    ->searchable()
                                    ->visible(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->required(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->columnSpan(4),

                                TextInput::make('container_qty')
                                    ->label('Jumlah Kontainer (FCL • General)')
                                    ->numeric()->minValue(1)->default(1)
                                    ->visible(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->required(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->columnSpan(4),


                                Repeater::make('lcl_items')
                                    ->label('Rincian Volume (LCL • General Cargo)')
                                    ->visible(fn(Get $get) => $get('service_option') === 'lcl' && $get('cargo_type') === CargoType::General->value)
                                    ->columns(12)
                                    ->schema([
                                        TextInput::make('description')->label('Deskripsi')->maxLength(120)->columnSpan(3),
                                        TextInput::make('length_cm')->label('P (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('width_cm')->label('L (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('height_cm')->label('T (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('qty')->label('Koli')->numeric()->minValue(1)->default(1)->live(onBlur: true)->columnSpan(1),
                                        TextInput::make('weight_kg')->label('Berat/pcs (kg)')->numeric()->minValue(0)->live(onBlur: true)->columnSpan(2),

                                        TextInput::make('cbm_item')->label('CBM')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function (Get $get, Set $set) {
                                                $p = (float)($get('length_cm') ?? 0);
                                                $l = (float)($get('width_cm') ?? 0);
                                                $t = (float)($get('height_cm') ?? 0);
                                                $q = (int)  ($get('qty') ?? 0);
                                                $cbm = ($p * $l * $t * $q) / 1_000_000;
                                                $set('cbm_item', $cbm > 0 ? number_format(round($cbm, 3, PHP_ROUND_HALF_UP), 3, '.', '') : null);
                                            })
                                            ->columnSpan(2),
                                    ])
                                    ->afterStateUpdated(function (Get $get, Set $set) use ($recalcLclTotals) {
                                        $rows = $get('lcl_items') ?? [];
                                        foreach ($rows as $i => $row) {
                                            $p = (float)($row['length_cm'] ?? 0);
                                            $l = (float)($row['width_cm'] ?? 0);
                                            $t = (float)($row['height_cm'] ?? 0);
                                            $q = (int)  ($row['qty'] ?? 0);
                                            $cbm = ($p * $l * $t * $q) / 1_000_000;
                                            $rows[$i]['cbm_item'] = $cbm > 0 ? number_format(round($cbm, 3, PHP_ROUND_HALF_UP), 3, '.', '') : null;
                                        }
                                        $set('lcl_items', $rows);
                                        $recalcLclTotals($get, $set);
                                    })
                                    ->addActionLabel('Tambah Item')
                                    ->columnSpan(12),

                                Section::make('Detail LCL (General)')
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'lcl'
                                            && $get('cargo_type') === \App\Enums\CargoType::General->value
                                    )
                                    ->columns(12)
                                    ->schema([

                                        TextInput::make('weight_total_input')
                                            ->label('Total Berat (opsional)')
                                            ->suffix('kg')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('Total (kg)')
                                            ->dehydrated(false)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) use ($recalcLclTotals) {
                                                $recalcLclTotals($get, $set);
                                            })
                                            ->columnSpan(6),

                                        Grid::make(12)
                                            ->columnSpanFull()
                                            ->schema([
                                                Placeholder::make('sum_packages')
                                                    ->label('Total Koli')
                                                    ->content(fn(Get $get) => (string) ($get('packages_total') ?? 0))
                                                    ->columnSpan(4),

                                                Placeholder::make('sum_cbm')
                                                    ->label('Total CBM')
                                                    ->content(fn(Get $get) => number_format((float) ($get('cbm_total') ?? 0), 3, '.', ''))
                                                    ->columnSpan(4),

                                                Placeholder::make('sum_weight')
                                                    ->label('Total Berat (kg)')
                                                    ->content(function (Get $get) {
                                                        $w = $get('weight_total');
                                                        return is_null($w) ? '—' : number_format((float) $w, 2, '.', '');
                                                    })
                                                    ->columnSpan(4),
                                            ]),

                                        Hidden::make('cbm_total')->dehydrated(),
                                        Hidden::make('packages_total')->dehydrated(),
                                        Hidden::make('weight_total')->dehydrated(),
                                    ]),

                                Repeater::make('units')
                                    ->label('Unit Kendaraan (Laut)')
                                    ->visible(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->columns(12)
                                    ->schema([
                                        TextInput::make('model_no')->label('Model No.')->maxLength(60)->columnSpan(3),
                                        TextInput::make('reg_no')->label('No. Polisi / Reg')->maxLength(30)->columnSpan(2),
                                        TextInput::make('chassis_no')->label('Rangka No.')->maxLength(60)->columnSpan(2),
                                        TextInput::make('engine_no')->label('Mesin No.')->maxLength(60)->columnSpan(2),
                                        TextInput::make('color')->label('Warna')->maxLength(30)->columnSpan(1),
                                        TextInput::make('do_number')->label('No. DO')->maxLength(60)->columnSpan(2),
                                        Forms\Components\Checkbox::make('is_rack')->label('Rack')->inline(false)->columnSpan(2),
                                        TextInput::make('qty')->label('Qty')->numeric()->minValue(1)->default(1)->columnSpan(1),
                                        TextInput::make('notes')->label('Ket')->maxLength(120)->columnSpan(5),
                                    ])
                                    ->addActionLabel('Tambah Unit')
                                    ->columnSpan(12),

                                Select::make('schedule_id')
                                    ->label('Jadwal Kapal')
                                    ->searchable()
                                    ->preload()
                                    ->options(function () use ($hasFleetSchedules) {
                                        if (!$hasFleetSchedules) return [];
                                        return DB::table('fleet_schedules')->orderByDesc('etd')->limit(200)
                                            ->get()
                                            ->mapWithKeys(function ($state) {
                                                $label = sprintf(
                                                    '%state / %state — %state (%state → %state)',
                                                    $state->vessel_name,
                                                    $state->voyage ?? '-',
                                                    $state->etd ? Carbon::parse($state->etd)->format('d M Y') : '-',
                                                    $state->pol ?? '-',
                                                    $state->pod ?? '-',
                                                );
                                                return [$state->id => $label];
                                            })->toArray();
                                    })
                                    ->required(fn() => $hasFleetSchedules)
                                    ->hidden(fn() => !$hasFleetSchedules)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) use ($hasFleetSchedules) {
                                        if (!$hasFleetSchedules || !$state) {
                                            foreach (['vessel_name', 'voyage', 'pol', 'pod'] as $f) $set($f, null);
                                            $set('etd', null);
                                            $set('eta', null);
                                            return;
                                        }
                                        $state = DB::table('fleet_schedules')->where('id', $state)->first();
                                        foreach (['vessel_name', 'voyage', 'pol', 'pod'] as $f) $set($f, $state?->$f);
                                        $set('etd', $state?->etd ? Carbon::parse($state->etd)->toDateTimeString() : null);
                                        $set('eta', $state?->eta ? Carbon::parse($state->eta)->toDateTimeString() : null);
                                    })
                                    ->columnSpan(12),

                                TextInput::make('vessel_name')->label('Nama Kapal')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                TextInput::make('voyage')->label('Voyage/Trip')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                DatePicker::make('etd')->label('ETD')->native(false)->disabled(fn() => $hasFleetSchedules)->dehydrated()->columnSpan(4),
                                DatePicker::make('eta')->label('ETA')->native(false)->disabled(fn() => $hasFleetSchedules)->dehydrated()->columnSpan(4),

                                TextInput::make('pol')->label('POL — Pelabuhan Muat')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),

                                TextInput::make('pod')->label('POD — Pelabuhan Bongkar')
                                    ->disabled(fn() => $hasFleetSchedules)->dehydrated()
                                    ->columnSpan(4),
                            ]),

                        // === DARAT ===
                        Group::make()
                            ->columnSpan(12)
                            ->columns(12)
                            ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Land->value)
                            ->schema([
                                ViewField::make('mode_badge_land')->view('filament.forms.fields.mode-badge-land')->columnSpan(12),

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
                                        fn(Set $set, $state) =>
                                        $set('service_option', match ($state) {
                                            'car_carrier' => 'car_carrier',
                                            'towing'      => 'towing',
                                            default       => 'truck'
                                        })
                                    )
                                    ->columnSpan(4),

                                Select::make('driver_id')
                                    ->label('Pilih Sopir')
                                    ->visible(fn() => $hasDrivers)
                                    ->options(function () use ($hasDrivers) {
                                        if (!$hasDrivers) return [];
                                        return DB::table('drivers')->orderBy('name')->limit(200)
                                            ->get()
                                            ->mapWithKeys(fn($d) => [$d->id => "{$d->name} • {$d->phone}"])
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) use ($hasDrivers) {
                                        if (!$hasDrivers || !$state) {
                                            $set('driver_name', null);
                                            $set('driver_phone', null);
                                            return;
                                        }
                                        $d = DB::table('drivers')->where('id', $state)->first();
                                        $set('driver_name', $d?->name);
                                        $set('driver_phone', $d?->phone);
                                    })
                                    ->columnSpan(4),

                                TextInput::make('vehicle_plate')->label('No. Polisi Armada')->maxLength(20)->columnSpan(4),
                                DatePicker::make('pickup_date')
                                    ->label('Tanggal Pickup (estimasi)')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->columnSpan(4),
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
                            ->required()
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
                    ->color(fn(Shipment $r) => $r->mode === ShipmentMode::Sea ? 'primary' : 'warning')
                    ->extraAttributes(['class' => 'font-mono'])
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                IconColumn::make('mode')
                    ->label('Moda')
                    ->icon(function ($state) {
                        $val = $state instanceof ShipmentMode ? $state->value : (string) $state;
                        return $val === ShipmentMode::Sea->value ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck';
                    })
                    ->color(function ($state) {
                        $val = $state instanceof ShipmentMode ? $state->value : (string) $state;
                        return $val === ShipmentMode::Sea->value ? 'primary' : 'warning';
                    })
                    ->tooltip(function ($state) {
                        $val = $state instanceof ShipmentMode ? $state->value : (string) $state;
                        return $val === ShipmentMode::Sea->value ? 'Laut' : 'Darat';
                    }),

                TextColumn::make('customer.name')->label('Pengirim')->badge()->searchable()->sortable(),
                TextColumn::make('receiver.name')->label('Penerima')->badge()->searchable()->sortable(),

                TextColumn::make('route')
                    ->label('Rute')
                    ->html()
                    ->getStateUsing(function (Shipment $record): string {
                        $oCity = $record->originCity->name ?? '-';
                        $dCity = $record->destinationCity->name ?? '-';
                        $line1 = "<div class='font-medium'>{$oCity} &rarr; {$dCity}</div>";
                        return $line1;
                    })
                    ->toggleable(),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(fn(Shipment $r) => $r->service_type?->label() ?? (is_string($r->service_type) ? $r->service_type : '-'))
                    ->badge()
                    ->colors([
                        'info'    => [ServiceType::SeaFreight->label()],
                        'warning' => [ServiceType::LandTrucking->label(), ServiceType::CarCarrier->label()],
                    ])
                    ->sortable(),

                TextColumn::make('request_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $val = $state instanceof RequestType ? $state->value : (string) $state;
                        return match ($val) {
                            RequestType::SPPB_DO->value => 'SPPB/DO',
                            RequestType::WA_TELP->value => 'WA/Telp',
                            RequestType::WALK_IN->value => 'Walk-in',
                            default => '-',
                        };
                    })
                    ->color(function ($state) {
                        $val = $state instanceof RequestType ? $state->value : (string) $state;
                        return match ($val) {
                            RequestType::SPPB_DO->value => 'primary',
                            RequestType::WA_TELP->value => 'info',
                            RequestType::WALK_IN->value => 'success',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('service_option')
                    ->label('Opsi')
                    ->formatStateUsing(function (?string $state, Shipment $record) {
                        $label = match ($state) {
                            'fcl'         => 'FCL',
                            'lcl'         => 'LCL',
                            'truck'       => 'Truck',
                            'towing'      => 'Towing',
                            'car_carrier' => 'Car Carrier',
                            default       => $state ?: '-',
                        };

                        if ($record->mode === ShipmentMode::Sea && $state === 'fcl') {
                            $sizeLabel = null;

                            if ($record->container_size instanceof ContainerSize) {
                                $sizeLabel = $record->container_size->label();
                            } else {
                                $sizeLabel = ContainerSize::tryFrom((string) $record->container_size)?->label();

                                if (! $sizeLabel) {
                                    $legacy = [
                                        '20'   => ContainerSize::COC_20_DRY->label(),
                                        '40'   => ContainerSize::COC_40_DRY->label(),
                                        '40hc' => ContainerSize::COC_40_DRY_HC->label(),
                                        '45hc' => ContainerSize::COC_21_DRY->label(),
                                    ];
                                    $key = strtolower((string) $record->container_size);
                                    $sizeLabel = $legacy[$key] ?? null;
                                }
                            }

                            if ($sizeLabel) {
                                $qty = $record->container_qty ? " × {$record->container_qty}" : '';
                                $label .= " • {$sizeLabel}{$qty}";
                            }
                        }

                        return $label;
                    })
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'fcl' => 'primary',
                        'lcl' => 'info',
                        'car_carrier' => 'warning',
                        'towing' => 'warning',
                        'truck' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('delivery_scope')
                    ->label('Cakupan')
                    ->getStateUsing(fn(Shipment $r) => $r->delivery_scope?->label() ?? (is_string($r->delivery_scope) ? $r->delivery_scope : '-'))
                    ->badge()
                    ->colors([
                        'primary' => [DeliveryScope::PortToPort->label()],
                        'success' => [DeliveryScope::DoorToDoor->label()],
                        'warning' => [DeliveryScope::DoorToPort->label(), DeliveryScope::PortToDoor->label()],
                    ])
                    ->toggleable(),

                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? ucfirst($state) : '-')
                    ->color(fn(?string $state) => $state === 'urgent' ? 'danger' : 'gray'),

                TextColumn::make('cargo_type')
                    ->label('Muatan')
                    ->getStateUsing(fn(Shipment $r) => $r->cargo_type?->label() ?? (is_string($r->cargo_type) ? $r->cargo_type : '-'))
                    ->badge()
                    ->colors([
                        'info' => ['General Cargo'],
                        'warning' => ['Unit Kendaraan'],
                    ])
                    ->toggleable(),

                TextColumn::make('packages_total')->label('Koli')->sortable()->toggleable(),
                TextColumn::make('cbm_total')
                    ->label('CBM')
                    ->numeric(decimalPlaces: 3, decimalSeparator: '.', thousandsSeparator: ',')
                    ->placeholder('—') 
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('weight_total')
                    ->label('Berat (kg)')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),


                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn(Shipment $r) => $r->status?->label() ?? (is_string($r->status) ? $r->status : '-'))
                    ->colors([
                        'gray'    => ['Draf'],
                        'warning' => ['Menunggu', 'Ditahan'],
                        'info'    => ['Penjemputan', 'Dalam Perjalanan'],
                        'success' => ['Terkirim'],
                        'danger'  => ['Dibatalkan'],
                    ])
                    ->sortable(),

                TextColumn::make('etd')->label('ETD')->badge()->dateTime('d M Y H:i')->color('gray')->sortable(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->badge()
                    ->dateTime('d M Y H:i')
                    ->color(function ($state) {
                        if (! $state) return 'gray';
                        $eta = Carbon::parse($state);
                        if ($eta->isPast()) return 'danger';
                        if ($eta->diffInDays(now()) <= 2) return 'warning';
                        return 'success';
                    })
                    ->sortable(),

                TextColumn::make('updated_at')->label('Diubah')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                    ->native(false),

                SelectFilter::make('customer_id')->label('Pengirim')->relationship('customer', 'name')->searchable()->preload(),
                SelectFilter::make('receiver_id')->label('Penerima')->relationship('receiver', 'name')->searchable()->preload(),

                Filter::make('in_progress')
                    ->label('Sedang Berjalan')
                    ->query(fn(Builder $q) => $q->whereIn('status', array_map(fn($e) => $e->value, ShipmentStatus::inProgress())))
                    ->toggle(),

                SelectFilter::make('service_type')->label('Jenis Layanan')->options([
                    ServiceType::SeaFreight->value   => ServiceType::SeaFreight->label(),
                    ServiceType::LandTrucking->value => ServiceType::LandTrucking->label(),
                    ServiceType::CarCarrier->value   => ServiceType::CarCarrier->label(),
                ])->native(false),

                SelectFilter::make('origin_city_id')->label('Kota Asal')->relationship('originCity', 'name')->searchable()->preload(),
                SelectFilter::make('destination_city_id')->label('Kota Tujuan')->relationship('destinationCity', 'name')->searchable()->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->defaultSort('updated_at', 'desc')
            ->actions([EditAction::make()->label('Edit')])
            ->bulkActions([
                BulkAction::make('export_selected')
                    ->label('Export Terpilih (CSV)')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) {
                            Notification::make()->title('Tidak ada baris terpilih')->warning()->send();
                            return;
                        }

                        $filename = 'shipments-selected-' . now()->format('Ymd-His') . '.csv';

                        return response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, [
                                'Kode',
                                'Pengirim',
                                'Penerima',
                                'Asal',
                                'Tujuan',
                                'Moda',
                                'Layanan',
                                'Opsi',
                                'Cakupan',
                                'Prioritas',
                                'Muatan',
                                'Koli',
                                'CBM',
                                'Berat (kg)',
                                'Status',
                                'ETD',
                                'ETA',
                                'Dibuat'
                            ]);

                            foreach ($records as $r) {
                                $mode   = $r->mode?->label()         ?? (string) $r->mode;
                                $stype  = $r->service_type?->label()  ?? (string) $r->service_type;
                                $opt    = (string) $r->service_option ?: '-';
                                $scope  = $r->delivery_scope?->label() ?? (string) $r->delivery_scope ?: '-';
                                $prio   = $r->priority ? ucfirst($r->priority) : '-';
                                $cargo  = $r->cargo_type?->label()    ?? (string) $r->cargo_type;
                                $status = $r->status?->label()        ?? (string) $r->status;

                                $cbm   = is_null($r->cbm_total)   ? null : number_format((float) $r->cbm_total, 3, '.', '');
                                $wkg   = is_null($r->weight_total) ? null : number_format((float) $r->weight_total, 2, '.', '');
                                $etd   = $r->etd ? Carbon::parse($r->etd)->format('d M Y H:i') : null;
                                $eta   = $r->eta ? Carbon::parse($r->eta)->format('d M Y H:i') : null;
                                $cdate = $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : null;

                                fputcsv($out, [
                                    $r->code,
                                    $r->customer->name    ?? '-',
                                    $r->receiver->name    ?? '-',
                                    $r->originCity->name  ?? '-',
                                    $r->destinationCity->name ?? '-',
                                    $mode,
                                    $stype,
                                    $opt,
                                    $scope,
                                    $prio,
                                    $cargo,
                                    $r->packages_total,
                                    $cbm,
                                    $wkg,
                                    $status,
                                    $etd,
                                    $eta,
                                    $cdate,
                                ]);
                            }

                            fclose($out);
                        }, $filename, ['Content-Type' => 'text/csv']);
                    }),

                DeleteBulkAction::make()
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus data terpilih?')
                    ->modalDescription('Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Data terpilih telah dihapus'),
            ]);
    }


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListShipments::route('/'),
            'create' => CreateShipment::route('/create'),
            'edit'   => EditShipment::route('/{record}/edit'),
        ];
    }
}

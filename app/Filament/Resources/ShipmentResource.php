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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Forms\Set;
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

        $recalcLclTotals = function (Get $get, Set $set) {
            $rows = $get('lcl_items') ?? [];
            $totalCbm = 0.0;
            $totalPkg = 0;
            $totalWgt = 0.0;

            foreach ($rows as $row) {
                $qty  = (int)   ($row['qty']       ?? 0);
                $p    = (float) ($row['length_cm'] ?? 0);
                $l    = (float) ($row['width_cm']  ?? 0);
                $t    = (float) ($row['height_cm'] ?? 0);
                $wpc  = (float) ($row['weight_kg'] ?? 0);

                $cbmItem = ($p * $l * $t * $qty) / 1_000_000;
                $totalCbm += $cbmItem;
                $totalPkg += $qty;
                $totalWgt += ($wpc * $qty);
            }

            $cbmRounded = round($totalCbm, 3, PHP_ROUND_HALF_UP);
            $set('cbm_total', $cbmRounded);
            $set('packages_total', $totalPkg);
            $set('weight_total', round($totalWgt, 2, PHP_ROUND_HALF_UP));
        };

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
                            ->afterStateUpdated(function (Set $set) {
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
                            ->label('Asal (Kota Asal) *')
                            ->relationship('originOffice', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('cth: Jakarta')
                            ->columnSpan(6),

                        Select::make('destination_office_id')
                            ->label('Tujuan (Kota Tujuan) *')
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
                                        if ($state !== 'fcl') {
                                            $set('container_size', null);
                                            $set('container_qty', null);
                                        }
                                        if ($state !== 'lcl') {
                                            $set('lcl_items', null);
                                            $set('cbm_total', null);
                                            $set('packages_total', null);
                                            $set('weight_total', null);
                                        }
                                        if ($state !== 'lcl') {
                                            foreach (['lcl_items', 'cbm_total', 'packages_total', 'weight_total'] as $f) {
                                                $set($f, null);
                                            }
                                            $set('weight_unit', 'kg');
                                        }
                                    })

                                    ->columnSpan(12),
                                Select::make('container_size')
                                    ->label("Size Kontainer (FCL • General)")
                                    ->options([
                                        '20'   => "20'",
                                        '40'   => "40'",
                                        '40HC' => "40' HC",
                                        '45HC' => "45' HC",
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'fcl' &&
                                            $get('cargo_type') === CargoType::General->value
                                    )
                                    ->required(
                                        fn(Get $get) =>
                                        $get('service_option') === 'fcl' &&
                                            $get('cargo_type') === CargoType::General->value
                                    )
                                    ->columnSpan(4),

                                TextInput::make('container_qty')
                                    ->label('Jumlah Kontainer (FCL • General)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'fcl' &&
                                            $get('cargo_type') === CargoType::General->value
                                    )
                                    ->required(
                                        fn(Get $get) =>
                                        $get('service_option') === 'fcl' &&
                                            $get('cargo_type') === CargoType::General->value
                                    )
                                    ->columnSpan(4),

                                Repeater::make('lcl_items')
                                    ->label('Rincian Volume (LCL • General Cargo)')
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'lcl' &&
                                            $get('cargo_type') === CargoType::General->value
                                    )
                                    ->columns(12)
                                    ->schema([
                                        TextInput::make('description')->label('Deskripsi')->maxLength(120)->columnSpan(3),
                                        TextInput::make('length_cm')->label('P (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('width_cm')->label('L (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('height_cm')->label('T (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('qty')->label('Koli')->numeric()->minValue(1)->default(1)->live(onBlur: true)->columnSpan(1),
                                        TextInput::make('weight_kg')->label('Berat/pcs (kg)')->numeric()->minValue(0)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('cbm_item')->label('CBM')
                                            ->disabled()->dehydrated(false)
                                            ->afterStateHydrated(function (Get $get, Set $set) {
                                                $p = (float)($get('length_cm') ?? 0);
                                                $l = (float)($get('width_cm') ?? 0);
                                                $t = (float)($get('height_cm') ?? 0);
                                                $q = (int)  ($get('qty') ?? 0);
                                                $cbm = ($p * $l * $t * $q) / 1_000_000;
                                                $rounded = round($cbm, 3, PHP_ROUND_HALF_UP);
                                                $set('cbm_item', $rounded > 0 ? number_format($rounded, 3, '.', '') : null);
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
                                            $rounded = round($cbm, 3, PHP_ROUND_HALF_UP);
                                            $rows[$i]['cbm_item'] = $rounded > 0 ? number_format($rounded, 3, '.', '') : null;
                                        }
                                        $set('lcl_items', $rows);
                                        $recalcLclTotals($get, $set);
                                    })
                                    ->addActionLabel('Tambah Item')
                                    ->columnSpan(12),

                                Group::make()->columns(12)
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'lcl' &&
                                            $get('cargo_type') === CargoType::General->value
                                    )
                                    ->schema([
                                        Placeholder::make('sum_packages')
                                            ->label('Total Koli')
                                            ->content(fn(Get $get) => (string)($get('packages_total') ?? 0))
                                            ->columnSpan(2),
                                        Placeholder::make('sum_cbm')
                                            ->label('Total CBM')
                                            ->content(fn(Get $get) => number_format((float)($get('cbm_total') ?? 0), 3, '.', ''))
                                            ->columnSpan(2),

                                        Placeholder::make('sum_weight')
                                            ->label('Total Berat (kg)')
                                            ->content(fn(Get $get) => number_format((float)($get('weight_total') ?? 0), 2, '.', ''))
                                            ->columnSpan(4),
                                    ]),

                                Hidden::make('cbm_total')->dehydrated(),
                                Hidden::make('packages_total')->dehydrated(),
                                Hidden::make('weight_total')->dehydrated(),

                                Repeater::make('units')
                                    ->label('Unit Kendaraan (Laut)')
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('cargo_type') === CargoType::Vehicle->value
                                    )
                                    ->columns(12)
                                    ->schema([
                                        TextInput::make('model_no')->label('Model No.')->maxLength(60)->columnSpan(3),
                                        TextInput::make('reg_no')->label('No. Polisi / Reg')->maxLength(30)->columnSpan(2),
                                        TextInput::make('chassis_no')->label('Rangka No.')->maxLength(60)->columnSpan(2),
                                        TextInput::make('engine_no')->label('Mesin No.')->maxLength(60)->columnSpan(2),
                                        TextInput::make('color')->label('Warna')->maxLength(30)->columnSpan(1),
                                        TextInput::make('do_number')->label('No. DO')->maxLength(60)->columnSpan(2),

                                        Checkbox::make('is_rack')->label('Rack')->inline(false)->columnSpan(2),
                                        TextInput::make('qty')->label('Qty')->numeric()->minValue(1)->default(1)->columnSpan(1),
                                        TextInput::make('notes')->label('Ket')->maxLength(120)->columnSpan(5),
                                    ])
                                    ->addActionLabel('Tambah Unit')
                                    ->columnSpan(12),

                                Select::make('container_size_vehicle')
                                    ->label("Size Kontainer (FCL • Unit Kendaraan)")
                                    ->options([
                                        '20'   => "20'",
                                        '40'   => "40'",
                                        '40HC' => "40' HC",
                                        '45HC' => "45' HC",
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'fcl' &&
                                            $get('cargo_type') === CargoType::Vehicle->value
                                    )
                                    ->required(false)
                                    ->columnSpan(4),


                                TextInput::make('container_qty_vehicle')
                                    ->label('Jumlah Kontainer (FCL • Unit)')
                                    ->numeric()->minValue(1)
                                    ->visible(
                                        fn(Get $get) =>
                                        $get('service_option') === 'fcl' &&
                                            $get('cargo_type') === CargoType::Vehicle->value
                                    )
                                    ->required(false)
                                    ->columnSpan(4),

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
                            ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Land->value)
                            ->schema([
                                ViewField::make('mode_badge_land')
                                    ->view('filament.forms.fields.mode-badge-land')
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
                                        fn(Set $set, $state) =>
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

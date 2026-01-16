<?php

namespace App\Filament\Resources;

use App\Enums\{
    ServiceType,
    ShipmentMode,
    ShipmentStatus,
    CargoType,
    RequestType,
    ContainerSize,
    DeliveryScope
};
use App\Enums\TrackStatus;
use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Filament\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Filament\Resources\ArmadaAssignmentResource;
use App\Models\Armada;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Voyage;
use App\Models\Depot;
use App\Models\City;
use Filament\Facades\Filament;
use Filament\Forms\Components\{
    Checkbox,
    DatePicker,
    DateTimePicker,
    FileUpload,
    Grid,
    Group,
    Hidden,
    Placeholder,
    Repeater,
    Section,
    Select,
    Textarea,
    TextInput,
    ToggleButtons,
    ViewField
};
use Filament\Forms\Components\Actions\Action;
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
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationGroup   = 'Pengiriman';
    protected static ?string $navigationLabel   = 'Permintaan Pengiriman';
    protected static ?string $modelLabel        = 'Permintaan Pengiriman';
    protected static ?string $pluralModelLabel  = 'Permintaan Pengiriman';
    protected static ?string $navigationIcon    = 'heroicon-m-queue-list';
    protected static ?int    $navigationSort    = 10;

    protected static function resolveDepotId(?int $branchId, ?string $mode, ?int $voyageId): ?int
    {
        if (!$branchId || !$mode) {
            return null;
        }

        $q = Depot::query()
            ->where('branch_id', $branchId)
            ->where('mode', $mode);

        if ($mode === ShipmentMode::Sea->value && $voyageId) {
            $polId = Voyage::whereKey($voyageId)->value('pol_id');
            if ($polId) {
                $byPol = (clone $q)->where('port_id', $polId)->orderBy('name')->value('id');
                if ($byPol) {
                    return (int) $byPol;
                }
            }
        }

        return $q->orderBy('name')->value('id');
    }

    protected static function currentBranchId(): ?int
    {
        if (app()->bound('currentBranchId')) {
            return app('currentBranchId');
        }

        return Filament::auth()->user()?->branch_id;
    }


    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        $branchId = self::currentBranchId();

        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        return $q;
    }


    protected static function resolveTimelineMask(Shipment $m): array
    {
        $cfg      = config('jss_timeline');
        $profiles = $cfg['profiles'] ?? [];
        $fallbackKey = $cfg['default_profile'] ?? 'standard_sea';
        $fallback = $profiles[$fallbackKey] ?? [
            'show_planning'        => true,
            'show_terminal_detail' => true,
            'show_legacy'          => false,
        ];

        $mode     = strtolower($m->mode?->value ?? (string) $m->mode);
        $originBr = (int) ($m->branch_id ?? 0);
        $destBr   = (int) optional($m->destinationOffice)->branch_id ?: 0;

        $matches = function (array $when) use ($mode, $originBr, $destBr): bool {
            if (isset($when['mode']) && strtolower($when['mode']) !== $mode) {
                return false;
            }
            if (isset($when['branch_id_in']) && ! in_array($originBr, (array) $when['branch_id_in'], true)) {
                return false;
            }
            if (isset($when['not_branch_id_in']) && in_array($originBr, (array) $when['not_branch_id_in'], true)) {
                return false;
            }
            if (isset($when['dest_branch_id_in']) && ! in_array($destBr, (array) $when['dest_branch_id_in'], true)) {
                return false;
            }

            return true;
        };

        foreach (($cfg['rules'] ?? []) as $rule) {
            if ($matches($rule['when'] ?? [])) {
                $use = $rule['use'] ?? null;
                return $profiles[$use] ?? $fallback;
            }
        }

        return $fallback;
    }

    public static function form(Form $form): Form
    {
        $recalcLclTotals = function (Get $get, Set $set) {
            $rows      = $get('lcl_items') ?? [];
            $sumCbm    = 0.0;
            $sumPkg    = 0;
            $sumItemKg = 0.0;

            foreach ($rows as $r) {
                $qty = (int) ($r['qty'] ?? 0);
                $p   = (float) ($r['length_cm'] ?? 0);
                $l   = (float) ($r['width_cm'] ?? 0);
                $t   = (float) ($r['height_cm'] ?? 0);
                $w   = (float) ($r['weight_kg'] ?? 0);

                $sumCbm    += ($p * $l * $t * $qty) / 1_000_000;
                $sumPkg    += $qty;
                $sumItemKg += ($w * $qty);
            }

            $set('cbm_total', round($sumCbm, 3, PHP_ROUND_HALF_UP));
            $set('packages_total', $sumPkg);

            $override = trim((string) ($get('weight_total_input') ?? ''));
            if ($override !== '' && is_numeric($override)) {
                $set('weight_total', round((float) $override, 2, PHP_ROUND_HALF_UP));
            } else {
                $set('weight_total', $sumItemKg > 0 ? round($sumItemKg, 2, PHP_ROUND_HALF_UP) : null);
            }
        };

        return $form
            ->schema([
                Section::make('A. Data Customer & Dokumen')
                    ->schema([
                        Grid::make(12)->schema([
                            Hidden::make('branch_id')
                                ->default(fn() => Filament::auth()->user()?->branch_id)
                                ->dehydrated(),
                            Group::make([
                                Select::make('customer_id')
                                    ->label('Pengirim')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('pickup_contact_use_custom', false);
                                    })
                                    ->columnSpan(12),
                            ])->columnSpan(['default' => 12, 'md' => 6]),
                            Group::make([
                                Select::make('receiver_id')
                                    ->label('Penerima')
                                    ->relationship('receiver', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('delivery_contact_use_custom', false);
                                    })
                                    ->columnSpan(12),
                            ])->columnSpan(['default' => 12, 'md' => 6]),
                            Placeholder::make('pickup_contact_summary')
                                ->key('pickup_contact_summary')
                                ->label('Kontak untuk Pickup')
                                ->content(function (Get $get) {
                                    if ($get('pickup_contact_use_custom')) {
                                        return collect([
                                            $get('pickup_contact_name'),
                                            $get('pickup_contact_phone'),
                                            $get('pickup_contact_address'),
                                        ])->filter()->join(' | ');
                                    }

                                    $id = (int) $get('customer_id');
                                    if ($id > 0) {
                                        $c = Customer::query()
                                            ->select(['name', 'pic_name', 'phone', 'pic_phone', 'address'])
                                            ->find($id);

                                        if ($c) {
                                            return collect([
                                                $c->pic_name ?: $c->name,
                                                $c->pic_phone ?: $c->phone,
                                                $c->address,
                                            ])->filter()->join(' | ');
                                        }
                                    }

                                    return 'Belum ada kontak';
                                })
                                ->hintActions([
                                    Action::make('editPickupContact')
                                        ->label('Ganti kontak')
                                        ->icon('heroicon-m-pencil')
                                        ->link()
                                        ->modalHeading('Ganti Pickup Contact')
                                        ->mountUsing(function (array $data, Get $get, Set $set) {
                                            if ($get('pickup_contact_use_custom')) {
                                                return;
                                            }

                                            $id = (int) $get('customer_id');
                                            if ($id > 0) {
                                                $c = Customer::query()
                                                    ->select(['name', 'pic_name', 'phone', 'pic_phone', 'address'])
                                                    ->find($id);

                                                if ($c) {
                                                    $set('pickup_contact_name', $c->pic_name ?: $c->name);
                                                    $set('pickup_contact_phone', $c->pic_phone ?: $c->phone);
                                                    $set('pickup_contact_address', $c->address);
                                                }
                                            }
                                        })
                                        ->form([
                                            TextInput::make('pickup_contact_name')->label('Nama PIC')->maxLength(100)->required(),
                                            TextInput::make('pickup_contact_phone')->label('Telepon')->tel()->maxLength(30)->required(),
                                            Textarea::make('pickup_contact_address')->label('Alamat')->rows(2)->required(),
                                        ])
                                        ->action(function (array $data, Set $set) {
                                            $set('pickup_contact_use_custom', true);
                                            $set('pickup_contact_name', $data['pickup_contact_name']);
                                            $set('pickup_contact_phone', $data['pickup_contact_phone']);
                                            $set('pickup_contact_address', $data['pickup_contact_address']);
                                        }),
                                    Action::make('resetPickupContact')
                                        ->label('Gunakan default')
                                        ->icon('heroicon-m-arrow-path')
                                        ->color('gray')
                                        ->link()
                                        ->requiresConfirmation()
                                        ->action(fn(Set $set) => $set('pickup_contact_use_custom', false)),
                                ])
                                ->extraAttributes([
                                    'class' => 'text-sm text-gray-700 break-words whitespace-pre-line leading-relaxed md:line-clamp-none line-clamp-4',
                                ])
                                ->columnSpan(['default' => 12, 'md' => 6]),
                            Placeholder::make('delivery_contact_summary')
                                ->key('delivery_contact_summary')
                                ->label('Kontak untuk Pengantaran')
                                ->content(function (Get $get) {
                                    if ($get('delivery_contact_use_custom')) {
                                        return collect([
                                            $get('delivery_contact_name'),
                                            $get('delivery_contact_phone'),
                                            $get('delivery_contact_address'),
                                        ])->filter()->join(' | ');
                                    }

                                    $id = (int) $get('receiver_id');
                                    if ($id > 0) {
                                        $r = Customer::query()
                                            ->select(['name', 'pic_name', 'phone', 'pic_phone', 'address'])
                                            ->find($id);

                                        if ($r) {
                                            return collect([
                                                $r->pic_name ?: $r->name,
                                                $r->pic_phone ?: $r->phone,
                                                $r->address,
                                            ])->filter()->join(' | ');
                                        }
                                    }

                                    return 'Belum ada kontak';
                                })
                                ->hintActions([
                                    Action::make('editDeliveryContact')
                                        ->label('Ganti kontak')
                                        ->icon('heroicon-m-pencil')
                                        ->link()
                                        ->modalHeading('Ganti Delivery Contact')
                                        ->mountUsing(function (array $data, Get $get, Set $set) {
                                            if ($get('delivery_contact_use_custom')) {
                                                return;
                                            }

                                            $id = (int) $get('receiver_id');
                                            if ($id > 0) {
                                                $r = Customer::query()
                                                    ->select(['name', 'pic_name', 'phone', 'pic_phone', 'address'])
                                                    ->find($id);

                                                if ($r) {
                                                    $set('delivery_contact_name', $r->pic_name ?: $r->name);
                                                    $set('delivery_contact_phone', $r->pic_phone ?: $r->phone);
                                                    $set('delivery_contact_address', $r->address);
                                                }
                                            }
                                        })
                                        ->form([
                                            TextInput::make('delivery_contact_name')->label('Nama PIC')->maxLength(100)->required(),
                                            TextInput::make('delivery_contact_phone')->label('Telepon')->tel()->maxLength(30)->required(),
                                            Textarea::make('delivery_contact_address')->label('Alamat')->rows(2)->required(),
                                        ])
                                        ->action(function (array $data, Set $set) {
                                            $set('delivery_contact_use_custom', true);
                                            $set('delivery_contact_name', $data['delivery_contact_name']);
                                            $set('delivery_contact_phone', $data['delivery_contact_phone']);
                                            $set('delivery_contact_address', $data['delivery_contact_address']);
                                        }),
                                    Action::make('resetDeliveryContact')
                                        ->label('Gunakan default')
                                        ->icon('heroicon-m-arrow-path')
                                        ->color('gray')
                                        ->link()
                                        ->requiresConfirmation()
                                        ->action(fn(Set $set) => $set('delivery_contact_use_custom', false)),
                                ])
                                ->extraAttributes([
                                    'class' => 'text-sm text-gray-700 break-words whitespace-pre-line leading-relaxed md:line-clamp-none line-clamp-4',
                                ])
                                ->columnSpan(['default' => 12, 'md' => 6]),
                            Hidden::make('pickup_contact_use_custom')->default(false)->dehydrated(false),
                            Hidden::make('pickup_contact_name')->dehydrated(false),
                            Hidden::make('pickup_contact_phone')->dehydrated(false),
                            Hidden::make('pickup_contact_address')->dehydrated(false),
                            Hidden::make('delivery_contact_use_custom')->default(false)->dehydrated(false),
                            Hidden::make('delivery_contact_name')->dehydrated(false),
                            Hidden::make('delivery_contact_phone')->dehydrated(false),
                            Hidden::make('delivery_contact_address')->dehydrated(false),
                            Section::make('Detail Permintaan')
                                ->schema([
                                    Grid::make(12)->schema([
                                        Select::make('request_type')
                                            ->label('Tipe Permintaan')
                                            ->options(collect(RequestType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                                            ->default(RequestType::SPPB_DO->value)
                                            ->required()
                                            ->live()
                                            ->selectablePlaceholder(false)
                                            ->columnSpan(['default' => 12, 'md' => 2]),
                                        TextInput::make('doc_number')
                                            ->label('No. Dokumen')
                                            ->maxLength(50)
                                            ->visible(fn(Get $get) => $get('request_type') === 'sppb_do')
                                            ->required(fn(Get $get) => $get('request_type') === 'sppb_do')
                                            ->columnSpan(['default' => 12, 'md' => 5]),
                                        Select::make('priority')
                                            ->label('Prioritas')
                                            ->options(['normal' => 'Normal', 'urgent' => 'Urgent'])
                                            ->default('normal')
                                            ->columnSpan(['default' => 12, 'md' => 2]),
                                        DatePicker::make('requested_at')
                                            ->label('Tanggal Permintaan')
                                            ->default(now())
                                            ->required()
                                            ->columnSpan(['default' => 12, 'md' => 3]),
                                    ]),
                                ])
                                ->compact()
                                ->columnSpan(12),
                            Grid::make(12)->schema([
                                FileUpload::make('attachments')
                                    ->label('Lampiran Dokumen')
                                    ->multiple()
                                    ->disk('public')
                                    ->directory(fn() => 'shipments/' . now()->format('Y/m'))
                                    ->visibility('public')
                                    ->preserveFilenames()
                                    ->downloadable()
                                    ->openable()
                                    ->imagePreviewHeight('160')
                                    ->acceptedFileTypes([
                                        'image/*',
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'text/plain',
                                    ])
                                    ->columnSpan(['default' => 12, 'md' => 6]),
                                Textarea::make('notes')
                                    ->label('Keterangan tambahan')
                                    ->rows(6)
                                    ->maxLength(1000)
                                    ->columnSpan(['default' => 12, 'md' => 6]),
                            ])->columnSpan(12),
                        ]),
                    ])
                    ->compact(),

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
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
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
                                        'voyage_id',
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
                                ) {
                                    $set($f, null);
                                }

                                $branchId = (int) ($get('branch_id') ?: Filament::auth()->user()?->branch_id);
                                $depotId  = self::resolveDepotId($branchId, $state, $get('voyage_id'));

                                $set('assigned_depot_id', $depotId);
                            })
                            ->rules(function (Get $get) {
                                return [
                                    function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $state = $get();

                                        if ($value === ShipmentMode::Sea->value) {
                                            $voy = $state['voyage_id'] ?? null;
                                            $pol = trim((string) ($state['pol'] ?? ''));
                                            $pod = trim((string) ($state['pod'] ?? ''));

                                            if (! $voy && ($pol === '' && $pod === '')) {
                                                $fail('Untuk moda laut, isi Voyage atau minimal POL/POD.');
                                            }

                                            if ($voy && empty($state['etd'])) {
                                                $fail('ETD dari Voyage tidak terbaca. Pastikan Voyage punya ETD.');
                                            }
                                        }
                                    },
                                ];
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
                            ->reactive()
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Select::make('destination_city_id')
                            ->label('Tujuan (Kota Tujuan) *')
                            ->placeholder('Pilih Kota Tujuan')
                            ->relationship('destinationCity', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('cth: Manado')
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        ToggleButtons::make('cargo_type')
                            ->label('Jenis Muatan')
                            ->options([
                                CargoType::Vehicle->value => CargoType::Vehicle->label(),
                                CargoType::General->value => CargoType::General->label(),
                            ])
                            ->inline()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (string $state, Get $get, Set $set) {
                                if ($state === CargoType::Vehicle->value) {
                                    $rows = $get('units') ?? [];
                                    if (count($rows) === 0) {
                                        $set('units', [['qty' => 1]]);
                                    }

                                    $set('service_option', null);
                                    $set('cbm_total', null);
                                    $set('packages_total', null);
                                    $set('weight_total', null);
                                    $set('weight_total_input', null);
                                } else {
                                    if (($get('service_option') ?? 'fcl') === 'lcl') {
                                        $items = $get('lcl_items') ?? [];
                                        if (count($items) === 0) {
                                            $set('lcl_items', [['qty' => 1]]);
                                        }
                                    }

                                    $set('units', null);
                                }
                            })
                            ->columnSpan(12),

                        // SEA SECTION
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
                                    ->live()
                                    ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value && $get('cargo_type') === CargoType::General->value)
                                    ->required(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value && $get('cargo_type') === CargoType::General->value)
                                    ->afterStateUpdated(function (string $state, Get $get, Set $set) {
                                        if ($state === 'lcl' && $get('cargo_type') === CargoType::General->value) {
                                            $items = $get('lcl_items') ?? [];
                                            if (count($items) === 0) {
                                                $set('lcl_items', [['qty' => 1]]);
                                            }
                                        } else {
                                            $set('lcl_items', null);
                                            $set('cbm_total', null);
                                            $set('packages_total', null);
                                            $set('weight_total', null);
                                            $set('weight_total_input', null);
                                        }

                                        if ($state === 'fcl') {
                                            $set('container_size', null);
                                            $set('container_qty', null);
                                        }
                                    }),

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
                                    ->label('Ukuran Kontainer (FCL • General)')
                                    ->options(ContainerSize::options())
                                    ->native(false)
                                    ->searchable()
                                    ->visible(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->required(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->columnSpan(4),

                                TextInput::make('container_qty')
                                    ->label('Jumlah Kontainer (FCL • General)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->visible(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->required(fn(Get $get) => $get('service_option') === 'fcl' && $get('cargo_type') === CargoType::General->value)
                                    ->columnSpan(4),

                                TextInput::make('container_no')
                                    ->label('No. Kontainer')
                                    ->maxLength(20)
                                    ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value)
                                    ->required(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $fanout = function (Get $get, Set $set) {
                                            $no   = trim((string) ($get('container_no') ?? ''));
                                            $seal = trim((string) ($get('seal_no') ?? ''));
                                            $label = ($no === '' && $seal === '') ? '–' : ($seal !== '' ? ($no . ' • ' . $seal) : $no);

                                            $items = $get('lcl_items') ?? [];
                                            foreach ($items as $i => $row) {
                                                $items[$i]['container_display'] = $label;
                                            }
                                            $set('lcl_items', $items);

                                            $units = $get('units') ?? [];
                                            foreach ($units as $i => $row) {
                                                $units[$i]['container_display'] = $label;
                                            }
                                            $set('units', $units);
                                        };

                                        $fanout($get, $set);
                                    })
                                    ->columnSpan(4),

                                TextInput::make('seal_no')
                                    ->label('Seal No.')
                                    ->maxLength(20)
                                    ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value)
                                    ->required(function (Get $get) {
                                        if ($get('mode') !== ShipmentMode::Sea->value) {
                                            return false;
                                        }

                                        $isVehicle = $get('cargo_type') === CargoType::Vehicle->value;
                                        $loading   = $get('vehicle_loading');

                                        if ($isVehicle && $loading === 'flat_rack') {
                                            return false;
                                        }

                                        return true;
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $no   = trim((string) ($get('container_no') ?? ''));
                                        $seal = trim((string) ($get('seal_no') ?? ''));
                                        $label = ($no === '' && $seal === '') ? '–' : ($seal !== '' ? ($no . ' • ' . $seal) : $no);

                                        $items = $get('lcl_items') ?? [];
                                        foreach ($items as $i => $row) {
                                            $items[$i]['container_display'] = $label;
                                        }
                                        $set('lcl_items', $items);

                                        $units = $get('units') ?? [];
                                        foreach ($units as $i => $row) {
                                            $units[$i]['container_display'] = $label;
                                        }
                                        $set('units', $units);
                                    })
                                    ->columnSpan(4),

                                Repeater::make('lcl_items')
                                    ->label('Item Muatan (LCL • General)')
                                    ->visible(fn(Get $get) => $get('service_option') === 'lcl'
                                        && $get('cargo_type') === CargoType::General->value)
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->reorderable(false)
                                    ->columns(['default' => 12, 'md' => 6, 'lg' => 12])
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        $items = $get('lcl_items') ?? [];
                                        if (count($items) === 0) {
                                            $items = [['qty' => 1]];
                                        }

                                        $no   = trim((string) ($get('container_no') ?? ''));
                                        $seal = trim((string) ($get('seal_no') ?? ''));
                                        $label = ($no === '' && $seal === '') ? '–' : ($seal !== '' ? "{$no} • {$seal}" : $no);

                                        foreach ($items as $i => $r) {
                                            $p = (float) ($r['length_cm'] ?? 0);
                                            $l = (float) ($r['width_cm'] ?? 0);
                                            $t = (float) ($r['height_cm'] ?? 0);
                                            $q = (int) ($r['qty'] ?? 0);

                                            $cbm = ($p * $l * $t * $q) / 1_000_000;
                                            $items[$i]['cbm_item']          = $cbm > 0 ? number_format(round($cbm, 3), 3, '.', '') : null;
                                            $items[$i]['container_display'] = $r['container_display'] ?? $label;
                                        }

                                        $set('lcl_items', $items);
                                    })
                                    ->schema([
                                        TextInput::make('description')->label('Deskripsi')->maxLength(120)->columnSpan(3),
                                        TextInput::make('length_cm')->label('P (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('width_cm')->label('L (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('height_cm')->label('T (cm)')->numeric()->minValue(0.01)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('qty')->label('Koli')->numeric()->minValue(1)->default(1)->live(onBlur: true)->columnSpan(1),
                                        TextInput::make('weight_kg')->label('Berat/pcs (kg)')->numeric()->minValue(0)->live(onBlur: true)->columnSpan(2),
                                        TextInput::make('container_display')->label('Dalam Kontainer')->disabled()->dehydrated(false)->columnSpan(3),
                                        TextInput::make('cbm_item')->label('CBM')->disabled()->dehydrated(false)->columnSpan(2),
                                    ])
                                    ->addActionLabel('Tambah Item')
                                    ->columnSpan(12),

                                Section::make('Detail LCL (General)')
                                    ->visible(fn(Get $get) => $get('service_option') === 'lcl' && $get('cargo_type') === CargoType::General->value)
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
                                                Placeholder::make('sum_packages')->label('Total Koli')->live()
                                                    ->content(fn(Get $get) => (string) ($get('packages_total') ?? 0))->columnSpan(4),
                                                Placeholder::make('sum_cbm')->label('Total CBM')->live()
                                                    ->content(fn(Get $get) => number_format((float) ($get('cbm_total') ?? 0), 3, '.', ''))->columnSpan(4),
                                                Placeholder::make('sum_weight')->label('Total Berat (kg)')->live()
                                                    ->content(function (Get $get) {
                                                        $w = $get('weight_total');
                                                        return is_null($w) ? '—' : number_format((float) $w, 2, '.', '');
                                                    })->columnSpan(4),
                                            ]),
                                        Hidden::make('cbm_total')->dehydrated(),
                                        Hidden::make('packages_total')->dehydrated(),
                                        Hidden::make('weight_total')->dehydrated(),
                                    ]),

                                ToggleButtons::make('vehicle_kind')
                                    ->label('Jenis Unit')
                                    ->options(['car' => 'Mobil', 'motorcycle' => 'Motor'])
                                    ->inline()
                                    ->live()
                                    ->visible(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->required(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->dehydrated(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $valid = $state === 'motorcycle'
                                            ? ['regular', 'flat_rack']
                                            : ['regular', 'rack'];

                                        if (! in_array($get('vehicle_loading'), $valid, true)) {
                                            $set('vehicle_loading', null);
                                        }
                                    })
                                    ->columnSpan(6),

                                ToggleButtons::make('vehicle_loading')
                                    ->label('Metode Muat Unit')
                                    ->options(function (Get $get) {
                                        $kind = $get('vehicle_kind') ?: 'car';

                                        return $kind === 'motorcycle'
                                            ? ['regular' => 'Reguler', 'flat_rack' => 'Flat Rack']
                                            : ['regular' => 'Reguler', 'rack' => 'Dengan Rack'];
                                    })
                                    ->inline()
                                    ->live()
                                    ->visible(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->required(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->dehydrated(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->columnSpan(6),

                                Repeater::make('units')
                                    ->label('Unit Kendaraan (Laut)')
                                    ->visible(fn(Get $get) => $get('cargo_type') === CargoType::Vehicle->value)
                                    ->columns(12)
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->reorderable(false)
                                    ->mutateDehydratedStateUsing(fn($state) => array_values(array_filter($state ?? [], function ($r) {
                                        foreach (['model_no', 'reg_no', 'chassis_no', 'engine_no', 'color', 'do_number', 'qty', 'notes'] as $f) {
                                            if (! empty($r[$f])) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    })))
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        $rows = $get('units') ?? [];
                                        if (count($rows) === 0) {
                                            $rows = [['qty' => 1]];
                                        }
                                        $set('units', $rows);
                                    })
                                    ->schema([
                                        TextInput::make('model_no')->label('Model No.')->maxLength(60)->columnSpan(3),
                                        TextInput::make('reg_no')->label('No. Polisi / Reg')->maxLength(30)->columnSpan(3),
                                        TextInput::make('chassis_no')->label('Rangka No.')->maxLength(60)->columnSpan(3),
                                        TextInput::make('engine_no')->label('Mesin No.')->maxLength(60)->columnSpan(3),
                                        TextInput::make('color')->label('Warna')->maxLength(30)->columnSpan(4),
                                        TextInput::make('do_number')->label('No. DO')->maxLength(60)->columnSpan(2),
                                        TextInput::make('qty')->label('Qty')->numeric()->minValue(1)->default(1)->columnSpan(1),
                                        TextInput::make('notes')->label('Ket')->maxLength(120)->columnSpan(5),
                                        TextInput::make('container_display')
                                            ->label('Dalam Kontainer')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->formatStateUsing(function (Get $get) {
                                                $no   = trim((string) ($get('../../container_no') ?? ''));
                                                $seal = trim((string) ($get('../../seal_no') ?? ''));

                                                return ($no === '' && $seal === '')
                                                    ? '–'
                                                    : ($seal !== '' ? ($no . ' • ' . $seal) : $no);
                                            })
                                            ->columnSpan(3),
                                    ])
                                    ->addActionLabel('Tambah Unit')
                                    ->columnSpan(12),

                                Select::make('voyage_id')
                                    ->label('Jadwal Kapal *')
                                    ->native(false)
                                    ->searchable()
                                    ->required(fn(Get $get) => $get('mode') === ShipmentMode::Sea->value)
                                    ->hidden(fn(Get $get) => $get('mode') !== ShipmentMode::Sea->value)
                                    ->options(function () {
                                        return Voyage::with(['vessel', 'pol', 'pod'])
                                            ->orderByDesc('etd')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($v) => [
                                                $v->id => sprintf(
                                                    '%s / %s — %s (%s → %s)',
                                                    $v->vessel?->name ?: '-',
                                                    $v->voyage_no,
                                                    $v->etd ? Carbon::parse($v->etd)->format('d M Y H:i') : '-',
                                                    $v->pol?->code ?: $v->pol?->name ?: '-',
                                                    $v->pod?->code ?: $v->pod?->name ?: '-',
                                                ),
                                            ])->toArray();
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Voyage::with(['vessel', 'pol', 'pod'])
                                            ->where('voyage_no', 'ilike', "%{$search}%")
                                            ->orWhereHas('vessel', fn($q) => $q->where('name', 'ilike', "%{$search}%"))
                                            ->orWhereHas('pol', fn($q) => $q
                                                ->where('code', 'ilike', "%{$search}%")
                                                ->orWhere('name', 'ilike', "%{$search}%"))
                                            ->orWhereHas('pod', fn($q) => $q
                                                ->where('code', 'ilike', "%{$search}%")
                                                ->orWhere('name', 'ilike', "%{$search}%"))
                                            ->orderByDesc('etd')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($v) => [
                                                $v->id => sprintf(
                                                    '%s / %s — %s (%s → %s)',
                                                    $v->vessel?->name ?: '-',
                                                    $v->voyage_no,
                                                    $v->etd ? Carbon::parse($v->etd)->format('d M Y H:i') : '-',
                                                    $v->pol?->code ?: $v->pol?->name ?: '-',
                                                    $v->pod?->code ?: $v->pod?->name ?: '-',
                                                ),
                                            ])->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $v = Voyage::with(['vessel', 'pol', 'pod'])->find($value);

                                        return $v
                                            ? sprintf(
                                                '%s / %s — %s (%s → %s)',
                                                $v->vessel?->name ?: '-',
                                                $v->voyage_no,
                                                $v->etd ? Carbon::parse($v->etd)->format('d M Y H:i') : '-',
                                                $v->pol?->code ?: $v->pol?->name ?: '-',
                                                $v->pod?->code ?: $v->pod?->name ?: '-',
                                            )
                                            : null;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        if (! $state) {
                                            foreach (['vessel_name', 'voyage', 'pol', 'pod', 'etd', 'eta'] as $f) {
                                                $set($f, null);
                                            }
                                        } else {
                                            if ($v = Voyage::with(['vessel', 'pol', 'pod'])->find($state)) {
                                                $set('vessel_name', $v->vessel?->name);
                                                $set('voyage',      $v->voyage_no);
                                                $set('pol',         $v->pol?->code ?: $v->pol?->name);
                                                $set('pod',         $v->pod?->code ?: $v->pod?->name);
                                                $set('etd',         optional($v->etd)->toDateTimeString());
                                                $set('eta',         optional($v->eta)->toDateTimeString());
                                            }
                                        }

                                        $branchId = (int) ($get('branch_id') ?: Filament::auth()->user()?->branch_id);
                                        $depotId  = self::resolveDepotId($branchId, $get('mode'), $state);
                                        $set('assigned_depot_id', $depotId);
                                    })
                                    ->columnSpan(12),

                                TextInput::make('vessel_name')->disabled()->dehydrated()->columnSpan(4),
                                TextInput::make('voyage')->disabled()->dehydrated()->columnSpan(4),
                                DateTimePicker::make('etd')->disabled()->seconds(false)->dehydrated()->columnSpan(4),
                                DateTimePicker::make('eta')->disabled()->seconds(false)->dehydrated()->columnSpan(4),
                                TextInput::make('pol')->disabled()->dehydrated()->columnSpan(4),
                                TextInput::make('pod')->disabled()->dehydrated()->columnSpan(4),

                                Hidden::make('assigned_depot_id')->dehydrated(),

                                Placeholder::make('auto_depot_display')
                                    ->label('Depo Penugasan (otomatis)')
                                    ->content(function (Get $get) {
                                        $branchId = (int) ($get('branch_id') ?: Filament::auth()->user()?->branch_id);
                                        $depotId  = $get('assigned_depot_id') ?: self::resolveDepotId($branchId, $get('mode'), $get('voyage_id'));

                                        if ($depotId) {
                                            return Depot::whereKey($depotId)->value('name') ?: '—';
                                        }

                                        return 'Belum ada depo untuk cabang & mode ini. Tambahkan depo di menu Depo.';
                                    })
                                    ->columnSpan(12),

                                Placeholder::make('assignment_hint')
                                    ->label('Status Penugasan Depo')
                                    ->content(function (?Shipment $record) {
                                        if (! $record) {
                                            return '—';
                                        }

                                        $mode = $record->mode instanceof ShipmentMode
                                            ? $record->mode->value
                                            : (string) $record->mode;

                                        if ($mode !== ShipmentMode::Sea->value) {
                                            return 'Non-SEA: tidak memakai penugasan depo.';
                                        }

                                        return $record->assigned_depot?->name
                                            ? ('Assigned ke: ' . $record->assigned_depot->name)
                                            : 'Belum ter-assign.';
                                    })
                                    ->columnSpan(12),
                            ]),
                    ]),

                // LAND SECTION
                Group::make()
                    ->columnSpan(12)
                    ->columns(['default' => 12, 'md' => 6, 'lg' => 12])
                    ->visible(fn(Get $get) => $get('mode') === ShipmentMode::Land->value)
                    ->schema([
                        ViewField::make('mode_badge_land')
                            ->view('filament.forms.fields.mode-badge-land')
                            ->columnSpan(12),

                        Select::make('armada_id')
                            ->label('Pilih Armada')
                            ->relationship('armada', 'code')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    $set('vehicle_plate', null);
                                    $set('service_option', null);
                                    return;
                                }

                                $armada = Armada::find($state);
                                $set('vehicle_plate', $armada?->plate_number);
                                $set('service_option', match ($armada?->type) {
                                    'car_carrier' => 'car_carrier',
                                    'towing'      => 'towing',
                                    'truck'       => 'truck',
                                    default       => null,
                                });
                            })
                            ->columnSpan(6),

                        TextInput::make('vehicle_plate')
                            ->label('No. Polisi')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(6),

                        Select::make('driver_id')
                            ->label('Pilih Supir')
                            ->relationship('driver', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    $set('driver_name', null);
                                    $set('driver_phone', null);
                                    return;
                                }

                                $driver = \App\Models\Driver::find($state);
                                $set('driver_name', $driver?->name);
                                $set('driver_phone', $driver?->phone);
                            })
                            ->columnSpan(6),

                        TextInput::make('driver_phone')
                            ->label('No. HP Sopir')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(6),
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
            ->modifyQueryUsing(function (Builder $query) {
                $query->where(function ($w) {
                    $w->whereNull('status')
                        ->orWhereNotIn('status', [
                            ShipmentStatus::Delivered->value,
                            ShipmentStatus::Cancelled->value,
                        ]);
                });

                $branchId = app()->bound('currentBranchId')
                    ? app('currentBranchId')
                    : null;

                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }

                $query->with([
                    'originCity:id,name',
                    'destinationCity:id,name',
                    'destinationOffice:id,branch_id',
                    'tracks:id,shipment_id,status,actual_at,tracked_at',
                ]);

                $user = Filament::auth()->user();

                if (! ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin'))) {

                    if ($user?->branch_id) {
                        $query->where(function ($w) use ($user) {
                            $w->where('branch_id', $user->branch_id)
                                ->orWhereNull('branch_id');
                        });
                    }

                    if ($user && method_exists($user, 'hasRole') && $user->hasRole('field_coordinator')) {
                        $query->where(function ($qq) use ($user) {
                            $qq->where('coordinator_id', $user->id)
                                ->orWhereNull('coordinator_id');
                        });
                    }
                }

                $query->with([
                    'originCity:id,name',
                    'destinationCity:id,name',
                    'destinationOffice:id,branch_id',
                    'tracks:id,shipment_id,status,actual_at,tracked_at',
                    'units:id,shipment_id,reg_no,chassis_no,model_no,qty'
                ]);
            })
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
                    ->getStateUsing(function (Shipment $record): string {
                        $oCity = $record->originCity->name ?? '-';
                        $dCity = $record->destinationCity->name ?? '-';

                        return "<div class='font-medium'>{$oCity} &rarr; {$dCity}</div>";
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
                            default                     => '-',
                        };
                    })
                    ->color(function ($state) {
                        $val = $state instanceof RequestType ? $state->value : (string) $state;

                        return match ($val) {
                            RequestType::SPPB_DO->value => 'primary',
                            RequestType::WA_TELP->value => 'info',
                            RequestType::WALK_IN->value => 'success',
                            default                     => 'gray',
                        };
                    }),

                TextColumn::make('service_option')
                    ->label('Opsi')
                    ->formatStateUsing(function (?string $state, Shipment $record) {
                        if ($record->cargo_type === CargoType::Vehicle) {
                            return 'Unit';
                        }

                        return match ($state) {
                            'fcl'         => 'FCL',
                            'lcl'         => 'LCL',
                            'truck'       => 'Truck',
                            'towing'      => 'Towing',
                            'car_carrier' => 'Car Carrier',
                            default       => $state ?: '-',
                        };
                    })
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'fcl'         => 'primary',
                        'lcl'         => 'info',
                        'car_carrier' => 'warning',
                        'towing'      => 'warning',
                        'truck'       => 'gray',
                        default       => 'gray',
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

                TextColumn::make('packages_total')
                    ->label('Koli')
                    ->getStateUsing(function (Shipment $r) {
                        return $r->cargo_type === CargoType::General
                            ? $r->packages_total
                            : null;
                    })
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('cbm_total')
                    ->label('CBM')
                    ->getStateUsing(function (Shipment $r) {
                        if ($r->cargo_type !== CargoType::General) {
                            return null;
                        }

                        return $r->cbm_total;
                    })
                    ->numeric(3, '.', ',')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('weight_total')
                    ->label('Berat (kg)')
                    ->getStateUsing(function (Shipment $r) {
                        if ($r->cargo_type !== CargoType::General) {
                            return null;
                        }

                        return $r->weight_total;
                    })
                    ->numeric(2, '.', ',')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('attachments_count')
                    ->label('Lampiran')
                    ->getStateUsing(fn(Shipment $r) => count($r->attachments ?? []))
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn(Shipment $r): ShipmentStatus|string|null => $r->status?->label() ?? (is_string($r->status) ? $r->status : '-'))
                    ->colors([
                        'gray'    => ['Draf'],
                        'warning' => ['Menunggu', 'Ditahan'],
                        'info'    => ['Penjemputan', 'Dalam Perjalanan'],
                        'success' => ['Terkirim'],
                        'danger'  => ['Dibatalkan'],
                    ])
                    ->sortable(),

                TextColumn::make('kpi_tam_status')
                    ->label('KPI TAM')
                    ->badge()
                    ->getStateUsing(function (Shipment $r) {
                        if (! method_exists($r, 'evaluateKpiForManado')) {
                            return null;
                        }

                        $ev = $r->evaluateKpiForManado();

                        if (! ($ev['applies'] ?? false)) {
                            return null;
                        }

                        return $ev['badge'] ?? null;
                    })
                    ->color(function (Shipment $r) {
                        if (! method_exists($r, 'evaluateKpiForManado')) {
                            return 'gray';
                        }

                        $ev = $r->evaluateKpiForManado();

                        if (! ($ev['applies'] ?? false)) {
                            return 'gray';
                        }

                        return match ($ev['badge'] ?? null) {
                            'On Time' => 'success',
                            'Late'    => 'danger',
                            'Pending' => 'warning',
                            default   => 'gray',
                        };
                    })
                    ->tooltip(function (Shipment $r) {
                        return method_exists($r, 'kpiManadoSummaryText')
                            ? ($r->kpiManadoSummaryText() ?? null)
                            : null;
                    })
                    ->placeholder('')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kpi_tam_summary')
                    ->label('Dw/Sai/Dor/Total')
                    ->getStateUsing(function (Shipment $r) {
                        return method_exists($r, 'kpiManadoSummaryText')
                            ? ($r->kpiManadoSummaryText() ?? '—')
                            : '—';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('timeline_progress')
                    ->label('Progress')
                    ->badge()
                    ->color(function (Shipment $r) {
                        $mask  = method_exists($r, 'timelineMask') ? $r->timelineMask() : self::resolveTimelineMask($r);
                        $order = TrackStatus::simplifiedForMode($r->mode, $mask);
                        $total = max(1, count($order));

                        $idx     = -1;
                        $reached = $r->tracks->filter(function ($t) {
                            return ! empty($t->status) && (! empty($t->actual_at) || ! empty($t->tracked_at));
                        })->values();

                        $orderMap = [];
                        foreach ($order as $i => $s) {
                            $orderMap[$s->value] = $i;
                        }

                        foreach ($reached as $t) {
                            $sv = $t->status instanceof TrackStatus ? $t->status->value : (string) $t->status;
                            if (isset($orderMap[$sv])) {
                                $idx = max($idx, $orderMap[$sv]);
                            }
                        }

                        $done = $idx + 1;
                        $pct  = (int) floor(($done / $total) * 100);

                        if ($pct >= 100) {
                            return 'success';
                        }
                        if ($pct >= 70) {
                            return 'primary';
                        }
                        if ($pct >= 40) {
                            return 'info';
                        }

                        return 'gray';
                    })
                    ->getStateUsing(function (Shipment $r) {
                        $cfg   = config('jss_timeline');
                        $mask  = method_exists($r, 'timelineMask') ? $r->timelineMask() : self::resolveTimelineMask($r);
                        $order = TrackStatus::simplifiedForMode($r->mode, $mask);
                        $total = max(1, count($order));

                        $idx     = -1;
                        $reached = $r->tracks->filter(function ($t) {
                            return ! empty($t->status) && (! empty($t->actual_at) || ! empty($t->tracked_at));
                        })->values();

                        $orderMap = [];
                        foreach ($order as $i => $s) {
                            $orderMap[$s->value] = $i;
                        }

                        foreach ($reached as $t) {
                            $sv = $t->status instanceof TrackStatus ? $t->status->value : (string) $t->status;
                            if (isset($orderMap[$sv])) {
                                $idx = max($idx, $orderMap[$sv]);
                            }
                        }

                        $done = $idx + 1;
                        $pct  = (int) floor(($done / $total) * 100);

                        $profiles    = $cfg['profiles'] ?? [];
                        $fallbackKey = $cfg['default_profile'] ?? 'standard_sea';
                        $maskKey     = array_search($mask, $profiles, true);
                        $pname       = is_string($maskKey) ? $maskKey : $fallbackKey;

                        return "{$done}/{$total} • {$pct}% • {$pname}";
                    })
                    ->toggleable(),

                TextColumn::make('etd')
                    ->label('ETD')
                    ->badge()
                    ->dateTime('d M Y H:i')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->badge()
                    ->dateTime('d M Y H:i')
                    ->color(function ($state) {
                        if (! $state) {
                            return 'gray';
                        }

                        $eta = Carbon::parse($state);
                        if ($eta->isPast()) {
                            return 'danger';
                        }
                        if ($eta->diffInDays(now()) <= 2) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->sortable(),

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
                    ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
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
                    ->label('Sedang Berjalan')
                    ->query(fn(Builder $q) => $q->whereIn('status', array_map(fn($e) => $e->value, ShipmentStatus::inProgress())))
                    ->toggle(),

                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        ServiceType::SeaFreight->value   => ServiceType::SeaFreight->label(),
                        ServiceType::LandTrucking->value => ServiceType::LandTrucking->label(),
                        ServiceType::CarCarrier->value   => ServiceType::CarCarrier->label(),
                    ])
                    ->native(false),

                SelectFilter::make('origin_city_id')
                    ->label('Kota Asal')
                    ->relationship('originCity', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('destination_city_id')
                    ->label('Kota Tujuan')
                    ->relationship('destinationCity', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('tam_manado_kpi')
                    ->label('Toyota Astra Motor (Manado)')
                    ->query(function (Builder $query) {
                        $cfg         = config('jss_kpi.manado', []);
                        $customerIds = array_map('intval', $cfg['customer_ids'] ?? []);

                        if (empty($customerIds)) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query->whereIn('customer_id', $customerIds);
                    })
                    ->toggle(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->defaultSort('updated_at', 'desc')
            ->actions([
                EditAction::make()->label('Edit'),

                \Filament\Tables\Actions\Action::make('createAssignment')
                    ->label('Buat Penugasan')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->url(fn($record) => ArmadaAssignmentResource::getUrl('create', [
                        'prefill[shipment_id]' => $record->id,
                        'prefill[branch_id]'   => $record->branch_id,
                        'prefill[depot_id]'    => $record->origin_office_id ?? $record->depot_id,
                    ]))
                    ->visible(
                        fn(Shipment $record) => ($record->mode === ShipmentMode::Land)
                            && in_array(
                                ($record->status?->value ?? (string) $record->status),
                                array_map(fn($s) => $s->value, ShipmentStatus::active()),
                                true
                            )
                    ),

                \Filament\Tables\Actions\Action::make('print_resi')
                    ->label('Cetak Resi')
                    ->icon('heroicon-m-printer')
                    ->url(fn($record) => route('shipments.resi', ['shipment' => $record->id]) . '?download=1')
                    ->openUrlInNewTab(),

                \Filament\Tables\Actions\Action::make('sendToFc')
                    ->label('Kirim ke FC')
                    ->icon('heroicon-m-paper-airplane')
                    ->visible(fn(Shipment $r) => $r->status === ShipmentStatus::Draft)
                    ->requiresConfirmation()
                    ->action(fn(Shipment $r) => $r->sendToFc()),
            ])
            ->bulkActions([
                BulkAction::make('export_selected')
                    ->label('Export Terpilih (CSV)')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $branchId = self::currentBranchId();

                        if ($branchId) {
                            $records = $records->where('branch_id', $branchId);
                        }

                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('Tidak ada data yang dapat diexport')
                                ->warning()
                                ->send();
                            return;
                        }

                        $records->load(
                            'originCity:id,name',
                            'destinationCity:id,name',
                            'customer:id,name',
                            'receiver:id,name'
                        );

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
                                'KPI TAM',
                                'KPI Ringkas',
                                'ETD',
                                'ETA',
                                'Dibuat',
                            ]);

                            foreach ($records as $r) {
                                $mode   = $r->mode?->label()        ?? (string) $r->mode;
                                $stype  = $r->service_type?->label() ?? (string) $r->service_type;
                                $opt    = (string) $r->service_option ?: '-';
                                $scope  = $r->delivery_scope?->label() ?? (string) $r->delivery_scope ?: '-';
                                $prio   = $r->priority ? ucfirst($r->priority) : '-';
                                $cargo  = $r->cargo_type?->label()    ?? (string) $r->cargo_type;
                                $status = $r->status?->label()        ?? (string) $r->status;

                                $ev   = method_exists($r, 'evaluateKpiForManado') ? $r->evaluateKpiForManado() : null;
                                $kpiBadge = ($ev && ($ev['applies'] ?? false)) ? ($ev['badge'] ?? 'Pending') : 'Non-target';
                                $kpiText  = method_exists($r, 'kpiManadoSummaryText') ? ($r->kpiManadoSummaryText() ?? '') : '';

                                $cbm   = is_null($r->cbm_total)    ? null : number_format((float) $r->cbm_total, 3, '.', '');
                                $wkg   = is_null($r->weight_total) ? null : number_format((float) $r->weight_total, 2, '.', '');
                                $etd   = $r->etd ? Carbon::parse($r->etd)->format('d M Y H:i') : null;
                                $eta   = $r->eta ? Carbon::parse($r->eta)->format('d M Y H:i') : null;
                                $cdate = $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : null;

                                fputcsv($out, [
                                    $r->code,
                                    $r->customer->name          ?? '-',
                                    $r->receiver->name          ?? '-',
                                    $r->originCity->name        ?? '-',
                                    $r->destinationCity->name   ?? '-',
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
                                    $kpiBadge,
                                    $kpiText,
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

    public static function getPages(): array
    {
        return [
            'index'  => ListShipments::route('/'),
            'create' => CreateShipment::route('/create'),
            'edit'   => EditShipment::route('/{record}/edit'),
        ];
    }

    public static function canEdit($record): bool
    {
        $u = Filament::auth()->user();

        if ($u?->hasRole('super_admin')) {
            return true;
        }

        $branchId = self::currentBranchId();

        return $u
            && $u->hasRole('office_admin')
            && (int) $record->branch_id === (int) $branchId;
    }


    public static function canViewAny(): bool
    {
        $u = Filament::auth()->user();

        return (bool) ($u
            && method_exists($u, 'hasAnyRole')
            && $u->hasAnyRole(['super_admin', 'office_admin', 'field_coordinator']));
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        unset(
            $data['pickup_contact_use_custom'],
            $data['pickup_contact_name'],
            $data['pickup_contact_phone'],
            $data['pickup_contact_address'],
            $data['delivery_contact_use_custom'],
            $data['delivery_contact_name'],
            $data['delivery_contact_phone'],
            $data['delivery_contact_address'],
            $data['edit_pickup_contact'],
            $data['edit_delivery_contact'],
        );

        $branchId = self::currentBranchId();

        $data['branch_id'] = $branchId;

        $mode  = $data['mode']           ?? null;
        $cargo = $data['cargo_type']     ?? null;
        $opt   = $data['service_option'] ?? null;

        if ($mode === ShipmentMode::Sea->value) {
            $data['service_type'] = ServiceType::SeaFreight->value;

            if (empty($data['assigned_depot_id'])) {
                $data['assigned_depot_id'] = self::resolveDepotId(
                    (int) $branchId,
                    $mode,
                    (int) ($data['voyage_id'] ?? 0) ?: null
                );
            }

            if (empty($data['assigned_depot_id'])) {
                throw ValidationException::withMessages([
                    'assigned_depot_id' => 'Tidak ada Depo (mode laut) untuk cabang ini.',
                ]);
            }
        } elseif ($mode === ShipmentMode::Land->value) {
            $data['service_type'] = match ($opt) {
                'car_carrier' => ServiceType::CarCarrier->value,
                default       => ServiceType::LandTrucking->value,
            };
        }

        if (empty($data['status'])) {
            $data['status'] = ShipmentStatus::Draft->value;
        }

        return $data;
    }
}

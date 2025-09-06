<?php

namespace App\Filament\Resources;

use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\CargoType;
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
        return $form->schema([
            Section::make('A. Data Customer & Dokumen')
                ->columns(12)
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer *')
                        ->relationship('customer', 'name')
                        ->searchable()->preload()->required()
                        ->columnSpan(4),

                    TextInput::make('pic_name')
                        ->label('PIC / Contact *')
                        ->required()->maxLength(100)
                        ->columnSpan(4),

                    TextInput::make('pic_phone')
                        ->label('No. Telp/WA *')
                        ->tel()->required()->maxLength(20)
                        ->columnSpan(4),

                    Select::make('request_type')
                        ->label('Jenis Permintaan *')
                        ->options([
                            'sppb' => 'SPPB',
                            'do'   => 'DO',
                            'lain' => 'Lainnya',
                        ])->native(false)->required()->live()
                        ->columnSpan(3),

                    TextInput::make('doc_number')
                        ->label('No. Dokumen (SPPB/DO)')
                        ->placeholder('Opsional')->maxLength(64)->columnSpan(3),

                    Select::make('priority')
                        ->label('Prioritas')
                        ->options(['normal' => 'Normal', 'urgent' => 'Urgent'])
                        ->default('normal')->native(false)->columnSpan(3),

                    DatePicker::make('requested_at')
                        ->label('Tanggal Permintaan *')
                        ->native(false)->required()->columnSpan(3),

                    FileUpload::make('attachments')
                        ->label('Lampiran Dokumen')
                        ->multiple()->downloadable()->openable()
                        ->maxSize(10 * 1024)->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/*',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->columnSpan(7),

                    Textarea::make('notes')
                        ->label('Keterangan tambahan')->rows(5)->columnSpan(5),
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
                        ->inline()->required()->live()
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
                        })
                        ->columnSpan(12),

                    Select::make('origin_office_id')
                        ->label('Asal (Depo/Kota Asal) *')
                        ->relationship('originOffice', 'name')
                        ->searchable()->preload()->required()
                        ->helperText('cth: Jakarta')->columnSpan(6),

                    Select::make('destination_office_id')
                        ->label('Tujuan (Depo/Kota Tujuan) *')
                        ->relationship('destinationOffice', 'name')
                        ->searchable()->preload()->required()
                        ->helperText('cth: Manado')->columnSpan(6),

                    ToggleButtons::make('cargo_type')
                        ->label('Jenis Muatan')
                        ->options([
                            CargoType::Vehicle->value => 'Unit Kendaraan',
                            CargoType::General->value => 'General Cargo',
                        ])
                        ->inline()->required()->columnSpan(12),

                    // SEA
                    Group::make()
                        ->columnSpan(12)->columns(12)
                        ->visible(fn (Forms\Get $get) => $get('mode') === ShipmentMode::Sea->value)
                        ->schema([
                            Placeholder::make('mode_badge_sea')
                                ->content('Moda: Laut')
                                ->extraAttributes(['class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700'])
                                ->columnSpan(12),

                            ToggleButtons::make('service_option')
                                ->label('Layanan Laut')
                                ->options(['fcl' => 'FCL', 'lcl' => 'LCL'])
                                ->inline()->required()->columnSpan(12),

                            TextInput::make('vessel_name')->label('Nama Kapal')->maxLength(100)->columnSpan(4),
                            TextInput::make('voyage')->label('Voyage/Trip')->maxLength(50)->columnSpan(4),
                            DatePicker::make('etd')->label('ETD (Rencana Berangkat)')->native(false)->columnSpan(4),
                            DatePicker::make('eta')->label('ETA (Perkiraan Tiba)')->native(false)->columnSpan(4),
                            TextInput::make('pol')->label('POL — Pelabuhan Muat')->maxLength(100)->columnSpan(4),
                            TextInput::make('pod')->label('POD — Pelabuhan Bongkar')->maxLength(100)->columnSpan(4),
                        ]),

                    // LAND
                    Group::make()
                        ->columnSpan(12)->columns(12)
                        ->visible(fn (Forms\Get $get) => $get('mode') === ShipmentMode::Land->value)
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
                                ])->native(false)->live()
                                  ->afterStateUpdated(function (Forms\Set $set, $state) {
                                      $set('service_option', match ($state) {
                                          'car_carrier' => 'car_carrier',
                                          'towing'      => 'towing',
                                          default       => 'truck',
                                      });
                                  })
                                  ->columnSpan(4),

                            TextInput::make('vehicle_plate')->label('No. Polisi Armada')->maxLength(20)->columnSpan(4),
                            DatePicker::make('pickup_date')->label('Tanggal Pickup (estimasi)')->native(false)->columnSpan(4),

                            TextInput::make('driver_name')->label('Nama Supir')->maxLength(100)->columnSpan(6),
                            TextInput::make('driver_phone')->label('No. HP Supir')->tel()->maxLength(20)->columnSpan(6),
                        ]),
                ]),

            Hidden::make('service_type')->dehydrated(),
            Hidden::make('route_summary')->dehydrated(),

            Section::make('C. Konfirmasi')
                ->columns(12)
                ->schema([
                    Checkbox::make('confirm_is_true')
                        ->label('Data sudah benar & sesuai dokumen.')
                        ->accepted()->columnSpan(12),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')->badge()->copyable()
                    ->searchable()->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')->badge()
                    ->searchable()->sortable(),

                TextColumn::make('originOffice.name')
                    ->label('Asal')->badge()->sortable(),

                TextColumn::make('destinationOffice.name')
                    ->label('Tujuan')->badge()->sortable(),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(fn ($record) =>
                        $record->service_type instanceof ServiceType
                            ? $record->service_type->value
                            : (string) $record->service_type
                    )
                    ->formatStateUsing(fn (string $state) => match ($state) {
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
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'fcl' => 'FCL', 'lcl' => 'LCL',
                        'truck' => 'Truck', 'towing' => 'Towing', 'car_carrier' => 'Car Carrier',
                        default => $state ?: '-',
                    })
                    ->badge()
                    ->toggleable(),

                TextColumn::make('cargo_type')
                    ->label('Muatan')
                    // ⇩⇩ FIX: konversi enum ke string dulu
                    ->getStateUsing(fn ($record) =>
                        $record->cargo_type instanceof CargoType
                            ? $record->cargo_type->value
                            : (string) $record->cargo_type
                    )
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        CargoType::Vehicle->value => 'Unit Kendaraan',
                        CargoType::General->value => 'General Cargo',
                        default => $state ?: '-',
                    })
                    ->badge()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) =>
                        $record->status instanceof ShipmentStatus
                            ? $record->status->value
                            : (string) $record->status
                    )
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'pickup' => 'Pickup',
                        'transit' => 'Transit',
                        'delivered' => 'Delivered',
                        'hold' => 'Hold',
                        'cancelled' => 'Cancelled',
                        default => $state ? ucfirst($state) : '-',
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

                TextColumn::make('updated_at')->label('Diubah')->since()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'pickup' => 'Pickup',
                        'transit' => 'Transit',
                        'delivered' => 'Delivered',
                        'hold' => 'Hold',
                        'cancelled' => 'Cancelled',
                    ])->native(false),

                Filter::make('in_progress')
                    ->label('In Progress')
                    ->query(fn (Builder $q) => $q->whereIn('status', ShipmentStatus::inProgress()))
                    ->toggle(),

                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')->options([
                        ServiceType::SeaFreight->value   => 'Sea Freight',
                        ServiceType::LandTrucking->value => 'Trucking Darat',
                        ServiceType::CarCarrier->value   => 'Car Carrier / Towing',
                    ])->native(false),

                SelectFilter::make('origin_office_id')
                    ->label('Kantor Asal')->relationship('originOffice', 'name')->searchable()->preload(),

                SelectFilter::make('destination_office_id')
                    ->label('Kantor Tujuan')->relationship('destinationOffice', 'name')->searchable()->preload(),
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

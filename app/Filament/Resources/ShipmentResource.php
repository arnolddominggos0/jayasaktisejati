<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;

// Forms
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;

// Tables
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\ShipmentStatus;

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
        return $form
            ->schema([
                // A. Data Customer & Dokumen
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
                            ->options(['sppb' => 'SPPB', 'do' => 'DO', 'lain' => 'Lainnya'])
                            ->native(false)->required()->columnSpan(3),

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
                            ->label('Lampiran Dokumen (SPPB/DO/Surat Jalan, Foto, dll.)')
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

                // B. Informasi Rute & Moda
                Section::make('B. Informasi Rute & Moda')
                    ->columns(12)
                    ->schema([
                        ToggleButtons::make('mode')
                            ->label('Moda Pengiriman *')
                            ->options(['sea' => 'Laut', 'land' => 'Darat (CC/Towing)'])
                            ->inline()->required()->columnSpan(12),

                        Select::make('origin_office_id')
                            ->label('Asal (Depo/City) *')
                            ->relationship('originOffice', 'name')
                            ->searchable()->preload()->required()
                            ->helperText('cth: Jakarta')->columnSpan(6),

                        Select::make('destination_office_id')
                            ->label('Tujuan (Depo/City) *')
                            ->relationship('destinationOffice', 'name')
                            ->searchable()->preload()->required()
                            ->helperText('cth: Manado')->columnSpan(6),

                        // LAUT
                        Group::make()->columnSpan(12)->columns(12)
                            ->visible(fn(callable $get) => $get('mode') === 'sea')
                            ->schema([
                                Placeholder::make('mode_badge_sea')
                                    ->content('Moda: Laut')
                                    ->extraAttributes(['class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700'])
                                    ->columnSpan(12),

                                TextInput::make('vessel_name')->label('Nama Kapal')->placeholder('cth: KM Tanto')->maxLength(100)->columnSpan(4),
                                TextInput::make('voyage')->label('Voyage/Trip')->placeholder('cth: VY123')->maxLength(50)->columnSpan(4),
                                DatePicker::make('etd')->label('ETD (Rencana Berangkat)')->native(false)->columnSpan(4),
                                DatePicker::make('eta')->label('ETA (Perkiraan Tiba)')->native(false)->columnSpan(4),
                                TextInput::make('pol')->label('POL — Pelabuhan Muat')->placeholder('cth: Tj. Priok')->maxLength(100)->columnSpan(4),
                                TextInput::make('pod')->label('POD — Pelabuhan Bongkar')->placeholder('cth: Bitung')->maxLength(100)->columnSpan(4),
                            ]),

                        // DARAT
                        Group::make()->columnSpan(12)->columns(12)
                            ->visible(fn(callable $get) => $get('mode') === 'land')
                            ->schema([
                                Placeholder::make('mode_badge_land')
                                    ->content('Moda: Darat (CC/Towing)')
                                    ->extraAttributes(['class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-amber-100 text-amber-700'])
                                    ->columnSpan(12),

                                Select::make('vehicle_type')
                                    ->label('Jenis Armada')
                                    ->options(['car_carrier' => 'Car Carrier (CC)', 'towing' => 'Towing', 'truck' => 'Truck / Wingbox'])
                                    ->native(false)->columnSpan(4),

                                TextInput::make('vehicle_plate')->label('No. Polisi Armada')->placeholder('cth: B 1234 XX')->maxLength(20)->columnSpan(4),
                                DatePicker::make('pickup_date')->label('Tanggal Pickup (estimasi)')->native(false)->columnSpan(4),

                                TextInput::make('driver_name')->label('Nama Supir')->placeholder('Opsional')->maxLength(100)->columnSpan(6),
                                TextInput::make('driver_phone')->label('No. HP Supir')->placeholder('Opsional')->tel()->maxLength(20)->columnSpan(6),
                            ]),
                    ]),

                // C. Konfirmasi
                Section::make('C. Konfirmasi')
                    ->columns(12)
                    ->schema([
                        Checkbox::make('confirm_is_true')
                            ->label('Data sudah benar & sesuai dokumen.')
                            ->columnSpan(12)
                            ->accepted(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer.name')
                    ->badge()
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('originOffice.name')
                    ->badge()
                    ->label('Asal')
                    ->sortable(),

                TextColumn::make('destinationOffice.name')
                    ->badge()
                    ->label('Tujuan')
                    ->sortable(),

                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->getStateUsing(function ($record) {
                        $state = $record->service_type;
                        return $state instanceof \BackedEnum ? (string) $state->value : (string) $state;
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'sea_freight'   => 'Sea Freight',
                        'land_trucking' => 'Trucking',
                        'car_carrier'   => 'Car Carrier',
                        default         => $state,
                    })
                    ->badge()
                    ->colors([
                        'info'    => ['sea_freight', 'air_cargo'],
                        'warning' => ['land_trucking', 'car_carrier'],
                    ])
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        $record->status instanceof ShipmentStatus ? $record->status->value : (string) $record->status
                    )
                    ->colors([
                        'gray'    => ['draft'],
                        'warning' => ['pending', 'hold'],
                        'info'    => ['pickup', 'transit'],
                        'success' => ['delivered'],
                        'danger'  => ['cancelled'],
                    ])
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
                    ->sortable(),

                TextColumn::make('etd')
                    ->label('ETD')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime('d M Y H:i')
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

                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        'sea_freight'   => 'Sea Freight',
                        'land_trucking' => 'Trucking Darat',
                        'car_carrier'   => 'Car Carrier / Towing',
                        'air_cargo'     => 'Air Cargo',
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
            ])
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

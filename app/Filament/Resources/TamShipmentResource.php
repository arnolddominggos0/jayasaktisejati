<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TamShipmentResource\Pages;
use App\Models\TamShipment;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TamShipmentResource extends Resource
{
    protected static ?string $model = TamShipment::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Unit TAM')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('vin')
                            ->label('VIN / No. Rangka')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('engine_no')
                            ->label('No. Mesin')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('model')
                            ->label('Model / Tipe')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('color')
                            ->label('Warna')
                            ->maxLength(50),
                    ]),

                Forms\Components\Section::make('Kapal & Dokumen')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('shipping_schedule_id')
                            ->label('Jadwal Kapal')
                            ->relationship('shippingSchedule', 'id')
                            ->getOptionLabelFromRecordUsing(function (ShippingSchedule $record): string {
                                $jss    = $record->jss ?: ('SCH-' . $record->id);
                                $voyage = $record->voyage_no ?: '-';
                                $vessel = $record->vessel->name ?? $record->vessel_name ?? '-';
                                $etd    = $record->etd?->format('d M') ?? '-';

                                return $jss . ' • ' . $voyage . ' • ' . $vessel . ' • ETD ' . $etd;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('do_number')
                            ->label('No. DO / Surat Jalan')
                            ->maxLength(100),
                    ]),

                Forms\Components\Section::make('Status & Timeline')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'booking'   => 'Booking',
                                'gate_in'   => 'Gate In Depo',
                                'loaded'    => 'Loaded ke Kapal',
                                'arrived'   => 'Arrived',
                                'delivered' => 'Delivered',
                            ])
                            ->required(),

                        Forms\Components\DateTimePicker::make('gate_in_at')->label('Gate In')->nullable(),
                        Forms\Components\DateTimePicker::make('loaded_at')->label('Loaded')->nullable(),
                        Forms\Components\DateTimePicker::make('arrived_at')->label('Arrived')->nullable(),
                        Forms\Components\DateTimePicker::make('delivered_at')->label('Delivered')->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shippingSchedule.jss')
                    ->label('Jadwal Kapal')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vin')
                    ->label('VIN')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'booking',
                        'info'      => 'gate_in',
                        'warning'   => 'loaded',
                        'primary'   => 'arrived',
                        'success'   => 'delivered',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTamShipments::route('/'),
            'create' => Pages\CreateTamShipment::route('/create'),
            'edit'   => Pages\EditTamShipment::route('/{record}/edit'),
        ];
    }
}

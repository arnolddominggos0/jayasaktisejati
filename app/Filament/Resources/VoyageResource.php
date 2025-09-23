<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoyageResource\Pages\CreateVoyage;
use App\Filament\Resources\VoyageResource\Pages\EditVoyage;
use App\Filament\Resources\VoyageResource\Pages\ListVoyages;
use App\Models\Voyage;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Jadwal Kapal';
    protected static ?string $pluralLabel = 'Jadwal Kapal';
    protected static ?string $navigationIcon = 'heroicon-m-calendar-days';
    protected static ?string $modelLabel = 'Jadwal Kapal';
    protected static ?int    $navigationSort  = 10;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('shipping_line_id')->relationship('shippingLine', 'name')->label('Shipping Line')->required(),
            Select::make('vessel_id')->relationship('vessel', 'name')->label('Vessel')->required(),
            TextInput::make('voyage_no')->label('Voyage No')->required(),
            Select::make('port_from_id')
                ->relationship('portFrom', 'name')
                ->label('POL')
                ->required()
                ->searchable()
                ->preload(),

            Select::make('port_to_id')
                ->relationship('portTo', 'name')
                ->label('POD')
                ->required()
                ->searchable()
                ->preload(),

            DatePicker::make('etd')->label('ETD')->required(),
            DatePicker::make('eta')->label('ETA'),
            TextInput::make('service')->label('Service')->maxLength(50),
        ])->columns(2);
    }

    public static function table(Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('shippingLine.name')->label('Line'),
            TextColumn::make('vessel.name')->label('Vessel'),
            TextColumn::make('voyage_no')->label('Voyage'),
            TextColumn::make('portFrom.code')->label('POL')->badge(),
            TextColumn::make('portTo.code')->label('POD')->badge(),
            TextColumn::make('etd')->date()->label('ETD'),
            TextColumn::make('eta')->date()->label('ETA'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVoyages::route('/'),
            'create' => CreateVoyage::route('/create'),
            'edit'   => EditVoyage::route('/{record}/edit'),
        ];
    }
}

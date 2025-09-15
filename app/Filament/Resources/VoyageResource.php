<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoyageResource\Pages;
use App\Models\Voyage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Jadwal Kapal (Voyage)';
    protected static ?string $pluralLabel = 'Jadwal Kapal (Voyage)';
    protected static ?string $navigationIcon = 'heroicon-m-calendar';
    protected static ?string $modelLabel = 'Jadwal Kapal (Voyage)';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('shipping_line_id')->relationship('shippingLine','name')->label('Shipping Line')->required(),
            Forms\Components\Select::make('vessel_id')->relationship('vessel','name')->label('Vessel')->required(),
            Forms\Components\TextInput::make('voyage_no')->label('Voyage No')->required(),
            Forms\Components\Select::make('port_from_id')->relationship('portFrom','name')->label('POL')->required()->searchable(),
            Forms\Components\Select::make('port_to_id')->relationship('portTo','name')->label('POD')->required()->searchable(),
            Forms\Components\DatePicker::make('etd')->label('ETD')->required(),
            Forms\Components\DatePicker::make('eta')->label('ETA'),
            Forms\Components\TextInput::make('service')->label('Service')->maxLength(50),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('shippingLine.name')->label('Line'),
            Tables\Columns\TextColumn::make('vessel.name')->label('Vessel'),
            Tables\Columns\TextColumn::make('voyage_no')->label('Voyage'),
            Tables\Columns\TextColumn::make('portFrom.code')->label('POL')->badge(),
            Tables\Columns\TextColumn::make('portTo.code')->label('POD')->badge(),
            Tables\Columns\TextColumn::make('etd')->date()->label('ETD'),
            Tables\Columns\TextColumn::make('eta')->date()->label('ETA'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'edit'   => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }
}

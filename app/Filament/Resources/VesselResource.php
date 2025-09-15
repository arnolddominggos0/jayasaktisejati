<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VesselResource\Pages;
use App\Models\Vessel;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class VesselResource extends Resource
{
    protected static ?string $model = Vessel::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Kapal';
    protected static ?string $pluralLabel = 'Kapal';
    protected static ?string $navigationIcon = 'heroicon-m-rocket-launch';
    protected static ?string $modelLabel = 'Kapal';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama Kapal')->required(),
            Forms\Components\Select::make('shipping_line_id')->relationship('shippingLine','name')->label('Shipping Line')->required(),
            Forms\Components\TextInput::make('imo')->label('IMO')->maxLength(20),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama Kapal')->searchable(),
            TextColumn::make('shippingLine.name')->label('Shipping Line'),
            TextColumn::make('imo')->label('IMO'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVessels::route('/'),
            'create' => Pages\CreateVessel::route('/create'),
            'edit'   => Pages\EditVessel::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingLineResource\Pages;
use App\Models\ShippingLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingLineResource extends Resource
{
    protected static ?string $model = ShippingLine::class;
    protected static ?string $navigationGroup = 'Master Data Shipment';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Pelayaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(20),
            Forms\Components\TextInput::make('name')->required()->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('vessels_count')->counts('vessels')->label('Jumlah Kapal'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingLines::route('/'),
            'create' => Pages\CreateShippingLine::route('/create'),
            'edit' => Pages\EditShippingLine::route('/{record}/edit'),
        ];
    }
}

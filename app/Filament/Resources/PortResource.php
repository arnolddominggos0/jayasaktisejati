<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortResource\Pages;
use App\Models\Port;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PortResource extends Resource
{
    protected static ?string $model = Port::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Pelabuhan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(20),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('city'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('city'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPorts::route('/'),
            'create' => Pages\CreatePort::route('/create'),
            'edit' => Pages\EditPort::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortResource\Pages;
use App\Models\Port;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortResource extends Resource
{
    protected static ?string $model = Port::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Pelabuhan';
    protected static ?string $pluralLabel     = 'Pelabuhan';
    protected static ?string $modelLabel      = 'Pelabuhan';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(20),
            TextInput::make('name')->required(),
            TextInput::make('city'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->sortable()->searchable(),
            TextColumn::make('name')->sortable()->searchable(),
            TextColumn::make('city'),
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

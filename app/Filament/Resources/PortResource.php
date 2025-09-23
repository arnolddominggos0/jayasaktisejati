<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortResource\Pages;
use App\Models\Port;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class PortResource extends Resource
{
    protected static ?string $model = Port::class;

    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Pelabuhan';
    protected static ?string $pluralLabel = 'Pelabuhan';
    protected static ?string $navigationIcon = 'heroicon-m-map-pin';
    protected static ?string $modelLabel = 'Pelabuhan';
    protected static ?int    $navigationSort  = 50;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\TextInput::make('city')->label('Kota'),
            Forms\Components\TextInput::make('country')->label('Negara')->default('ID'),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->badge()->label('Kode'),
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('city')->label('Kota'),
            Tables\Columns\TextColumn::make('country')->label('Negara'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPorts::route('/'),
            'create' => Pages\CreatePort::route('/create'),
            'edit'   => Pages\EditPort::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingLineResource\Pages;
use App\Models\ShippingLine;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class ShippingLineResource extends Resource
{
    protected static ?string $model = ShippingLine::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Pelayaran';
    protected static ?string $pluralLabel = 'Pelayaran';
    protected static ?string $navigationIcon = 'heroicon-m-arrow-path';
    protected static ?string $modelLabel = 'Pelayaran';
    protected static ?int    $navigationSort  = 30;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\TextInput::make('contact')->label('Kontak'),
            Forms\Components\TextInput::make('phone')->label('Telepon'),
            Forms\Components\TextInput::make('email')->email()->label('Email'),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->badge()->label('Kode'),
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('phone')->label('Telepon'),
            Tables\Columns\TextColumn::make('email')->label('Email'),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingLines::route('/'),
            'create' => Pages\CreateShippingLine::route('/create'),
            'edit'   => Pages\EditShippingLine::route('/{record}/edit'),
        ];
    }
}

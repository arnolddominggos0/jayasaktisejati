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

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Shipping Line';
    protected static ?string $pluralLabel = 'Shipping Line';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $modelLabel = 'Shipping Line';
    protected static ?int    $navigationSort  = 30;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Kode')
                ->unique(ignoreRecord: true)
                ->maxLength(20),
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(120),
            Forms\Components\TextInput::make('contact_name')->label('PIC')->maxLength(120),
            Forms\Components\TextInput::make('contact_phone')->label('Telepon')->maxLength(60),
            Forms\Components\TextInput::make('email')->email()->label('Email')->maxLength(120),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->badge()->label('Kode')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('contact_name')->label('PIC')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('contact_phone')->label('Telepon')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'contact_name', 'contact_phone', 'email'];
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

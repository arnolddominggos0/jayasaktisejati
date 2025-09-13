<?php

namespace App\Filament\Resources;

use App\Enums\ArmadaType;
use App\Filament\Resources\ArmadaResource\Pages;
use App\Models\Armada;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class ArmadaResource extends Resource
{
    protected static ?string $model = Armada::class;
    protected static ?string $navigationGroup = 'Pengiriman';
    protected static ?string $navigationIcon = 'heroicon-m-truck';
    protected static ?string $navigationLabel = 'Manajemen Armada & MP';
    protected static ?string $pluralModelLabel = 'Manejemen Armada & MP';
    protected static ?string $modelLabel = 'Manejemen Armada & MP';
    protected static ?int $navigationSort = 20;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')
                    ->options(collect(ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
                Forms\Components\TextInput::make('plate_number'),
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('capacity')->numeric(),
                Forms\Components\Select::make('branch_id')->relationship('branch', 'name')->required(),
                Forms\Components\Select::make('manpowers')
                    ->multiple()
                    ->relationship('manpowers', 'name'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->label('Kode'),
                Tables\Columns\TextColumn::make('type')->badge()->label('Tipe'),
                Tables\Columns\TextColumn::make('name')->label('Nama Armada'),
                Tables\Columns\TextColumn::make('plate_number')->label('Plat'),
                Tables\Columns\TextColumn::make('capacity')->label('Kapasitas'),
                Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArmadas::route('/'),
            'create' => Pages\CreateArmada::route('/create'),
            'edit' => Pages\EditArmada::route('/{record}/edit'),
        ];
    }
}

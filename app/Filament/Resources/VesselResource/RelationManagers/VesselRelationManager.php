<?php

namespace App\Filament\Resources\ShippingLineResource\RelationManagers;

use App\Models\Vessel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VesselsRelationManager extends RelationManager
{
    protected static string $relationship = 'vessels';
    protected static ?string $title = 'Daftar Kapal';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama Kapal')->required()->maxLength(120),
            Forms\Components\TextInput::make('imo')->label('IMO')->maxLength(30)->nullable(),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Kapal')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('imo')->label('IMO')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Kapal'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->visible(fn(Vessel $v) => $v->schedules()->count() === 0),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}

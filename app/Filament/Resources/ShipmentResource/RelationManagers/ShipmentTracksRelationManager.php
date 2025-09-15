<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'shipmentTracks'; // <- penting

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Judul')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Dibuat'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

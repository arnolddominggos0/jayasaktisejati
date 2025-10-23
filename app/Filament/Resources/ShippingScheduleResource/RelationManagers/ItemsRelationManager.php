<?php

namespace App\Filament\Resources\ShippingScheduleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Detail Jadwal (TAM)';
    protected static ?string $recordTitleAttribute = 'vessel_name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('etd')->label('ETD')->required(),
            Forms\Components\DateTimePicker::make('eta')->label('ETA')->required(),
            Forms\Components\TextInput::make('cargo_plan')->label('Cargo Plan')->numeric()->minValue(0)->nullable(),
            Forms\Components\TextInput::make('vessel_name')->label('Vessel')->maxLength(120)->required(),
            Forms\Components\TextInput::make('vessel_capacity')->label('Capacity')->numeric()->nullable(),
            Forms\Components\TextInput::make('voyage_no')->label('Voyage No')->maxLength(50)->nullable(),
            Forms\Components\TextInput::make('jss')->label('JSS')->maxLength(50)->nullable(),
            Forms\Components\TextInput::make('lts')->label('LTS')->maxLength(50)->nullable(),
            Forms\Components\TextInput::make('dwelling')->label('Dwelling')->numeric()->nullable(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime('d M'),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M'),
                Tables\Columns\TextColumn::make('cargo_plan')->label('Plan')->alignCenter(),
                Tables\Columns\TextColumn::make('vessel_name')->label('Vessel')->searchable(),
                Tables\Columns\TextColumn::make('vessel_capacity')->label('Cap')->alignCenter()->toggleable(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voy')->toggleable(),
                Tables\Columns\TextColumn::make('jss')->label('JSS')->toggleable(),
                Tables\Columns\TextColumn::make('lts')->label('LTS')->toggleable(),
                Tables\Columns\TextColumn::make('dwelling')->label('Dw')->alignCenter()->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}

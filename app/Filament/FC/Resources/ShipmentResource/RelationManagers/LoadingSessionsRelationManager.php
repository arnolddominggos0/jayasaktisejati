<?php

namespace App\Filament\FC\Resources\ShipmentResource\RelationManagers;

use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoadingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'loadingSessions';

    protected static ?string $title = 'Loading Sessions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code'),
                Tables\Columns\BadgeColumn::make('operation_type')
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('progress')
                    ->formatStateUsing(fn ($record) => $record->getProgressPercentage() . '%'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Loading Session')
                    ->url(fn () => route('filament.fc.resources.loading-sessions.create', ['shipment_id' => $this->getOwnerRecord()->id]))
                    ->openUrlInNewTab(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.fc.resources.loading-sessions.view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}

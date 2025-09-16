<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Enums\TrackStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'tracks';
    protected static ?string $title = 'Riwayat Tracking';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(collect(TrackStatus::order())
                    ->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                ->required(),
            Forms\Components\TextInput::make('location')->label('Lokasi'),
            Forms\Components\Textarea::make('note')->label('Catatan'),
            Forms\Components\DateTimePicker::make('tracked_at')->default(now()),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('status')
                ->label('Status')->badge()
                ->formatStateUsing(fn($state) => TrackStatus::from($state)->label()),
            Tables\Columns\TextColumn::make('location')->label('Lokasi'),
            Tables\Columns\TextColumn::make('note')->label('Catatan')->limit(30),
            Tables\Columns\TextColumn::make('tracked_at')->dateTime()->label('Waktu'),
            Tables\Columns\TextColumn::make('user.name')->label('Diupdate oleh'),
        ])
            ->defaultSort('tracked_at', 'desc')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

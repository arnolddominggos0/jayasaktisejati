<?php

namespace App\Filament\Resources\TamShipmentResource\RelationManagers;

use App\Enums\TrackStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TracksRelationManager extends RelationManager
{
    protected static string $relationship = 'tracks';

    protected static ?string $title = 'Tracking';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(
                    collect(TrackStatus::cases())
                        ->mapWithKeys(
                            fn($case) => [
                                $case->value => method_exists($case, 'label')
                                    ? $case->label()
                                    : ucfirst(str_replace('_', ' ', $case->value)),
                            ]
                        )
                        ->toArray()
                )
                ->required(),

            Forms\Components\DateTimePicker::make('tracked_at')
                ->label('Waktu')
                ->required(),

            Forms\Components\TextInput::make('location')
                ->label('Lokasi')
                ->maxLength(255),

            Forms\Components\Textarea::make('note')
                ->label('Catatan')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracked_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof TrackStatus && method_exists($state, 'label')) {
                            return $state->label();
                        }

                        if ($state instanceof TrackStatus) {
                            return ucfirst(str_replace('_', ' ', $state->value));
                        }

                        return ucfirst(str_replace('_', ' ', (string) $state));
                    }),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(40),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Tracking'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}

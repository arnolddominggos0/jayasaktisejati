<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Enums\TrackStatus;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Enum as EnumRule;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'tracks';
    protected static ?string $title = 'Riwayat Tracking';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('status')
                ->label('Status')
                ->options(
                    collect(TrackStatus::order())
                        ->mapWithKeys(fn(TrackStatus $s) => [$s->value => $s->label()])
                )
                ->afterStateHydrated(function ($component, $state) {
                    $component->state($state instanceof TrackStatus ? $state->value : $state);
                })
                ->dehydrateStateUsing(fn($state) => $state instanceof TrackStatus ? $state->value : $state)
                ->rule(new EnumRule(TrackStatus::class))
                ->required(),
            TextInput::make('location')->label('Lokasi')->maxLength(120),
            Textarea::make('note')->label('Catatan')->rows(3),
            DateTimePicker::make('tracked_at')->label('Waktu')->default(now()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('status')
                ->label('Status')->badge()
                ->formatStateUsing(function ($state) {
                    return $state?->label() ?? (string)$state;
                }),
            TextColumn::make('location')->label('Lokasi'),
            TextColumn::make('note')->label('Catatan')->limit(40),
            TextColumn::make('tracked_at')->dateTime()->label('Waktu'),
            TextColumn::make('user.name')->label('Diupdate oleh'),
        ])
            ->defaultSort('tracked_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn() => auth_user()?->hasRole('super_admin')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth_user()?->hasRole('super_admin')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth_user()?->hasRole('super_admin')),
            ]);
    }
}

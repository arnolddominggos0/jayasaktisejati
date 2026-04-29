<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'tracks';
    protected static ?string $title = 'Timeline';
    protected static ?string $recordTitleAttribute = 'status';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(function () {
                    /** @var \App\Models\Shipment $shipment */
                    $shipment = $this->getOwnerRecord();
                    $order = \App\Enums\TrackStatus::orderForMode($shipment?->mode);
                    $out = [];
                    foreach ($order as $s) $out[$s->value] = $s->label();
                    return $out;
                })
                ->required()
                ->native(false),

            Forms\Components\DateTimePicker::make('tracked_at')
                ->label('Waktu')
                ->seconds(false)
                ->required(),

            Forms\Components\TextInput::make('location')
                ->label('Lokasi')
                ->maxLength(120),

            Forms\Components\Textarea::make('note')
                ->label('Catatan')
                ->rows(3),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn($state) => \App\Enums\TrackStatus::normalize($state)?->label() ?? (string) $state)
                ->badge(),

            TextColumn::make('tracked_at')->label('Waktu')->dateTime('d M Y H:i')->placeholder('—'),
            TextColumn::make('location')->label('Lokasi')->limit(30)->wrap(),
            TextColumn::make('note')->label('Catatan')->limit(40)->wrap(),
            TextColumn::make('updated_at')->label('Update')->since()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->defaultSort('tracked_at', 'asc');
    }
}

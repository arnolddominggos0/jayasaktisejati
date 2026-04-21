<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Enums\ShipmentMode;
use App\Enums\TrackStatus;
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
                ->native(false)
                ->live(),

            Forms\Components\DateTimePicker::make('tracked_at')
                ->label('Waktu')
                ->seconds(false)
                ->required(),

            Forms\Components\TextInput::make('location')
                ->label('Lokasi')
                ->maxLength(120),

            Forms\Components\Textarea::make('note')
                ->label('Catatan')
                ->rows(3)
                ->required(function (Forms\Get $get) {
                    /** @var \App\Models\Shipment $shipment */
                    $shipment = $this->getOwnerRecord();
                    $isSea = ($shipment?->mode?->value ?? $shipment?->mode) === 'sea';

                    if (! $isSea) {
                        return false;
                    }

                    $statusValue = $get('status');
                    if (! $statusValue) {
                        return false;
                    }

                    $status = TrackStatus::tryFrom($statusValue);
                    return $status?->requiresNote() ?? false;
                })
                ->minLength(function (Forms\Get $get) {
                    /** @var \App\Models\Shipment $shipment */
                    $shipment = $this->getOwnerRecord();
                    $isSea = ($shipment?->mode?->value ?? $shipment?->mode) === 'sea';

                    if (! $isSea) {
                        return null;
                    }

                    $statusValue = $get('status');
                    if (! $statusValue) {
                        return null;
                    }

                    $status = TrackStatus::tryFrom($statusValue);
                    return $status?->requiresNote() ? 10 : null;
                })
                ->validationMessages([
                    'required' => 'Status ini memerlukan catatan (minimal 10 karakter).',
                    'min' => 'Catatan minimal 10 karakter untuk status ini.',
                ]),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        $isSea = fn() => $this->getOwnerRecord()?->mode === ShipmentMode::Sea;

        return $table->columns([
            TextColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn($state) => \App\Enums\TrackStatus::normalize($state)?->label() ?? (string) $state)
                ->badge(),

            TextColumn::make('tracked_at')->label('Waktu')->dateTime('d M Y H:i')->placeholder('—'),

            // Sea-specific: Vessel departure timestamp
            TextColumn::make('actual_berthing_time_at')
                ->label('Berthing')
                ->dateTime('d M Y H:i')
                ->placeholder('—')
                ->visible($isSea)
                ->toggleable(isToggledHiddenByDefault: true),

            // Sea-specific: Loading time
            TextColumn::make('actual_loading_time_at')
                ->label('Loading')
                ->dateTime('d M Y H:i')
                ->placeholder('—')
                ->visible($isSea)
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('location')->label('Lokasi')->limit(30)->wrap(),
            TextColumn::make('note')->label('Catatan')->limit(40)->wrap(),
            TextColumn::make('updated_at')->label('Update')->since()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->defaultSort('tracked_at', 'asc');
    }
}

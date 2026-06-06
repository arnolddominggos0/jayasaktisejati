<?php

namespace App\Filament\Resources\VesselPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VesselPlanItemRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Draft Jadwal Kapal';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Kapal')
                ->schema([
                    Select::make('shipping_line_id')
                        ->label('Pelayaran')
                        ->relationship('shippingLine', 'name')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($set) => $set('vessel_id', null)),

                    Select::make('vessel_id')
                        ->label('Kapal')
                        ->relationship(
                            'vessel',
                            'name',
                            fn($query, Get $get) =>
                            $query->where('shipping_line_id', $get('shipping_line_id'))
                        )
                        ->required()
                        ->disabled(fn(Get $get) => blank($get('shipping_line_id'))),
                ])
                ->columns(2),

            Forms\Components\Section::make('Jadwal')
                ->schema([
                    DateTimePicker::make('planned_etd')
                        ->label('ETD (Rencana)')
                        ->required()
                        ->native(false),

                    DateTimePicker::make('planned_etb')
                        ->label('ETB (Rencana Sandar)')
                        ->native(false)
                        ->helperText('Opsional. Waktu estimasi kapal sandar.'),

                    DateTimePicker::make('planned_eta')
                        ->label('ETA (Rencana)')
                        ->required()
                        ->native(false),

                    TextInput::make('cargo_plan')
                        ->label('Rencana Muatan (unit)')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Opsional. Jumlah unit yang direncanakan.'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Informasi Voyage')
                ->schema([
                    TextInput::make('voyage_no')
                        ->label('No Voyage')
                        ->maxLength(50)
                        ->helperText('Nomor voyage dari shipping line. Kosongkan untuk auto-generate saat finalisasi.'),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shippingLine.name')
                    ->label('Pelayaran'),

                TextColumn::make('vessel.name')
                    ->label('Kapal'),

                TextColumn::make('voyage_no')
                    ->label('No Voyage')
                    ->placeholder('—')
                    ->copyable(),

                TextColumn::make('planned_etd')
                    ->label('ETD')
                    ->dateTime(),

                TextColumn::make('planned_etb')
                    ->label('ETB')
                    ->dateTime()
                    ->placeholder('—'),

                TextColumn::make('planned_eta')
                    ->label('ETA')
                    ->dateTime(),

                TextColumn::make('cargo_plan')
                    ->label('Muatan (unit)')
                    ->placeholder('—'),

                TextColumn::make('planned_sailing')
                    ->label('Sailing (hari)')
                    ->getStateUsing(function ($record) {
                        if (!$record->planned_etd || !$record->planned_eta) {
                            return '—';
                        }

                        return $record->planned_etd->diffInDays($record->planned_eta) . ' hari';
                    }),

                TextColumn::make('etd_gap')
                    ->label('ETD Gap')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $plan = $this->getOwnerRecord();
                        if (! $plan) return '—';

                        $gap = $plan->etdGaps()[$record->id] ?? null;
                        return $gap === null ? '—' : "{$gap} hari";
                    })
                    ->color(function ($record) {
                        $plan = $this->getOwnerRecord();
                        if (! $plan) return null;

                        $gap = $plan->etdGaps()[$record->id] ?? null;
                        return $gap !== null && $gap > 6 ? 'danger' : 'success';
                    }),
            ])

            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),
            ])

            ->defaultSort('planned_etd');
    }
}
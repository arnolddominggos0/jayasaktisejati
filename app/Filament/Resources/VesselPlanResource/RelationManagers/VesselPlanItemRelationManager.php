<?php

namespace App\Filament\Resources\VesselPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

                    DateTimePicker::make('planned_eta')
                        ->label('ETA (Rencana)')
                        ->required()
                        ->native(false),
                ])
                ->columns(2),
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

                TextColumn::make('planned_etd')
                    ->label('ETD')
                    ->dateTime(),

                TextColumn::make('planned_eta')
                    ->label('ETA')
                    ->dateTime(),

                TextColumn::make('etd_gap')
                    ->label('ETD Gap (hari)')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $plan = $this->getOwnerRecord();
                        if (! $plan) {
                            return '—';
                        }

                        $gap = $plan->etdGaps()[$record->id] ?? null;
                        return $gap === null ? '—' : "{$gap} hari";
                    })
                    ->color(function ($record) {
                        $plan = $this->getOwnerRecord();
                        if (! $plan) {
                            return null;
                        }

                        $gap = $plan->etdGaps()[$record->id] ?? null;
                        return $gap !== null && $gap > 6 ? 'danger' : null;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(
                        fn() =>
                        $this->getOwnerRecord()?->isEditable() ?? false
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn() =>
                        $this->getOwnerRecord()?->isEditable() ?? false
                    ),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn() =>
                        $this->getOwnerRecord()?->isEditable() ?? false
                    ),
            ])
            ->defaultSort('planned_etd');
    }
}

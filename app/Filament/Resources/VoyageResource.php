<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoyageResource\Pages\ListVoyages;
use App\Filament\Resources\VoyageResource\Pages\ViewVoyage;
use App\Filament\Resources\VoyageResource\RelationManagers\PlansRelationManager;
use App\Models\Voyage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Jadwal Kapal';
    protected static ?string $navigationIcon = 'heroicon-m-rocket-launch';
    protected static ?int $navigationSort = 11;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->query(Voyage::query()->onlyFinal())
            ->columns([
                Tables\Columns\TextColumn::make('plan_etd')->label('ETD')->state(fn($record) => $record->plan_etd)->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('plan_eta')->label('ETA')->state(fn($record) => $record->plan_eta)->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('shippingLine.name')->label('Line')->wrap(),
                Tables\Columns\TextColumn::make('vessel.name')->label('Vessel')->wrap(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->searchable(),
                Tables\Columns\TextColumn::make('portFrom.code')->label('POL')->badge(),
                Tables\Columns\TextColumn::make('portTo.code')->label('POD')->badge(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
            ]);
    }

    public static function getRelations(): array
    {
        return [PlansRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoyages::route('/'),
            'view' => ViewVoyage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false;
    }
    public static function canDelete($record): bool
    {
        return false;
    }
}

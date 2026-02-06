<?php

namespace App\Filament\Resources;

use App\Models\VesselPlan;
use App\Enums\VesselPlanStatus;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\VesselPlanResource\Pages;
use App\Filament\Resources\VesselPlanResource\RelationManagers\VesselPlanItemRelationManager;

class VesselPlanResource extends Resource
{
    protected static ?string $model = VesselPlan::class;

    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationLabel = 'Rencana Jadwal Kapal';
    protected static ?string $pluralLabel     = 'Rencana Jadwal Kapal';
    protected static ?string $modelLabel      = 'Rencana Jadwal Kapal';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days'; 

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('F Y'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jumlah Jadwal'),

                TextColumn::make('status_sop')
                    ->label('Status SOP')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->sopStatus()['label'])
                    ->color(fn($record) => $record->sopStatus()['color']),

            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
            ])
            ->defaultSort('period_month', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            VesselPlanItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVesselPlans::route('/'),
            'create' => Pages\CreateVesselPlan::route('/create'),
            'edit'   => Pages\EditVesselPlan::route('/{record}/edit'),
        ];
    }
}
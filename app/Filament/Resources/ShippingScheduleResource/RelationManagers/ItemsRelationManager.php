<?php

namespace App\Filament\Resources\ShippingScheduleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Baris Jadwal';
    protected static ?string $recordTitleAttribute = 'voyage_no';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('etd')->label('ETD')->dateTime(),
                TextColumn::make('eta')->label('ETA')->dateTime(),
                TextColumn::make('shippingLine.name')->label('Line'),
                TextColumn::make('vessel.name')->label('Vessel'),
                TextColumn::make('voyage_no')->label('Voy'),
                TextColumn::make('pol.code')->label('POL')->badge(),
                TextColumn::make('pod.code')->label('POD')->badge(),
                TextColumn::make('service')->label('Service')->badge(),
                TextColumn::make('vessel_capacity')->label('Cap')->state(fn($r) => $r->vessel_capacity),
                TextColumn::make('cargo_plan')->label('Cargo Plan')->state(fn($r) => $r->cargo_plan),
            ])
            ->defaultSort('etd', 'asc')
            ->paginated(false);
    }
}

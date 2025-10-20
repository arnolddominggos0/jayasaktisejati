<?php

namespace App\Filament\Resources\ShippingScheduleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
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
                TextColumn::make('vessel_capacity')->label('Capacity')->state(fn($r) => $r->vessel_capacity),
                TextColumn::make('voyage_no')->label('Voyage No'),
                TextColumn::make('jss')->label('JSS')->state(fn($r) => $r->jss),
                TextColumn::make('lts')->label('LTS')->state(fn($r) => $r->lts),
                TextColumn::make('dwelling')->label('Dwelling')->state(fn($r) => $r->dwelling),
                TextColumn::make('pol.code')->label('POL')->badge(),
                TextColumn::make('pod.code')->label('POD')->badge(),
                TextColumn::make('service')->label('Service')->badge(),
                TextColumn::make('cargo_plan')->label('Cargo Plan')->state(fn($r) => $r->cargo_plan),
            ])
            ->defaultSort('etd', 'asc')
            ->paginated(false);
    }
}

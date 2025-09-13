<?php

namespace App\Filament\Widgets;

use App\Models\ShipSchedule;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class UpcomingShipSchedules extends BaseWidget
{
    protected int|string|array $columnSpan = 2; 

    protected function getTableQuery(): Builder|Relation|null
    {
        return $this->getUpcomingShipSchedulesQuery();
    }

    protected function getUpcomingShipSchedulesQuery(): Builder
    {
        return ShipSchedule::query()
            ->where('departure_time', '>=', now())
            ->orderBy('departure_time');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('armada.name')->label('Kapal'),
            Tables\Columns\TextColumn::make('voyage_number')->label('Voyage'),
            Tables\Columns\TextColumn::make('origin_port')->label('Asal'),
            Tables\Columns\TextColumn::make('destination_port')->label('Tujuan'),
            Tables\Columns\TextColumn::make('departure_time')->dateTime()->label('Berangkat'),
            Tables\Columns\TextColumn::make('arrival_time')->dateTime()->label('Tiba'),
        ];
    }
}

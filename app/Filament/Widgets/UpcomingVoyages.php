<?php

namespace App\Filament\Widgets;

use App\Models\Voyage;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingVoyages extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Voyage::query()
            ->onlyFinal()
            ->whereNotNull('voyages.id')
            ->orderByRaw("COALESCE((select (payload->>'etd')::timestamp from voyage_plans where voyage_plans.voyage_id = voyages.id and state = 'final' order by created_at desc limit 1), now()) asc");
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('plan_etd')->dateTime()->label('ETD'),
            Tables\Columns\TextColumn::make('plan_eta')->dateTime()->label('ETA'),
            Tables\Columns\TextColumn::make('shippingLine.name')->label('Line'),
            Tables\Columns\TextColumn::make('vessel.name')->label('Vessel'),
            Tables\Columns\TextColumn::make('voyage_no')->label('Voyage'),
            Tables\Columns\TextColumn::make('portFrom.code')->label('POL')->badge(),
            Tables\Columns\TextColumn::make('portTo.code')->label('POD')->badge(),
        ];
    }
}
    
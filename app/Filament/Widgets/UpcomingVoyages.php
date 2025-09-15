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
            ->whereDate('etd', '>=', now()->toDateString())
            ->whereDate('etd', '<=', now()->addDays(30)->toDateString())
            ->orderBy('etd');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('etd')->date()->label('ETD'),
            Tables\Columns\TextColumn::make('shippingLine.name')->label('Line'),
            Tables\Columns\TextColumn::make('vessel.name')->label('Vessel'),
            Tables\Columns\TextColumn::make('voyage_no')->label('Voyage'),
            Tables\Columns\TextColumn::make('portFrom.code')->label('POL')->badge(),
            Tables\Columns\TextColumn::make('portTo.code')->label('POD')->badge(),
        ];
    }
}

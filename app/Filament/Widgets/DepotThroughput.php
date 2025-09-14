<?php

namespace App\Filament\Widgets;

use App\Models\DepotActivity;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DepotThroughput extends BaseWidget
{
    protected int|string|array $columnSpan = 2;

    protected function getTableQuery(): Builder
    {
        return DepotActivity::query()
            ->where('date', now()->toDateString())
            ->with('depot')
            ->orderBy('depot_id');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('depot.name')->label('Depot'),
            Tables\Columns\TextColumn::make('metric')->label('Metrik')->badge(),
            Tables\Columns\TextColumn::make('value')->label('Nilai')->badge(),
            Tables\Columns\TextColumn::make('remark')->label('Catatan')->limit(30),
        ];
    }
}

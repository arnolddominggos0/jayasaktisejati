<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Filament\Tables;
use App\Models\ShippingSchedule;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Dom\Text;

class ShippingScheduleTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public string $period;

    protected function getTableQuery(): Builder
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        return ShippingSchedule::query()
            ->with([    
                'voyage.vessel.shippingLine',
                'voyage.pol',
                'voyage.pod',
            ])
            ->whereDate('period_month', $dt->toDateString())
            ->orderBy('etd');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('jss')
                ->label('JSS')
                ->badge()
                ->copyable()
                ->searchable(),

            Tables\Columns\TextColumn::make('voyage.vessel.shippingLine.name')
                ->label('Pelayaran')
                ->sortable(),

            Tables\Columns\TextColumn::make('voyage.vessel.name')
                ->label('Kapal')
                ->sortable(),

            Tables\Columns\TextColumn::make('voyage.voyage_no')
                ->label('Voyage'),

            Tables\Columns\TextColumn::make('lane')
                ->label('Lane')
                ->getStateUsing(fn ($r) =>
                    optional($r->voyage?->pol)->code . ' → ' .
                    optional($r->voyage?->pod)->code
                ),

            Tables\Columns\TextColumn::make('etd')
                ->label('ETD')
                ->dateTime('d M Y H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('eta')
                ->label('ETA')
                ->dateTime('d M Y H:i')
                ->sortable(),
        
            Tables\Columns\TextColumn::make('state')
                ->label('Status')
                ->colors([
                    'success' => 'final',
                    'warning' => 'draft',
                ]),
        ];
    }
}

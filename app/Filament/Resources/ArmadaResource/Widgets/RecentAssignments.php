<?php

namespace App\Filament\Resources\ArmadaResource\Widgets;

use App\Models\ArmadaAssignment;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class RecentAssignments extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?int $armadaId = null;

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                ArmadaAssignment::query()->where('armada_id', $this->armadaId)->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('shipment.code')->label('Shipment'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('started_at')->since()->label('Mulai'),
            ]);
    }
}

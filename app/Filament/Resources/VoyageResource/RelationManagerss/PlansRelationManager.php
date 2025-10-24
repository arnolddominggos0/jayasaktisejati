<?php

namespace App\Filament\Resources\VoyageResource\RelationManagers;

use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PlansRelationManager extends RelationManager
{
    protected static string $relationship = 'plans';
    protected static ?string $title = 'Finalisasi Pelayaran';
    protected static ?string $recordTitleAttribute = 'state';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('state')->label('Status')->badge()->colors(['success' => 'final']),
                TextColumn::make('payload.etd')->label('ETD')->dateTime(),
                TextColumn::make('payload.eta')->label('ETA')->dateTime(),
                TextColumn::make('notes')->label('Catatan')->limit(60),
                TextColumn::make('source')->label('Sumber')->badge(),
                TextColumn::make('approval_ref')->label('Lampiran')->limit(40),
                TextColumn::make('finalized_at')->label('Finalized')->since(),
                TextColumn::make('creator.name')->label('Oleh'),
                TextColumn::make('created_at')->label('Dibuat')->since(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

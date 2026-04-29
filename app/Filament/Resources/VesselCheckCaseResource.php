<?php

namespace App\Filament\Resources;

use App\Enums\VesselCheckStatus;
use App\Filament\Resources\VesselCheckCaseResource\Pages;
use App\Models\VesselCheckCase;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VesselCheckCaseResource extends Resource
{
    protected static ?string $model = VesselCheckCase::class;

    protected static ?string $navigationLabel = 'Tindak Lanjut Perubahan Jadwal';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID Tindak Lanjut')
                    ->sortable(),

                TextColumn::make('shippingSchedule.voyage.voyage_no')
                    ->label('Voyage')
                    ->searchable(),

                TextColumn::make('opened_at')
                    ->label('Mulai Ditangani')
                    ->dateTime(),

                TextColumn::make('case_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(VesselCheckStatus $state) => $state->label())
                    ->color('info'),

                TextColumn::make('delay_flag')
                    ->label('Perubahan ETD')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'YA' : 'TIDAK')
                    ->color(fn($state) => $state ? 'danger' : 'gray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselCheckCases::route('/'),
        ];
    }
}

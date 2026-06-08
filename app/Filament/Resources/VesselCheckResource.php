<?php

namespace App\Filament\Resources;

use App\Enums\VesselCheckLogStatus;
use App\Filament\Resources\VesselCheckResource\Pages;
use App\Models\VesselCheck;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

/**
 * Vessel Check Resource — Carrier Readiness Monitoring (admin/backend view).
 *
 * Navigation: hidden from operators. Data input dilakukan dari Monitoring Kapal TAM
 * melalui modal "Readiness Check" (saveReadiness).
 *
 * Scope: semua voyage H-2/H-1, terlepas dari shipment.
 * Lihat: GenerateDailyVesselChecks untuk penjelasan scope boundary.
 */
class VesselCheckResource extends Resource
{
    protected static ?string $model = VesselCheck::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Kesiapan Kapal';
    protected static ?string $pluralLabel     = 'Kesiapan Kapal';
    protected static ?string $modelLabel      = 'Kesiapan Kapal';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?int    $navigationSort  = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_date', 'desc')
            ->defaultGroup('voyage.voyage_no')
            ->groups([
                Tables\Grouping\Group::make('voyage.voyage_no')
                    ->label('Voyage')
                    ->collapsible(),
            ])
            ->columns([

                TextColumn::make('day_code')
                    ->label('H')
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'H-1'   => 'danger',
                        'H-2'   => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('check_date')
                    ->label('Tanggal')
                    ->date('d M Y'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof VesselCheckLogStatus
                        ? $state->label()
                        : strtoupper((string) $state))
                    ->color(fn($state) => $state instanceof VesselCheckLogStatus
                        ? $state->color()
                        : 'gray'),

                TextColumn::make('delay_reason')
                    ->label('Alasan')
                    ->placeholder('—')
                    ->limit(40),

                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('—')
                    ->limit(60),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
            ])
            ->emptyStateHeading('Belum ada data pemeriksaan')
            ->emptyStateDescription('Pemeriksaan dibuat otomatis H-2/H-1 sebelum keberangkatan, atau diisi manual dari Monitoring Kapal TAM.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselChecks::route('/'),
            'view'  => Pages\ViewVesselCheck::route('/{record}'),
        ];
    }
}

git add <?php

namespace App\Filament\Resources\BriefingSessionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

/**
 * Admin panel — Stok APD per sesi briefing (READ-ONLY).
 *
 * computed_status = accessor pada model StockApdCheck (bukan kolom DB `status`).
 * Selalu gunakan computed_status untuk tampilan — jangan kolom `status` raw.
 */
class StockApdChecksRelationManager extends RelationManager
{
    protected static string  $relationship        = 'stockApdChecks';
    protected static ?string $title               = 'Stok APD';
    protected static ?string $recordTitleAttribute = 'id';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('ppe_type')
                    ->label('Jenis APD')
                    ->formatStateUsing(fn ($state) => match (strtolower((string) $state)) {
                        'helm'          => 'Helm Safety',
                        'rompi'         => 'Rompi',
                        'sepatu'        => 'Sepatu Safety',
                        'sarung_tangan' => 'Sarung Tangan',
                        default         => (string) $state,
                    }),

                TextColumn::make('stock_available')
                    ->label('Stok')
                    ->numeric()
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('required_quantity')
                    ->label('Butuh')
                    ->numeric()
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('gap')
                    ->label('Gap')
                    ->state(fn ($record) => $record->gap !== null
                        ? ($record->gap > 0 ? "+{$record->gap}" : (string) $record->gap)
                        : '—')
                    ->color(fn ($record) => match (true) {
                        $record->gap === null => 'gray',
                        $record->gap >= 0    => 'success',
                        default              => 'danger',
                    })
                    ->alignCenter(),

                TextColumn::make('computed_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record) => $record->computed_status)
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cukup'       => 'Cukup',
                        'kurang'      => 'Kurang',
                        'belum_diisi' => 'Belum Diisi',
                        default       => $state ?? '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'cukup'  => 'success',
                        'kurang' => 'danger',
                        default  => 'gray',
                    }),

                TextColumn::make('remark')
                    ->label('Catatan')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->remark),
            ])
            // Read-only — tidak ada create / edit / delete
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}

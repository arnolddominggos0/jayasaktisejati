<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Support\Carbon;

class ShipmentTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'tracks';
    protected static ?string $title = 'Riwayat Tracking';

    public function table(Table $table): Table
    {
        return $table
            ->recordClasses(fn ($record) => empty($record->tracked_at) ? 'opacity-60' : '')
            ->columns([
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof TrackStatus) return $state->label();
                        $try = TrackStatus::tryFrom((string) $state);
                        return $try ? $try->label() : (string) $state;
                    })
                    ->color(fn ($state) => match (($state instanceof TrackStatus ? $state->value : $state)) {
                        'delivered' => 'success',
                        'hold' => 'warning',
                        'cancelled' => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),

                // Inline edit waktu (tanpa formatStateUsing)
                TextInputColumn::make('tracked_at')
                    ->label('Waktu (YYYY-MM-DD HH:mm)')
                    ->placeholder('—')
                    ->extraAttributes(['class' => 'font-mono text-sm'])
                    ->rules(['nullable','date'])
                    ->afterStateUpdated(function ($state, ShipmentTrack $record) {
                        $record->tracked_at = blank($state) ? null : Carbon::parse($state);
                        $record->updated_by = Filament::auth()?->id() ?? auth()->id();
                        $record->save();
                    }),

                TextInputColumn::make('location')
                    ->label('Lokasi')
                    ->placeholder('—')
                    ->rules(['nullable','string','max:120'])
                    ->afterStateUpdated(function ($state, ShipmentTrack $record) {
                        $record->location = $state ?: null;
                        $record->updated_by = Filament::auth()?->id() ?? auth()->id();
                        $record->save();
                    }),

                TextInputColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('—')
                    ->rules(['nullable','string','max:500'])
                    ->afterStateUpdated(function ($state, ShipmentTrack $record) {
                        $record->note = $state ?: null;
                        $record->updated_by = Filament::auth()?->id() ?? auth()->id();
                        $record->save();
                    }),
            ])
            ->defaultSort('tracked_at', 'asc')
            ->paginated(false)
            ->emptyStateHeading('Belum ada timeline.')
            ->emptyStateDescription('Klik Generate Timeline untuk membuat rencana langkah otomatis.')
            ->headerActions([
                Action::make('generate_timeline')
                    ->label('Generate Timeline')
                    ->icon('heroicon-m-sparkles')
                    ->requiresConfirmation()
                    ->action(function () {
                        $s = $this->getOwnerRecord();
                        if ($s instanceof Shipment) {
                            $s->ensureTrackSkeleton();
                        }
                    })
                    ->successNotificationTitle('Timeline otomatis dibuat'),
            ])
            // nol row action, biar gak “panel kontrol kapal”
            ->actions([]);
    }
}

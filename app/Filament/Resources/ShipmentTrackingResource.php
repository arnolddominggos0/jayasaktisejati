<?php

namespace App\Filament\Resources;

use App\Enums\TrackStatus;
use App\Filament\Resources\ShipmentTrackingResource\Pages;
use App\Models\Shipment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn, ViewColumn};
use Illuminate\Database\Eloquent\Builder;

class ShipmentTrackingResource extends Resource
{
    protected static ?string $model = Shipment::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup    = 'Pengiriman';
    protected static ?string $navigationLabel    = 'Pelacakan & Monitoring';
    protected static ?string $modelLabel         = 'Pelacakan';
    protected static ?string $pluralModelLabel   = 'Pelacakan';
    protected static ?string $navigationIcon     = 'heroicon-m-map-pin';

    public static function shouldRegisterNavigation(): bool
    {
        return auth_user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => static::getEloquentQuery())
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')->badge()->copyable()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->sortable()->searchable(),

                IconColumn::make('mode')
                    ->label('Moda')
                    ->icon(fn ($state) => ($state?->value ?? $state) === 'sea' ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn ($state) => ($state?->value ?? $state) === 'sea' ? 'primary' : 'warning')
                    ->tooltip(fn ($state) => ($state?->value ?? $state) === 'sea' ? 'Laut' : 'Darat')
                    ->toggleable(),

                TextColumn::make('customer.name')->label('Customer')->badge()->toggleable(),

                TextColumn::make('route_summary')->label('Rute')->html()
                    ->getStateUsing(function (Shipment $record): string {
                        $oCity = $record->originCity->name ?? '-';
                        $dCity = $record->destinationCity->name ?? '-';
                        return "<div class='font-medium'>{$oCity} &rarr; {$dCity}</div>";
                    })
                    ->toggleable(),

                TextColumn::make('progress_count')
                    ->label('Progress')
                    ->state(function (Shipment $record) {
                        $order = TrackStatus::order();
                        $raw   = $record->latestTrack?->status;
                        $last  = $raw instanceof \BackedEnum ? $raw->value : $raw;

                        $idx = -1;
                        if ($last) {
                            $current = TrackStatus::tryFrom((string) $last);
                            if ($current) {
                                $idx = array_search($current, $order, true);
                            }
                        }
                        return ($idx + 1) . '/' . count($order);
                    })
                    ->badge()
                    ->icon('heroicon-m-bolt')
                    ->toggleable(),

                ViewColumn::make('progress_stepper')
                    ->label(' ')
                    ->view('tables.columns.tracking-progress'),

                TextColumn::make('latestTrack.status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Belum dimulai')
                    ->badge()
                    ->color(function ($state) {
                        if (! $state) return 'gray';
                        $val = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        return match ($val) {
                            TrackStatus::Delivered->value => 'success',
                            TrackStatus::Hold->value      => 'warning',
                            TrackStatus::Cancelled->value => 'danger',
                            default                       => 'primary',
                        };
                    })
                    ->toggleable(),

                TextColumn::make('eta')->label('ETA')->dateTime('d M Y')->placeholder('—')->toggleable(),
                TextColumn::make('updated_at')->label('Update')->since()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_tracks')
                    ->label('Sudah ditrack?')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')->falseLabel('Belum')
                    ->queries(
                        fn (Builder $q) => $q->whereHas('tracks'),
                        fn (Builder $q) => $q->whereDoesntHave('tracks'),
                    ),

                Tables\Filters\SelectFilter::make('latest_track_status')
                    ->label('Status Tracking')
                    ->options(
                        collect(TrackStatus::order())
                            ->mapWithKeys(fn (TrackStatus $s) => [$s->value => $s->label()])
                            ->toArray()
                        + [
                            TrackStatus::Hold->value      => TrackStatus::Hold->label(),
                            TrackStatus::Cancelled->value => TrackStatus::Cancelled->label(),
                        ]
                    )
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['value'])) {
                            $q->whereHas('latestTrack', fn ($tq) => $tq->where('status', $data['value']));
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->url(fn ($record) => route('filament.admin.resources.shipments.edit', $record))
                    ->icon('heroicon-m-eye'),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentTrackings::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'receiver', 'originCity', 'destinationCity', 'latestTrack']);
    }
}

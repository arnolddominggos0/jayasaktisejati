<?php

namespace App\Filament\Resources;

use App\Enums\TrackStatus;
use App\Filament\Resources\ShipmentResource\RelationManagers\ShipmentTracksRelationManager;
use App\Filament\Resources\ShipmentTrackingResource\Pages;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn, ViewColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class ShipmentTrackingResource extends Resource
{
    protected static ?string $model = Shipment::class;
    protected static ?string $navigationGroup  = 'Pengiriman';
    protected static ?string $navigationLabel  = 'Pelacakan & Monitoring';
    protected static ?string $modelLabel       = 'Pelacakan';
    protected static ?string $pluralModelLabel = 'Pelacakan';
    protected static ?string $navigationIcon   = 'heroicon-m-map-pin';

    public static function shouldRegisterNavigation(): bool
    {
        return auth_user()?->hasAnyRole(['super_admin','office_admin']) === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => static::getEloquentQuery())
            ->columns([
                TextColumn::make('code')->label('Kode')->badge()->copyable()
                    ->extraAttributes(['class' => 'font-mono'])->sortable()->searchable(),

                IconColumn::make('mode')->label('Moda')
                    ->icon(fn($state) => ($state?->value ?? $state) === 'sea' ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn($state) => ($state?->value ?? $state) === 'sea' ? 'primary' : 'warning')
                    ->tooltip(fn($state) => ($state?->value ?? $state) === 'sea' ? 'Laut' : 'Darat')
                    ->toggleable(),

                TextColumn::make('customer.name')->label('Customer')->badge()->toggleable(),

                TextColumn::make('route_summary')->label('Rute')->html()
                    ->getStateUsing(fn(Shipment $r) =>
                        "<div class='font-medium'>".($r->originCity->name ?? '-') ." &rarr; ".($r->destinationCity->name ?? '-')."</div>"
                    )->toggleable(),

                TextColumn::make('progress_count')->label('Progress')->state(function (Shipment $r) {
                    $order = TrackStatus::order();
                    $raw   = $r->latestTrack?->status;
                    $last  = $raw instanceof \BackedEnum ? $raw->value : $raw;
                    $idx = -1;
                    if ($last && ($cur = TrackStatus::tryFrom((string) $last))) {
                        $idx = array_search($cur, $order, true);
                    }
                    return ($idx + 1) . '/' . count($order);
                })->badge()->icon('heroicon-m-bolt')->toggleable(),

                ViewColumn::make('progress_stepper')->label(' ')->view('tables.columns.tracking-progress'),

                TextColumn::make('latestTrack.status')->label('Status')
                    ->formatStateUsing(fn($state) => $state?->label() ?? 'Belum dimulai')
                    ->badge()
                    ->color(function ($state) {
                        if (! $state) return 'gray';
                        $val = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        return match ($val) {
                            'delivered' => 'success',
                            'hold'      => 'warning',
                            'cancelled' => 'danger',
                            default     => 'primary',
                        };
                    })
                    ->toggleable(),

                TextColumn::make('eta')->label('ETA')->dateTime('d M Y')->placeholder('—')->toggleable(),
                TextColumn::make('updated_at')->label('Update')->since()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('has_tracks')->label('Sudah ditrack?')->placeholder('Semua')
                    ->trueLabel('Ya')->falseLabel('Belum')
                    ->queries(fn(Builder $q) => $q->whereHas('tracks'), fn(Builder $q) => $q->whereDoesntHave('tracks')),

                SelectFilter::make('mode')->options(['sea' => 'Laut', 'land' => 'Darat']),
            ])
            ->actions([
                Tables\Actions\Action::make('kelola')
                    ->label('Kelola Timeline')
                    ->icon('heroicon-m-sparkles')
                    ->color('primary')
                    ->url(fn($record) => static::getUrl('manage', ['record' => $record]))
                    ->visible(fn() => auth_user()?->hasRole('super_admin') === true),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ShipmentTracksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShipmentTrackings::route('/'),
            'manage' => Pages\ManageShipmentTracking::route('/{record}/manage'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer','receiver','originCity','destinationCity','latestTrack']);
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\TrackStatus;
use App\Enums\ShipmentMode;
use App\Filament\Resources\ShipmentResource\RelationManagers\ShipmentTracksRelationManager;
use App\Filament\Resources\ShipmentTrackingResource\Pages;
use App\Models\Shipment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn, ViewColumn};
use Filament\Tables\Filters\{SelectFilter, Filter, TernaryFilter};
use Illuminate\Database\Eloquent\Builder;

class ShipmentTrackingResource extends Resource
{
    protected static ?string $model = Shipment::class;
    protected static ?string $navigationGroup  = 'Transaksi';
    protected static ?string $navigationLabel  = 'Pelacakan & Monitoring';
    protected static ?string $modelLabel       = 'Pelacakan';
    protected static ?string $pluralModelLabel = 'Pelacakan';
    protected static ?string $navigationIcon   = 'heroicon-m-map';
    protected static ?int    $navigationSort   = 20;

    public static function shouldRegisterNavigation(): bool
    {
        $u = auth_user();
        return (bool) ($u && method_exists($u, 'hasAnyRole') && $u->hasAnyRole(['super_admin', 'office_admin']));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'receiver', 'originCity', 'destinationCity', 'latestTrack', 'tracks'])
            ->where(function ($w) {
                $w->whereNull('status')
                    ->orWhereNotIn('status', ['delivered', 'cancelled']);
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn() => static::getEloquentQuery())
            ->columns([
                TextColumn::make('code')->label('Kode')
                    ->badge()->copyable()->sortable()->searchable()
                    ->extraAttributes(['class' => 'font-mono']),

                IconColumn::make('mode')->label('Moda')
                    ->icon(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'heroicon-m-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'primary' : 'warning')
                    ->tooltip(fn($state) => ($state?->value ?? $state) === ShipmentMode::Sea->value ? 'Laut' : 'Darat')
                    ->toggleable(),

                TextColumn::make('customer.name')->label('Customer')->badge()->toggleable(),

                TextColumn::make('route_summary')->label('Rute')->html()
                    ->getStateUsing(
                        fn(Shipment $r) =>
                        "<div class='font-medium'>" . ($r->originCity->name ?? '-') . " &rarr; " . ($r->destinationCity->name ?? '-') . "</div>"
                    )->toggleable(),

                TextColumn::make('progress_count')->label('Progress')
                    ->state(function (Shipment $r) {
                        $order = TrackStatus::orderForMode($r->mode);
                        $raw  = $r->latestTrack?->status;
                        $cur  = $raw instanceof TrackStatus ? $raw : TrackStatus::tryFrom((string) $raw);
                        if (!$cur) return '0/' . count($order);
                        if ($cur === TrackStatus::Hold) return $cur->label();
                        if ($cur === TrackStatus::Cancelled) return $cur->label();
                        $idx = array_search($cur, $order, true);
                        $pos = ($idx === false) ? 0 : ($idx + 1);
                        return $pos . '/' . count($order);
                    })
                    ->badge()
                    ->icon('heroicon-m-bolt')
                    ->color(fn($state) => match ($state) {
                        'Ditahan' => 'warning',
                        'Dibatalkan' => 'danger',
                        default => 'primary',
                    })
                    ->toggleable(),

                ViewColumn::make('progress_stepper')->label(' ')->view('tables.columns.tracking-progress'),

                TextColumn::make('latestTrack.status')->label('Status')
                    ->formatStateUsing(fn($state) => $state?->label() ?? 'Belum dimulai')
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
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

                TextColumn::make('kpi_summary')
                    ->label('KPI Manado')
                    ->state(fn(Shipment $r) => $r->kpiManadoSummaryText() ?? '-')
                    ->tooltip('KPI Manado: Normal ≤19 hari, Urgent ≤17 hari')
                    ->wrap(),

                TextColumn::make('kpi_status')
                    ->label('Status KPI')
                    ->badge()
                    ->state(function (Shipment $r) {
                        $ev = $r->evaluateKpiForManado();
                        return $ev['applies'] ? ($ev['badge'] ?? 'Pending') : '—';
                    })
                    ->color(function (Shipment $r) {
                        $ev = $r->evaluateKpiForManado();
                        if (!($ev['applies'] ?? false)) return 'gray';
                        return match ($ev['badge'] ?? 'Pending') {
                            'On Time' => 'success',
                            'Late'    => 'danger',
                            default   => 'gray',
                        };
                    }),

                TextColumn::make('updated_at')->label('Update')->since()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('has_tracks')->label('Sudah ditrack?')->placeholder('Semua')
                    ->trueLabel('Ya')->falseLabel('Belum')
                    ->queries(fn(Builder $q) => $q->whereHas('tracks'), fn(Builder $q) => $q->whereDoesntHave('tracks')),

                SelectFilter::make('mode')->options(['sea' => 'Laut', 'land' => 'Darat']),

                Filter::make('manado_kpi_target')->label('Target KPI Manado')
                    ->query(function (Builder $q) {
                        $cfg = config('jss_kpi.manado', []);
                        $branchIds = array_map('intval', $cfg['branch_ids'] ?? []);
                        $cityIds   = array_map('intval', $cfg['coverage_city_ids'] ?? []);
                        $depotIds  = array_map('intval', $cfg['depot_ids'] ?? []);
                        $q->where(function ($w) use ($branchIds, $cityIds, $depotIds) {
                            if (!empty($branchIds)) $w->orWhereIn('branch_id', $branchIds);
                            if (!empty($cityIds))   $w->orWhereIn('destination_city_id', $cityIds);
                            if (!empty($depotIds))  $w->orWhereIn('assigned_depot_id', $depotIds);
                        });
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('kelola')
                    ->label('Kelola Timeline')
                    ->icon('heroicon-m-sparkles')
                    ->color('primary')
                    ->url(fn($record) => static::getUrl('manage', ['record' => $record]))
                    ->visible(function () {
                        $u = auth_user();
                        return (bool) ($u && method_exists($u, 'hasRole') && $u->hasRole('super_admin'));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_kpi')
                    ->label('Export KPI (CSV)')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        if ($records->isEmpty()) {
                            \Filament\Notifications\Notification::make()->title('Tidak ada baris terpilih')->warning()->send();
                            return;
                        }
                        $records->load(['tracks:id,shipment_id,status,tracked_at', 'originCity:id,name', 'destinationCity:id,name', 'customer:id,name']);

                        $filename = 'shipments-kpi-' . now()->format('Ymd-His') . '.csv';
                        return response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['Kode', 'Customer', 'Asal', 'Tujuan', 'Moda', 'KPI Summary', 'KPI Status', 'ETA', 'Dibuat']);
                            foreach ($records as $r) {
                                $ev  = $r->evaluateKpiForManado();
                                $sum = $r->kpiManadoSummaryText() ?? '-';
                                $bad = $ev['applies'] ? ($ev['badge'] ?? 'Pending') : '—';

                                fputcsv($out, [
                                    $r->code,
                                    $r->customer->name ?? '-',
                                    $r->originCity->name ?? '-',
                                    $r->destinationCity->name ?? '-',
                                    $r->mode?->label() ?? (string)$r->mode,
                                    $sum,
                                    $bad,
                                    optional($r->eta)->format('d M Y H:i'),
                                    optional($r->created_at)->format('d M Y H:i'),
                                ]);
                            }
                            fclose($out);
                        }, $filename, ['Content-Type' => 'text/csv']);
                    }),
            ])
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
}

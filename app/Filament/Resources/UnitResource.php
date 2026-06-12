<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Filament\Resources\UnitResource\Pages\ViewUnit;
use App\Filament\Resources\UnitResource\RelationManagers\UnitInspectionsRelationManager;
use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\UnitInspection;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * UnitResource — Pemeriksaan Unit
 *
 * Halaman 2 dari modul Quality Assurance.
 * Read-only. Menampilkan unit + status kualitas + timeline pemeriksaan.
 *
 * Navigasi: Quality Assurance → Pemeriksaan Unit
 */
class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon  = 'heroicon-m-magnifying-glass-circle';
    protected static ?string $navigationLabel = 'Pemeriksaan Unit';
    protected static ?string $navigationGroup = 'Quality Assurance';
    protected static ?int    $navigationSort  = 2;
    protected static bool    $shouldRegisterNavigation = true;
    protected static ?string $modelLabel      = 'Unit';
    protected static ?string $pluralModelLabel = 'Pemeriksaan Unit';

    protected static ?string $recordTitleAttribute = 'chassis_no';

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q
                ->with(['shipment.voyage.vessel', 'shipment.customer'])
                ->withCount('inspections')
                ->withCount(['inspections as inspections_failed_count' => fn (Builder $sub) =>
                    $sub->where('status', UnitInspection::STATUS_FAILED)
                ])
            )
            ->columns([
                TextColumn::make('chassis_no')
                    ->label('Chassis Number')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('engine_no')
                    ->label('Engine Number')
                    ->searchable()
                    ->toggleable()
                    ->fontFamily('mono'),

                TextColumn::make('model_no')
                    ->label('Model')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('shipment.code')
                    ->label('Shipment')
                    ->searchable()
                    ->url(fn (Unit $r) => $r->shipment_id
                        ? ShipmentResource::getUrl('view', ['record' => $r->shipment_id])
                        : null),

                TextColumn::make('shipment.voyage.voyage_no')
                    ->label('Voyage')
                    ->sortable(),

                TextColumn::make('shipment.voyage.vessel.name')
                    ->label('Kapal')
                    ->toggleable(),

                TextColumn::make('inspections_count')
                    ->label('Tahap')
                    ->badge()
                    ->color(fn (int $state) => $state === 6 ? 'success' : ($state > 0 ? 'warning' : 'gray'))
                    ->suffix(' / 6'),

                // Status Akhir — dihitung dari subquery
                TextColumn::make('status_kualitas')
                    ->label('Status Akhir')
                    ->getStateUsing(fn (Unit $record) => match (true) {
                        $record->inspections_count === 0                  => 'belum_diperiksa',
                        ($record->inspections_failed_count ?? 0) > 0     => 'tidak_lulus',
                        default                                           => 'lulus',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'lulus'            => 'success',
                        'tidak_lulus'      => 'danger',
                        'belum_diperiksa'  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'lulus'            => 'LULUS',
                        'tidak_lulus'      => 'TIDAK LULUS',
                        'belum_diperiksa'  => 'Belum Diperiksa',
                        default            => $state,
                    }),
            ])
            ->recordUrl(fn (Unit $r) => self::getUrl('view', ['record' => $r->id]))
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->url(fn (Unit $r) => self::getUrl('view', ['record' => $r->id])),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('shipment_id')
                    ->label('Voyage')
                    ->options(fn () => DB::table('shipments as s')
                        ->join('voyages as v', 'v.id', '=', 's.voyage_id')
                        ->selectRaw("s.id, v.voyage_no || ' – ' || s.route_from || ' → ' || s.route_to AS label")
                        ->orderByDesc('s.id')
                        ->pluck('label', 's.id')
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('model_no')
                    ->label('Model Kendaraan')
                    ->options(fn () => Unit::whereNotNull('model_no')
                        ->distinct()->orderBy('model_no')->pluck('model_no', 'model_no')->toArray()),
            ])
            ->searchable()
            ->emptyStateHeading('Belum ada unit')
            ->emptyStateDescription('Unit akan muncul setelah data shipment diimport.');
    }

    // ── Infolist (View page) ──────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Identitas Unit ──────────────────────────────────────────
                Section::make('Identitas Unit')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('model_no')
                            ->label('Model Kendaraan')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('chassis_no')
                            ->label('Chassis Number')
                            ->copyable()
                            ->fontFamily('mono')
                            ->weight('bold'),
                        TextEntry::make('engine_no')
                            ->label('Engine Number')
                            ->copyable()
                            ->fontFamily('mono'),
                        TextEntry::make('color')
                            ->label('Warna'),
                        TextEntry::make('reg_no')
                            ->label('No. Polisi')
                            ->default('—'),
                        TextEntry::make('do_number')
                            ->label('DO Number')
                            ->default('—'),
                    ]),

                // ── Informasi Perjalanan ─────────────────────────────────────
                Section::make('Informasi Perjalanan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('shipment.code')
                            ->label('Kode Shipment'),
                        TextEntry::make('shipment.voyage.voyage_no')
                            ->label('Voyage'),
                        TextEntry::make('shipment.voyage.vessel.name')
                            ->label('Kapal'),
                        TextEntry::make('shipment.route_from')
                            ->label('Asal'),
                        TextEntry::make('shipment.route_to')
                            ->label('PDC Tujuan'),
                        TextEntry::make('shipment.status')
                            ->label('Status Pengiriman')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'delivered' => 'success',
                                'transit'   => 'info',
                                'pickup'    => 'warning',
                                default     => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => strtoupper($state)),
                    ]),

                // ── Catatan ──────────────────────────────────────────────────
                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('notes')->label('')->default('Tidak ada catatan.'),
                    ])
                    ->collapsed(),

                // ── Checksheet Unit ───────────────────────────────────────────
                Section::make('Checksheet Unit')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->schema([
                        // Ringkasan status pemeriksaan
                        TextEntry::make('checksheet_status')
                            ->label('Status Pemeriksaan')
                            ->getStateUsing(function (Unit $record): string {
                                $total = $record->inspections()->count();
                                if ($total === 0) return 'belum';
                                $failed = $record->inspections()->where('status', UnitInspection::STATUS_FAILED)->count();
                                return $failed > 0 ? 'ada_temuan' : 'selesai';
                            })
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'selesai'     => 'success',
                                'ada_temuan'  => 'danger',
                                default       => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'selesai'     => '✓ SUDAH DIPERIKSA',
                                'ada_temuan'  => '✕ ADA TEMUAN',
                                default       => '— Belum Diperiksa',
                            }),

                        Grid::make(4)
                            ->schema([
                                TextEntry::make('tahap_selesai')
                                    ->label('Tahap')
                                    ->getStateUsing(fn (Unit $record): string =>
                                        $record->inspections()->count() . ' / 6'
                                    )
                                    ->weight('bold'),

                                TextEntry::make('total_item')
                                    ->label('Total Item')
                                    ->getStateUsing(fn (Unit $record): int =>
                                        (int) DB::table('unit_inspection_items')
                                            ->join('unit_inspections', 'unit_inspections.id', '=', 'unit_inspection_items.unit_inspection_id')
                                            ->where('unit_inspections.unit_id', $record->id)
                                            ->count()
                                    ),

                                TextEntry::make('total_ng')
                                    ->label('Total NG')
                                    ->getStateUsing(fn (Unit $record): int =>
                                        (int) DB::table('unit_inspection_items')
                                            ->join('unit_inspections', 'unit_inspections.id', '=', 'unit_inspection_items.unit_inspection_id')
                                            ->where('unit_inspections.unit_id', $record->id)
                                            ->where('unit_inspection_items.result', 'ng')
                                            ->count()
                                    )
                                    ->badge()
                                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),
                            ]),

                        // Timeline tahap
                        TextEntry::make('checksheet_timeline')
                            ->label('Tahap Pemeriksaan')
                            ->getStateUsing(function (Unit $record): string {
                                $done = $record->inspections()
                                    ->get(['stage', 'status'])
                                    ->keyBy('stage');

                                $stages = UnitInspection::STAGE_LABELS;
                                $lines  = [];
                                foreach ($stages as $key => $label) {
                                    if (isset($done[$key])) {
                                        $status = $done[$key]->status;
                                        $icon   = $status === UnitInspection::STATUS_FAILED ? '✕' : '✓';
                                        $lines[] = $icon . ' ' . $label . ($status === UnitInspection::STATUS_FAILED ? ' (NG)' : '');
                                    } else {
                                        $lines[] = '○ ' . $label;
                                    }
                                }
                                return implode("\n", $lines);
                            })
                            ->html(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            UnitInspectionsRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'view'  => ViewUnit::route('/{record}'),
        ];
    }

    public static function canCreate(): bool { return false; }
}

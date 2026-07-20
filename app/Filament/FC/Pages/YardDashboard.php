<?php

namespace App\Filament\FC\Pages;

use App\Filament\FC\Widgets\YardInventoryWidget;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\Unit;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Yard Inventory — single operational yard dashboard.
 *
 * KPI header: YardInventoryWidget (YardInventoryService::snapshot(), 30s polling).
 * Five operational tabs below:
 *   1. Ready Loading     — in yard, inspection accept / allow_with_remark
 *   2. Bermasalah        — in yard, gate_decision = return_to_pdc
 *   3. Waiting Inspection — in yard, no submitted handover_depot inspection
 *   4. Aging Yard        — all units in yard, colour-coded by days
 *   5. Shipment Readiness — by shipment, aggregated readiness %
 *
 * Source of truth: shipment_tracks + unit_inspections + units + shipments.
 * briefing_sessions is NOT used here.
 * Exit-gate rules: rack → delivery_to_port, non-rack → stuffing.
 */
class YardDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool    $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Operasional Lapangan';
    protected static ?string $navigationLabel = 'Yard Inventory';
    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?int    $navigationSort  = 25;

    protected static string $view = 'filament.fc.pages.yard-dashboard';

    public string $activeTab = 'ready_loading';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->isFieldCoordinator() ?? false;
    }

    public function getHeading(): string
    {
        return 'Yard Inventory Harian';
    }

    public function getSubheading(): ?string
    {
        return $this->getDepotName() . ' · ' . now()->translatedFormat('l, d F Y');
    }

    // ── Tab state ─────────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getTabs(): array
    {
        return [
            'ready_loading'      => 'Ready Loading',
            'bermasalah'         => 'Bermasalah',
            'waiting_inspection' => 'Waiting Inspection',
            'aging_yard'         => 'Aging Yard',
            'shipment_readiness' => 'Shipment Readiness',
        ];
    }

    // ── Master table dispatch ─────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'bermasalah'         => $this->buildBermasalahTable($table),
            'waiting_inspection' => $this->buildWaitingInspectionTable($table),
            'aging_yard'         => $this->buildAgingYardTable($table),
            'shipment_readiness' => $this->buildShipmentReadinessTable($table),
            default              => $this->buildReadyLoadingTable($table),
        };
    }

    // Exit-gate SQL: rack → delivery_to_port, non-rack → stuffing.

    private function exitNotExistsClosure(): \Closure
    {
        return fn ($q) => $q
            ->from('shipment_tracks as st_exit')
            ->whereColumn('st_exit.shipment_id', 's.id')
            ->whereNotNull('st_exit.tracked_at')
            ->whereRaw("(
                (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
            )");
    }

    private function noDepotQuery(Table $table): Table
    {
        return $table
            ->query(Unit::query()->whereRaw('1 = 0'))
            ->columns([TextColumn::make('id')])
            ->emptyStateHeading('Depot tidak ditemukan')
            ->emptyStateDescription('Hubungi admin untuk mengatur scope depot.');
    }

    // ── Tab 1: Ready Loading ──────────────────────────────────────────────────

    private function buildReadyLoadingTable(Table $table): Table
    {
        $depotId = $this->resolveDepotId();
        if (! $depotId) return $this->noDepotQuery($table);

        $query = Unit::query()
            ->select([
                'units.id',
                'units.sjkb_no',
                'units.reg_no',
                'units.chassis_no',
                DB::raw('s.code  AS shipment_code'),
                DB::raw('s.voyage'),
                DB::raw('st_h.tracked_at AS handover_at'),
                DB::raw('(CURRENT_DATE - st_h.tracked_at::date)::int AS aging_days'),
                DB::raw('ui.gate_decision'),
            ])
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->join('shipment_tracks as st_h', fn ($j) =>
                $j->on('st_h.shipment_id', '=', 's.id')
                  ->where('st_h.status', 'handover')
                  ->whereNotNull('st_h.tracked_at')
            )
            ->join('unit_inspections as ui', fn ($j) =>
                $j->on('ui.unit_id', '=', 'units.id')
                  ->where('ui.stage', 'handover_depot')
                  ->whereNotNull('ui.submitted_at')
                  ->whereIn('ui.gate_decision', ['accept', 'allow_with_remark'])
            )
            ->whereNotExists($this->exitNotExistsClosure());

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('sjkb_no')
                    ->label('SJKB')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('reg_no')
                    ->label('Plate Number')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('chassis_no')
                    ->label('Chassis')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('shipment_code')
                    ->label('Shipment')
                    ->searchable(),

                TextColumn::make('voyage')
                    ->label('Voyage')
                    ->placeholder('—'),

                TextColumn::make('handover_at')
                    ->label('Handover Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->translatedFormat('d M Y')
                        : '—'
                    ),

                TextColumn::make('aging_days')
                    ->label('Aging')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . ' hr' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state > 7 => 'danger',
                        (int) $state > 3 => 'warning',
                        default          => 'success',
                    }),

                TextColumn::make('gate_decision')
                    ->label('Inspection')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'accept'            => 'Accept',
                        'allow_with_remark' => 'Allow w/ Remark',
                        default             => (string) $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'accept'            => 'success',
                        'allow_with_remark' => 'warning',
                        default             => 'gray',
                    }),
            ])
            ->defaultSort('handover_at', 'asc')
            ->paginated([15, 25, 50])
            ->striped()
            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('Tidak ada unit ready loading')
            ->emptyStateDescription('Unit muncul di sini setelah handover tracked dan inspeksi accept.');
    }

    // ── Tab 2: Bermasalah ─────────────────────────────────────────────────────

    private function buildBermasalahTable(Table $table): Table
    {
        $depotId = $this->resolveDepotId();
        if (! $depotId) return $this->noDepotQuery($table);

        $query = Unit::query()
            ->select([
                'units.id',
                'units.sjkb_no',
                'units.model_no',
                DB::raw('s.code  AS shipment_code'),
                DB::raw('s.voyage'),
                DB::raw('ui.submitted_at AS inspection_date'),
                DB::raw('(CURRENT_DATE - ui.submitted_at::date)::int AS aging_days'),
                DB::raw('ui.notes AS remark'),
            ])
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->join('shipment_tracks as st_h', fn ($j) =>
                $j->on('st_h.shipment_id', '=', 's.id')
                  ->where('st_h.status', 'handover')
                  ->whereNotNull('st_h.tracked_at')
            )
            ->join('unit_inspections as ui', fn ($j) =>
                $j->on('ui.unit_id', '=', 'units.id')
                  ->where('ui.stage', 'handover_depot')
                  ->where('ui.gate_decision', 'return_to_pdc')
            )
            ->whereNotExists($this->exitNotExistsClosure());

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('sjkb_no')
                    ->label('SJKB')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('model_no')
                    ->label('Unit')
                    ->placeholder('—'),

                TextColumn::make('shipment_code')
                    ->label('Shipment')
                    ->searchable(),

                TextColumn::make('voyage')
                    ->label('Voyage')
                    ->placeholder('—'),

                TextColumn::make('inspection_date')
                    ->label('Inspection Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->translatedFormat('d M Y H:i')
                        : '—'
                    ),

                TextColumn::make('aging_days')
                    ->label('Aging')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . ' hr' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state > 7 => 'danger',
                        (int) $state > 3 => 'warning',
                        default          => 'success',
                    }),

                TextColumn::make('remark')
                    ->label('Remark')
                    ->placeholder('—')
                    ->limit(60)
                    ->wrap(),
            ])
            ->defaultSort('aging_days', 'desc')
            ->paginated([15, 25, 50])
            ->striped()
            ->emptyStateIcon('heroicon-o-exclamation-triangle')
            ->emptyStateHeading('Tidak ada unit bermasalah')
            ->emptyStateDescription('Unit dengan gate decision return_to_pdc akan muncul di sini.');
    }

    // ── Tab 3: Waiting Inspection ─────────────────────────────────────────────

    private function buildWaitingInspectionTable(Table $table): Table
    {
        $depotId = $this->resolveDepotId();
        if (! $depotId) return $this->noDepotQuery($table);

        $query = Unit::query()
            ->select([
                'units.id',
                'units.sjkb_no',
                DB::raw('s.code  AS shipment_code'),
                DB::raw('s.voyage'),
                DB::raw('st_h.tracked_at AS handover_at'),
                DB::raw('(CURRENT_DATE - st_h.tracked_at::date)::int AS waiting_days'),
            ])
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->join('shipment_tracks as st_h', fn ($j) =>
                $j->on('st_h.shipment_id', '=', 's.id')
                  ->where('st_h.status', 'handover')
                  ->whereNotNull('st_h.tracked_at')
            )
            ->whereNotExists($this->exitNotExistsClosure())
            ->whereNotExists(fn ($q) => $q
                ->from('unit_inspections as ui')
                ->whereColumn('ui.unit_id', 'units.id')
                ->where('ui.stage', 'handover_depot')
                ->whereNotNull('ui.submitted_at')
            );

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('sjkb_no')
                    ->label('SJKB')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('shipment_code')
                    ->label('Shipment')
                    ->searchable(),

                TextColumn::make('voyage')
                    ->label('Voyage')
                    ->placeholder('—'),

                TextColumn::make('handover_at')
                    ->label('Handover Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->translatedFormat('d M Y')
                        : '—'
                    ),

                TextColumn::make('waiting_days')
                    ->label('Waiting Days')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . ' hr' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state > 7 => 'danger',
                        (int) $state > 3 => 'warning',
                        (int) $state > 1 => 'warning',
                        default          => 'success',
                    }),
            ])
            ->defaultSort('waiting_days', 'desc')
            ->paginated([15, 25, 50])
            ->striped()
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('Semua unit sudah diinspeksi')
            ->emptyStateDescription('Unit menunggu inspeksi handover depot akan muncul di sini.');
    }

    // ── Tab 4: Aging Yard ─────────────────────────────────────────────────────

    private function buildAgingYardTable(Table $table): Table
    {
        $depotId = $this->resolveDepotId();
        if (! $depotId) return $this->noDepotQuery($table);

        $query = Unit::query()
            ->select([
                'units.id',
                'units.sjkb_no',
                'units.model_no',
                'units.chassis_no',
                DB::raw('s.code  AS shipment_code'),
                DB::raw('st_h.tracked_at AS handover_at'),
                DB::raw('(CURRENT_DATE - st_h.tracked_at::date)::int AS aging_days'),
            ])
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->join('shipment_tracks as st_h', fn ($j) =>
                $j->on('st_h.shipment_id', '=', 's.id')
                  ->where('st_h.status', 'handover')
                  ->whereNotNull('st_h.tracked_at')
            )
            ->whereNotExists($this->exitNotExistsClosure());

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('sjkb_no')
                    ->label('SJKB')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('model_no')
                    ->label('Unit')
                    ->placeholder('—'),

                TextColumn::make('chassis_no')
                    ->label('Chassis')
                    ->placeholder('—'),

                TextColumn::make('shipment_code')
                    ->label('Shipment')
                    ->searchable(),

                TextColumn::make('handover_at')
                    ->label('Handover Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->translatedFormat('d M Y')
                        : '—'
                    ),

                TextColumn::make('aging_days')
                    ->label('Aging')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . ' hr' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state > 7 => 'danger',
                        (int) $state > 3 => 'warning',
                        (int) $state > 1 => 'warning',
                        default          => 'success',
                    })
                    ->sortable(),
            ])
            ->defaultSort('aging_days', 'desc')
            ->paginated([15, 25, 50])
            ->striped()
            ->recordClasses(fn (Unit $record) => match (true) {
                (int) $record->aging_days > 7 => 'bg-red-50 dark:bg-red-950/20',
                (int) $record->aging_days > 3 => 'bg-orange-50 dark:bg-orange-950/20',
                (int) $record->aging_days > 1 => 'bg-yellow-50 dark:bg-yellow-950/10',
                default                       => '',
            })
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateHeading('Tidak ada unit dalam yard')
            ->emptyStateDescription('Unit yang masuk yard akan muncul di sini.');
    }

    // ── Tab 5: Shipment Readiness ─────────────────────────────────────────────

    private function buildShipmentReadinessTable(Table $table): Table
    {
        $depotId = $this->resolveDepotId();
        if (! $depotId) {
            return $table
                ->query(Shipment::query()->whereRaw('1 = 0'))
                ->columns([TextColumn::make('id')])
                ->emptyStateHeading('Depot tidak ditemukan');
        }

        // Correlated SQL referencing outer `shipments` table (no alias).
        $handoverExists = "(SELECT 1 FROM shipment_tracks st_h
            WHERE st_h.shipment_id = shipments.id
            AND st_h.status = 'handover'
            AND st_h.tracked_at IS NOT NULL)";

        $exitExists = "(SELECT 1 FROM shipment_tracks st_exit
            WHERE st_exit.shipment_id = shipments.id
            AND st_exit.tracked_at IS NOT NULL
            AND (
                (shipments.mode IN ('sea', 'sea_freight') AND shipments.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                OR (NOT (shipments.mode IN ('sea', 'sea_freight') AND shipments.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
            ))";

        $inYard = "EXISTS {$handoverExists} AND NOT EXISTS {$exitExists}";

        $query = Shipment::query()
            ->select([
                'shipments.id',
                'shipments.code',
                'shipments.voyage',
                DB::raw("(SELECT COUNT(*) FROM units u
                    WHERE u.shipment_id = shipments.id
                ) AS expected_unit"),
                DB::raw("(SELECT COUNT(*) FROM units u
                    WHERE u.shipment_id = shipments.id
                    AND NOT EXISTS {$exitExists}
                ) AS masuk_yard"),
                DB::raw("(SELECT COUNT(*) FROM units u
                    WHERE u.shipment_id = shipments.id
                    AND {$inYard}
                    AND EXISTS (SELECT 1 FROM unit_inspections ui
                        WHERE ui.unit_id = u.id
                        AND ui.stage = 'handover_depot'
                        AND ui.submitted_at IS NOT NULL
                        AND ui.gate_decision IN ('accept', 'allow_with_remark'))
                ) AS siap_loading"),
                DB::raw("(SELECT COUNT(*) FROM units u
                    WHERE u.shipment_id = shipments.id
                    AND {$inYard}
                    AND EXISTS (SELECT 1 FROM unit_inspections ui
                        WHERE ui.unit_id = u.id
                        AND ui.stage = 'handover_depot'
                        AND ui.gate_decision = 'return_to_pdc')
                ) AS bermasalah"),
                DB::raw("(SELECT COUNT(*) FROM units u
                    WHERE u.shipment_id = shipments.id
                    AND {$inYard}
                    AND NOT EXISTS (SELECT 1 FROM unit_inspections ui
                        WHERE ui.unit_id = u.id
                        AND ui.stage = 'handover_depot'
                        AND ui.submitted_at IS NOT NULL)
                ) AS waiting_inspection"),
            ])
            ->where('shipments.assigned_depot_id', $depotId)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st_h')
                ->whereColumn('st_h.shipment_id', 'shipments.id')
                ->where('st_h.status', 'handover')
                ->whereNotNull('st_h.tracked_at')
            );

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('code')
                    ->label('Shipment')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('voyage')
                    ->label('Voyage')
                    ->placeholder('—'),

                TextColumn::make('expected_unit')
                    ->label('Expected')
                    ->alignCenter()
                    ->suffix(' unit'),

                TextColumn::make('masuk_yard')
                    ->label('Masuk Yard')
                    ->alignCenter()
                    ->color(fn ($state, $record) =>
                        (int) $state >= (int) $record->expected_unit ? 'success' : 'warning'
                    ),

                TextColumn::make('siap_loading')
                    ->label('Ready Loading')
                    ->alignCenter()
                    ->color(fn ($state) => (int) $state > 0 ? 'success' : 'gray'),

                TextColumn::make('bermasalah')
                    ->label('Bermasalah')
                    ->alignCenter()
                    ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('waiting_inspection')
                    ->label('Waiting Insp.')
                    ->alignCenter()
                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'gray'),

                TextColumn::make('readiness_pct')
                    ->label('Readiness %')
                    ->getStateUsing(fn ($record) => (int) $record->expected_unit > 0
                        ? number_format((int) $record->siap_loading / (int) $record->expected_unit * 100, 1) . '%'
                        : '—'
                    )
                    ->color(fn ($record) => match (true) {
                        (int) $record->expected_unit === 0                                   => 'gray',
                        (int) $record->siap_loading >= (int) $record->expected_unit          => 'success',
                        (int) $record->siap_loading >= (int) ($record->expected_unit * 0.6)  => 'warning',
                        default                                                              => 'danger',
                    })
                    ->badge()
                    ->alignCenter(),
            ])
            ->defaultSort('code', 'asc')
            ->paginated([10, 25, 50])
            ->striped()
            ->emptyStateIcon('heroicon-o-chart-bar')
            ->emptyStateHeading('Tidak ada shipment aktif di yard')
            ->emptyStateDescription('Shipment akan muncul setelah handover track diinput.');
    }

    // ── Scope helpers ─────────────────────────────────────────────────────────

    private function resolveDepotId(): ?int
    {
        $user = Filament::auth()->user();
        if (! $user) return null;

        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        if (isset($user->scope_unit_type) && $user->scope_unit_type === 'depot' && $user->scope_unit_id) {
            return (int) $user->scope_unit_id;
        }

        $raw = DB::table('depots')->where('coordinator_user_id', $user->id)->value('id');
        return $raw ? (int) $raw : null;
    }

    private function getDepotName(): string
    {
        $depotId = $this->resolveDepotId();
        return $depotId
            ? (Depot::select('name')->find($depotId)?->name ?? 'Depot tidak diketahui')
            : 'Depot tidak diketahui';
    }
}

<?php

namespace App\Filament\Pages;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string $view = 'filament.pages.admin-dashboard';

    protected static ?string $title = 'Dashboard';

    public ?int $branch_id = null;

    public ?string $mode = null;

    public string $period = 'this_month';

    public ?string $period_month = null;

    public string $brandHex = '#0137A1';

    public string $dashboardView = 'tam';

    public function mount(): void
    {
        $this->period_month = now()->format('Y-m');

        $this->form->fill([
            'dashboardView' => 'tam',
            'branch_id' => null,
            'mode' => null,
            'period' => 'this_month',
            'period_month' => $this->period_month,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 5])
                ->schema([
                    Forms\Components\ToggleButtons::make('dashboardView')
                        ->options(['all' => 'Dashboard Umum', 'tam' => 'Dashboard TAM'])
                        ->inline()->default('tam')->reactive()
                        ->afterStateUpdated(fn($state) => $this->dashboardView = $state ?: 'tam')
                        ->hiddenLabel()->columnSpan(['default' => 2, 'lg' => 1])
                        ->hidden(),

                    Forms\Components\Select::make('period')
                        ->label('Periode')
                        ->options(['this_month' => 'Bulan ini', 'this_year' => 'Tahun ini', 'by_month' => 'Per bulan'])
                        ->default('this_month')->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->period = $state ?: 'this_month';
                            if ($this->period === 'by_month' && ! $this->period_month) {
                                $this->period_month = now()->format('Y-m');
                            }
                            $this->dispatch('charts-ready');
                        })->columnSpan(1),

                    Forms\Components\Select::make('period_month')
                        ->label('Bulan')
                        ->options($this->getMonthOptions())
                        ->default(now()->format('Y-m'))
                        ->reactive()
                        ->hidden(fn(Get $get) => $get('period') !== 'by_month')
                        ->afterStateUpdated(function ($state) {
                            $this->period_month = $state ?: now()->format('Y-m');
                            $this->dispatch('charts-ready');
                        })
                        ->columnSpan(1),

                    Forms\Components\Select::make('branch_id')
                        ->label('Cabang')
                        ->placeholder('Semua cabang')
                        ->options($this->getBranchOptions())
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->branch_id = $state ?: null;
                            $this->dispatch('charts-ready');
                        })
                        ->columnSpan(1),

                    Forms\Components\Select::make('mode')
                        ->label('Moda')
                        ->placeholder('Semua moda')
                        ->options([ShipmentMode::Sea->value => 'Laut', ShipmentMode::Land->value => 'Darat'])
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->mode = $state ?: null;
                            $this->dispatch('charts-ready');
                        })
                        ->columnSpan(1),
                ]),
        ];
    }

    protected function getMonthOptions(): array
    {
        $options = [];
        for ($i = 0; $i < 12; $i++) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            $options[$month->format('Y-m')] = $month->translatedFormat('F Y');
        }

        return $options;
    }

    protected function getBranchOptions(): array
    {
        return DB::table('branches')->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected function parsePeriodMonth(): Carbon
    {
        if ($this->period === 'by_month' && $this->period_month) {
            try {
                return Carbon::createFromFormat('Y-m', $this->period_month)->startOfMonth();
            } catch (\Throwable $e) {
                return now()->startOfMonth();
            }
        }

        return now()->startOfMonth();
    }

    protected function getPeriodRange(): array
    {
        $state = $this->form->getState();

        $period = $state['period'] ?? $this->period;
        $periodMonth = $state['period_month']
            ?? $this->period_month
            ?? now()->format('Y-m');

        if ($period === 'by_month') {
            $base = Carbon::createFromFormat('Y-m', $periodMonth);

            return [
                $base->copy()->startOfMonth(),
                $base->copy()->endOfMonth(),
            ];
        }

        if ($period === 'this_year') {
            return [
                now()->startOfYear(),
                now()->endOfYear(),
            ];
        }

        return [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ];
    }

    protected function applyFilters(Builder $q): Builder
    {
        return $q
            ->when($this->branch_id, fn($qq) => $qq->where('branch_id', $this->branch_id))
            ->when($this->mode, fn($qq) => $qq->where('mode', $this->mode));
    }

    protected function baseShipmentQuery(): Builder
    {
        return $this->applyFilters(Shipment::query());
    }

    protected function getConfigCustomerIds(): array
    {
        $cfg = config('jss_kpi.manado', []);

        return array_map('intval', $cfg['customer_ids'] ?? []);
    }

    protected function tamBaseQuery(): Builder
    {
        $customerIds = $this->getConfigCustomerIds();

        return $this->baseShipmentQuery()
            ->when(! empty($customerIds), fn($qq) => $qq->whereIn('customer_id', $customerIds))
            ->whereNotNull('delivered_at');
    }

    protected function tamPortStockBaseQuery(): Builder
    {
        $customerIds = $this->getConfigCustomerIds();

        return $this->baseShipmentQuery()
            ->when(! empty($customerIds), fn($qq) => $qq->whereIn('customer_id', $customerIds))
            ->whereNull('delivered_at');
    }

    public function getTamPortStock(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $cacheKey = 'tam_port_stock_historical:' . md5(implode('|', [
            $start->toIso8601String(),
            $end->toIso8601String(),
            $this->branch_id ?: 'all',
            $this->mode ?: 'all',
        ]));

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($start, $end) {

            $loadedStatuses = [
                TrackStatus::UnitLoading->value,
                TrackStatus::OnShip->value,
                TrackStatus::VesselDepart->value,
            ];

            $portStatuses = [
                TrackStatus::DeliveryToPort->value,
                TrackStatus::Stacking->value,
            ];

            // Penentu port stock berbasis track-status, bukan delivered_at.
            // - Harus punya track delivery_to_port/stacking <= $end
            // - Belum punya track onship/unit_loading/vessel_depart <= $end
            // Ini agar shipment lama (masuk port bulan lalu) tetap ikut dihitung
            // dan delivered_at yang terisi oleh generator tidak memblokir.
            $customerIds = $this->getConfigCustomerIds();

            $shipments = $this->baseShipmentQuery()
                ->when(! empty($customerIds), fn($qq) => $qq->whereIn('customer_id', $customerIds))
                ->whereHas('tracks', fn($q) => $q
                    ->whereIn('status', $portStatuses)
                    ->where('tracked_at', '<=', $end)
                )
                ->whereDoesntHave('tracks', fn($q) => $q
                    ->whereIn('status', $loadedStatuses)
                    ->where('tracked_at', '<=', $end)
                )
                ->with([
                    'tracks' => fn($q) => $q->orderBy('tracked_at', 'asc'),
                    'units:id,shipment_id',
                ])
                ->get();

            $items = [];
            $total = 0;   // unit-based total (not shipment count)
            $sumAge = 0;  // unit-weighted age sum for correct avg_age
            $over3 = 0;   // unit-based over-3-days count

            foreach ($shipments as $shipment) {
                // Ambil track sampai akhir periode — shipment lama tetap masuk hitungan
                $tracks = $shipment->tracks
                    ->filter(fn($t) => $t->tracked_at && $t->tracked_at <= $end)
                    ->values();

                if ($tracks->isEmpty()) {
                    continue;
                }

                $last = $tracks->last();

                $lastStatus = $last->status instanceof \BackedEnum
                    ? $last->status->value
                    : (string) $last->status;

                // Hanya shipment yang posisi terakhirnya di port
                if (! in_array($lastStatus, $portStatuses, true)) {
                    continue;
                }

                // Sudah naik kapal / berangkat → bukan port stock lagi
                $alreadyLoaded = $tracks->contains(fn($t) => in_array(
                    $t->status instanceof \BackedEnum ? $t->status->value : (string) $t->status,
                    $loadedStatuses,
                    true
                ));

                if ($alreadyLoaded) {
                    continue;
                }
                if (! $last->tracked_at) {
                    continue;
                }

                $age = Carbon::parse($last->tracked_at)
                    ->startOfDay()
                    ->diffInDays($end->copy()->startOfDay());

                // Each physical vehicle in this SPPB is a distinct unit at port.
                // getRelation() bypasses the 'units'=>'array' cast.
                $unitCount = max(1, $shipment->getRelation('units')->count());

                $total   += $unitCount;
                $sumAge  += $age * $unitCount;
                if ($age >= 3) {
                    $over3 += $unitCount;
                }

                $items[] = [
                    'shipment_id' => $shipment->id,
                    'code'        => $shipment->code,
                    'route'       => $shipment->route_label,
                    'status'      => $lastStatus,
                    'age_days'    => $age,
                    'unit_count'  => $unitCount,
                    'stacking_at' => $last->tracked_at->toDateTimeString(),
                ];
            }

            return [
                'total'      => $total,
                'avg_age'    => $total > 0 ? round($sumAge / $total, 1) : 0,
                'over_three' => $over3,
                'items'      => $items,
            ];
        });
    }

    public function getKpis(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $q = $this->baseShipmentQuery()
            ->whereBetween('created_at', [$start, $end]);

        $totalAktif = (clone $q)
            ->whereNotIn('status', [
                ShipmentStatus::Cancelled,
                ShipmentStatus::Delivered,
            ])->count();

        $pendingPickup = (clone $q)
            ->whereIn('status', [
                ShipmentStatus::Draft->value,
                ShipmentStatus::Pending->value,
                ShipmentStatus::Pickup->value,
            ])->count();

        $aktivitasPeriode = ShipmentTrack::query()
            ->when(
                $this->branch_id,
                fn($qq) => $qq->whereHas(
                    'shipment',
                    fn($s) => $s->where('branch_id', $this->branch_id)
                )
            )
            ->when(
                $this->mode,
                fn($qq) => $qq->whereHas(
                    'shipment',
                    fn($s) => $s->where('mode', $this->mode)
                )
            )
            ->whereBetween('tracked_at', [$start, $end])
            ->count();

        return [
            'totalAktif' => $totalAktif,
            'pendingPickup' => $pendingPickup,
            'aktivitasHariIni' => $aktivitasPeriode,
            'sparkline' => $this->buildSparkline($start, $end),
        ];
    }

    protected function buildSparkline(Carbon $start, Carbon $end): array
    {
        $days = min(14, $start->diffInDays($end));

        return collect(range($days, 0))->map(function ($i) use ($end) {
            $day = $end->copy()->subDays($i);

            $count = ShipmentTrack::query()
                ->when(
                    $this->branch_id,
                    fn($qq) => $qq->whereHas(
                        'shipment',
                        fn($s) => $s->where('branch_id', $this->branch_id)
                    )
                )
                ->when(
                    $this->mode,
                    fn($qq) => $qq->whereHas(
                        'shipment',
                        fn($s) => $s->where('mode', $this->mode)
                    )
                )
                ->whereDate('tracked_at', $day)
                ->count();

            return [
                'label' => $day->format('d M'),
                'value' => $count,
            ];
        })->values()->all();
    }

    public function getTrendSeries(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $labels = collect();
        $series = collect();

        if ($this->period === 'this_year') {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $labels->push($cursor->format('M Y'));
                $monthStart = $cursor->copy()->startOfMonth();
                $monthEnd = $cursor->copy()->endOfMonth();
                $series->push($this->baseShipmentQuery()->whereBetween('created_at', [$monthStart, $monthEnd])->count());
                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $labels->push($cursor->format('d M'));
                $series->push($this->baseShipmentQuery()->whereDate('created_at', $cursor)->count());
                $cursor->addDay();
            }
        }

        return ['labels' => $labels->values()->all(), 'series' => $series->values()->all()];
    }

    public function getStatusDistribution(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $statuses = [ShipmentStatus::Draft, ShipmentStatus::Pending, ShipmentStatus::Pickup, ShipmentStatus::Transit, ShipmentStatus::Delivered, ShipmentStatus::Hold, ShipmentStatus::Cancelled];

        $labels = [];
        $values = [];
        foreach ($statuses as $st) {
            $labels[] = $st->label();
            $values[] = $this->baseShipmentQuery()->whereBetween('created_at', [$start, $end])->where('status', $st)->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getTopCustomers(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $rows = Customer::query()
            ->select(['customers.id', 'customers.name'])
            ->selectRaw('COUNT(shipments.id) as total')
            ->leftJoin('shipments', 'shipments.customer_id', '=', 'customers.id')
            ->when($this->branch_id, fn($qq) => $qq->where('shipments.branch_id', $this->branch_id))
            ->when($this->mode, fn($qq) => $qq->where('shipments.mode', $this->mode))
            ->whereBetween('shipments.created_at', [$start, $end])
            ->groupBy(['customers.id', 'customers.name'])
            ->orderByDesc('total')->limit(5)->get();

        return $rows->map(fn($r) => ['name' => $r->name, 'total' => (int) $r->total])->values()->all();
    }

    public function getLeadTimeSummary(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $avg = $this->baseShipmentQuery()
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$start, $end])
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (delivered_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

        $avgDays = $avg ? round($avg, 1) : 0.0;
        $targetDays = 12;

        $progress = 0;
        if ($avgDays > 0) {
            $p = (($targetDays - $avgDays) / $targetDays) * 100;
            $progress = (int) max(0, min(100, round($p)));
        }

        return ['avg_days' => $avgDays, 'target' => $targetDays, 'progress' => $progress];
    }

    public function getRecentActivities(): array
    {
        [$start, $end] = $this->getPeriodRange();

        return ShipmentTrack::query()
            ->with(['shipment:id,code', 'user:id,name'])
            ->when($this->branch_id, fn($qq) => $qq->whereHas('shipment', fn($s) => $s->where('branch_id', $this->branch_id)))
            ->when($this->mode, fn($qq) => $qq->whereHas('shipment', fn($s) => $s->where('mode', $this->mode)))
            ->whereBetween('tracked_at', [$start, $end])
            ->latest('tracked_at')->limit(12)->get()
            ->map(fn($t) => [
                'shipment_code' => $t->shipment?->code ?? '-',
                'status' => $t->status instanceof \BackedEnum
                    ? (method_exists($t->status, 'label')
                        ? $t->status->label()
                        : $t->status->value)
                    : (string) $t->status,
                'when' => Carbon::parse($t->tracked_at)->diffForHumans(),
                'who' => $t->user?->name ?? 'Sistem',
                'note' => $t->note,
            ])->all();
    }

    public function getTamKpiSummary(): array
    {
        $cfg = config('jss_kpi.manado', []);

        [$start, $end] = $this->getPeriodRange();

        $rows = $this->tamBaseQuery()
            ->whereBetween('delivered_at', [$start, $end])
            ->with('units:id,shipment_id')
            ->get();

        $total  = 0;
        $onTime = 0;
        $late   = 0;

        foreach ($rows as $shipment) {
            if (! method_exists($shipment, 'evaluateKpiForManado')) {
                continue;
            }

            $evaluation = $shipment->evaluateKpiForManado();

            if (! ($evaluation['applies'] ?? false)) {
                continue;
            }

            // Count per unit — each physical vehicle is 1 KPI data point.
            // getRelation() bypasses the 'units'=>'array' cast in Shipment::$casts
            // that would otherwise intercept $shipment->units and return null.
            $unitCount = max(1, $shipment->getRelation('units')->count());

            $total += $unitCount;
            $badge = $evaluation['badge'] ?? null;

            if (in_array($badge, ['On Time', 'Tepat Waktu'], true)) {
                $onTime += $unitCount;
            }

            if (in_array($badge, ['Late', 'Terlambat'], true)) {
                $late += $unitCount;
            }
        }

        return [
            'total'        => $total,
            'on_time'      => $onTime,
            'late'         => $late,
            'on_time_pct'  => $total > 0
                ? round(($onTime / $total) * 100, 1)
                : 0,
            'late_pct'     => $total > 0
                ? round(($late / $total) * 100, 1)
                : 0,
            'target_total' => (int) ($cfg['thresholds']['total_days']['normal'] ?? 19),
        ];
    }

    public function getTamLateShipments(): array
    {
        [$start, $end] = $this->getPeriodRange();

        $rows = $this->tamBaseQuery()->whereBetween('delivered_at', [$start, $end])->latest('delivered_at')->limit(50)->with('tracks:id,shipment_id,status,tracked_at')->get();

        $result = [];
        foreach ($rows as $s) {
            if (! method_exists($s, 'evaluateKpiForManado')) {
                continue;
            }
            $ev = $s->evaluateKpiForManado();
            if (! ($ev['applies'] ?? false)) {
                continue;
            }
            if (($ev['badge'] ?? null) !== 'Late' && ($ev['badge'] ?? null) !== 'Terlambat') {
                continue;
            }
            $summary = $ev['summary'] ?? [];
            $totalActual = $summary['total']['actual'] ?? null;
            $totalLimit = $summary['total']['limit'] ?? null;
            $lateBy = null;
            if (! is_null($totalActual) && ! is_null($totalLimit)) {
                $lateBy = max(0, (int) $totalActual - (int) $totalLimit);
            }
            $result[] = ['code' => $s->code, 'late_by' => $lateBy, 'summary' => $s->kpiManadoSummaryText() ?? null];
            if (count($result) >= 10) {
                break;
            }
        }

        return $result;
    }

    public function getTamLeadTimeSeries(): array
    {
        $cfg        = config('jss_kpi.manado', []);
        $thresholds = $cfg['thresholds'] ?? [];
        $targetDw    = (float) ($thresholds['dwelling_days']        ?? 6);
        $targetSa    = (float) ($thresholds['sailing_days']         ?? 10);
        $targetDo    = (float) ($thresholds['dooring_days']         ?? 2);
        $targetTotal = (float) ($thresholds['total_days']['normal'] ?? 19);

        [$start, $end] = $this->getPeriodRange();

        $rows = $this->tamBaseQuery()
            ->whereBetween('delivered_at', [$start, $end])
            ->with(['tracks:id,shipment_id,status,tracked_at', 'units:id,shipment_id'])
            ->get();

        // Unit-weighted sums so that SPPBs with 7 units weigh 7× more than single-unit SPPBs.
        $sumDw = $sumSa = $sumDo = $sumTotal = 0.0;
        $weightedN = 0;

        foreach ($rows as $s) {
            if (! method_exists($s, 'evaluateKpiForManado')) {
                continue;
            }
            $ev = $s->evaluateKpiForManado();
            if (! ($ev['applies'] ?? false)) {
                continue;
            }
            $summary = $ev['summary'] ?? [];
            $dw = $summary['dwelling']['actual'] ?? null;
            $sa = $summary['sailing']['actual'] ?? null;
            $do = $summary['dooring']['actual'] ?? null;
            $tt = $summary['total']['actual']   ?? null;

            if ($dw !== null && $sa !== null && $do !== null && $tt !== null) {
                $unitCount  = max(1, $s->getRelation('units')->count());
                $sumDw    += (float) $dw * $unitCount;
                $sumSa    += (float) $sa * $unitCount;
                $sumDo    += (float) $do * $unitCount;
                $sumTotal += (float) $tt * $unitCount;
                $weightedN += $unitCount;
            }
        }

        if ($weightedN > 0) {
            $avgDw    = (int) round($sumDw    / $weightedN);
            $avgSa    = (int) round($sumSa    / $weightedN);
            $avgDo    = (int) round($sumDo    / $weightedN);
            $avgTotal = (int) round($sumTotal / $weightedN);
        } else {
            $avgDw = $avgSa = $avgDo = $avgTotal = 0;
        }

        return [
            'labels'   => ['Dwelling', 'Sailing', 'Dooring', 'Total'],
            'values'   => [$avgDw, $avgSa, $avgDo, $avgTotal],
            'avg_days' => ['dwelling' => $avgDw, 'sailing' => $avgSa, 'dooring' => $avgDo, 'total' => $avgTotal],
            'targets'  => [$targetDw, $targetSa, $targetDo, $targetTotal],
        ];
    }

    public function getTamLeadTimeEvaluation(): array
    {
        [$start, $end] = $this->getPeriodRange();
        $rows = $this->tamBaseQuery()
            ->whereBetween('delivered_at', [$start, $end])
            ->with(['tracks:id,shipment_id,status,tracked_at', 'units:id,shipment_id'])
            ->get();

        $metrics = ['dwelling' => ['ok' => 0, 'ng' => 0, 'pending' => 0], 'sailing' => ['ok' => 0, 'ng' => 0, 'pending' => 0], 'dooring' => ['ok' => 0, 'ng' => 0, 'pending' => 0], 'total' => ['ok' => 0, 'ng' => 0, 'pending' => 0]];

        foreach ($rows as $s) {
            if (! method_exists($s, 'evaluateKpiForManado')) {
                continue;
            }
            $ev = $s->evaluateKpiForManado();
            if (! ($ev['applies'] ?? false)) {
                continue;
            }
            // Count per unit so achievement percentages reflect physical vehicles, not SPPBs.
            $unitCount = max(1, $s->getRelation('units')->count());
            $summary = $ev['summary'] ?? [];
            foreach (['dwelling', 'sailing', 'dooring', 'total'] as $key) {
                if (! isset($summary[$key])) {
                    continue;
                }
                $st = $summary[$key]['status'] ?? 'PENDING';
                if ($st === 'OK') {
                    $metrics[$key]['ok'] += $unitCount;
                } elseif ($st === 'LATE') {
                    $metrics[$key]['ng'] += $unitCount;
                } else {
                    $metrics[$key]['pending'] += $unitCount;
                }
            }
        }

        foreach ($metrics as $key => $val) {
            $total = $val['ok'] + $val['ng'];
            if ($total > 0) {
                $metrics[$key]['ok_pct'] = round(($val['ok'] / $total) * 100, 1);
                $metrics[$key]['ng_pct'] = round(($val['ng'] / $total) * 100, 1);
            } else {
                $metrics[$key]['ok_pct'] = 0;
                $metrics[$key]['ng_pct'] = 0;
            }
        }

        return $metrics;
    }

    public function getTamPortStockList(): array
    {
        return $this->getTamPortStock()['items'] ?? [];
    }

    public function getTamPortStockSummary(): array
    {
        $d = $this->getTamPortStock();

        return [
            'total' => $d['total'] ?? 0,
            'avg_age' => $d['avg_age'] ?? 0,
            'over_three' => $d['over_three'] ?? 0,
        ];
    }

    public function getTamMonthlyBreakdown(): array
    {
        [$start, $end] = $this->getPeriodRange();
        $cfg = config('jss_kpi.manado', []);
        $thresholds = $cfg['thresholds'] ?? [];
        $targetDw = (float) ($thresholds['dwelling_days'] ?? 6);
        $targetSa = (float) ($thresholds['sailing_days'] ?? 10);
        $targetDo = (float) ($thresholds['dooring_days'] ?? 3);

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $dt = now()->subMonths($i)->startOfMonth();
            $months->push([
                'start' => $dt->copy()->startOfMonth(),
                'end' => $dt->copy()->endOfMonth(),
                'label' => $dt->translatedFormat('F'),
            ]);
        }

        $result = [];
        foreach ($months as $m) {
            $rows = $this->tamBaseQuery()
                ->whereBetween('delivered_at', [$m['start'], $m['end']])
                ->with(['tracks:id,shipment_id,status,tracked_at', 'units:id,shipment_id'])
                ->get();

            $sumDw = $sumSa = $sumDo = 0.0;
            $weightedN = 0;
            $n = 0;

            foreach ($rows as $s) {
                if (! method_exists($s, 'evaluateKpiForManado')) {
                    continue;
                }
                $ev = $s->evaluateKpiForManado();
                if (! ($ev['applies'] ?? false)) {
                    continue;
                }
                $summary = $ev['summary'] ?? [];
                $dw = $summary['dwelling']['actual'] ?? null;
                $sa = $summary['sailing']['actual'] ?? null;
                $do = $summary['dooring']['actual'] ?? null;

                if ($dw !== null && $sa !== null && $do !== null) {
                    $unitCount  = max(1, $s->getRelation('units')->count());
                    $sumDw    += (float) $dw * $unitCount;
                    $sumSa    += (float) $sa * $unitCount;
                    $sumDo    += (float) $do * $unitCount;
                    $weightedN += $unitCount;
                    $n++;
                }
            }

            $avgDw = $weightedN > 0 ? (int) round($sumDw / $weightedN) : null;
            $avgSa = $weightedN > 0 ? (int) round($sumSa / $weightedN) : null;
            $avgDo = $weightedN > 0 ? (int) round($sumDo / $weightedN) : null;

            $result[] = [
                'month' => $m['label'],
                'dw' => $avgDw,
                'sl' => $avgSa,
                'dr' => $avgDo,
                'n' => $n,
            ];
        }

        return [
            'rows' => $result,
            'targets' => [
                'dwelling' => $targetDw,
                'sailing' => $targetSa,
                'dooring' => $targetDo,
            ],
        ];
    }

    public function getInspeksiRingkasan(): array
    {
        $total = DB::table('units')->count();

        $sudah = DB::table('units as u')
            ->join('unit_inspections as ui', 'ui.unit_id', '=', 'u.id')
            ->distinct('u.id')
            ->count('u.id');

        $ng = DB::table('unit_inspection_items as uii')
            ->join('unit_inspections as ui', 'ui.id', '=', 'uii.unit_inspection_id')
            ->where('uii.result', 'ng')
            ->count();

        return [
            'total' => $total,
            'sudah' => $sudah,
            'belum' => max(0, $total - $sudah),
            'ng'    => $ng,
        ];
    }
}

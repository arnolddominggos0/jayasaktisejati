<?php

namespace App\Filament\Pages;

use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Customer;
use App\Models\Armada;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentMode;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string $view             = 'filament.pages.admin-dashboard';
    protected static ?string $title           = 'Dashboard';

    public string $dashboardView = 'all';
    public ?int $branchId = null;
    public ?string $mode = null;
    public string $period = 'weekly';
    public string $brandHex = '#0137A1';


    public function mount(): void
    {
        $this->dashboardView = 'all';
        $this->branchId      = null;
        $this->mode          = null;
        $this->period        = 'weekly';

        $this->form->fill([
            'dashboardView' => $this->dashboardView,
            'branchId'      => $this->branchId,
            'mode'          => $this->mode,
            'period'        => $this->period,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(['default' => 2, 'sm' => 4, 'lg' => 8])
                ->schema([
                    // Toggle tampilan: umum / TAM
                    Forms\Components\ToggleButtons::make('dashboardView')
                        ->label('Tampilan')
                        ->inline()
                        ->options([
                            'all' => 'Dashboard umum',
                            'tam' => 'Dashboard TAM (Manado)',
                        ])
                        ->default('all')
                        ->columnSpan(['default' => 2, 'lg' => 2]),

                    Forms\Components\Select::make('branchId')
                        ->label('Cabang')
                        ->placeholder('Semua cabang')
                        ->options(
                            DB::table('branches')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->columnSpan(['default' => 2, 'lg' => 2]),

                    Forms\Components\Select::make('mode')
                        ->label('Moda')
                        ->placeholder('Semua moda')
                        ->options([
                            ShipmentMode::Sea->value  => 'Laut',
                            ShipmentMode::Land->value => 'Darat',
                        ])
                        ->columnSpan(['default' => 2, 'lg' => 2]),

                    Forms\Components\Select::make('period')
                        ->label('Periode')
                        ->options([
                            'weekly'  => 'Mingguan (12 minggu)',
                            'monthly' => 'Bulanan (12 bulan)',
                        ])
                        ->default('weekly')
                        ->columnSpan(['default' => 2, 'lg' => 1]),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('reset')
                            ->label('Reset')
                            ->color('gray')
                            ->action(function (): void {
                                $this->dashboardView = 'all';
                                $this->branchId      = null;
                                $this->mode          = null;
                                $this->period        = 'weekly';

                                $this->form->fill([
                                    'dashboardView' => $this->dashboardView,
                                    'branchId'      => $this->branchId,
                                    'mode'          => $this->mode,
                                    'period'        => $this->period,
                                ]);
                            }),
                    ])
                        ->columnSpan(['default' => 2, 'lg' => 1])
                        ->alignEnd(),
                ]),
        ];
    }

    protected function baseShipmentQuery(): Builder
    {
        return Shipment::query()
            ->when($this->branchId, fn(Builder $q) => $q->where('branch_id', $this->branchId))
            ->when($this->mode, fn(Builder $q) => $q->where('mode', $this->mode));
    }

    protected function tamBaseQuery(): Builder
    {
        $cfg         = config('jss_kpi.manado', []);
        $customerIds = array_map('intval', $cfg['customer_ids'] ?? []);

        return $this->baseShipmentQuery()
            ->when(! empty($customerIds), fn(Builder $q) => $q->whereIn('customer_id', $customerIds))
            ->whereNotNull('delivered_at');
    }

    public function getKpis(): array
    {
        $q = $this->baseShipmentQuery();

        $totalAktif = (clone $q)
            ->whereNotIn('status', [ShipmentStatus::Cancelled, ShipmentStatus::Delivered])
            ->count();

        $pendingPickup = (clone $q)
            ->whereIn('status', [
                ShipmentStatus::Draft->value,
                ShipmentStatus::Pending->value,
                ShipmentStatus::Pickup->value,
            ])
            ->count();

        $armadaAktif = Armada::query()
            ->when($this->branchId, fn(Builder $qq) => $qq->where('branch_id', $this->branchId))
            ->where('is_active', true)
            ->count();

        $aktivitasHariIni = ShipmentTrack::query()
            ->when($this->branchId, fn(Builder $qq) => $qq->whereHas('shipment', fn(Builder $s) => $s->where('branch_id', $this->branchId)))
            ->when($this->mode, fn(Builder $qq) => $qq->whereHas('shipment', fn(Builder $s) => $s->where('mode', $this->mode)))
            ->whereDate('tracked_at', Carbon::today())
            ->count();

        $sparkline = collect(range(6, 0))->map(function (int $i) {
            $day = Carbon::today()->subDays($i);

            $count = ShipmentTrack::query()
                ->when($this->branchId, fn(Builder $qq) => $qq->whereHas('shipment', fn(Builder $s) => $s->where('branch_id', $this->branchId)))
                ->when($this->mode, fn(Builder $qq) => $qq->whereHas('shipment', fn(Builder $s) => $s->where('mode', $this->mode)))
                ->whereDate('tracked_at', $day)
                ->count();

            return [
                'label' => $day->format('d M'),
                'value' => $count,
            ];
        });

        return [
            'totalAktif'       => $totalAktif,
            'pendingPickup'    => $pendingPickup,
            'armadaAktif'      => $armadaAktif,
            'aktivitasHariIni' => $aktivitasHariIni,
            'sparkline'        => $sparkline->values()->all(),
        ];
    }

    public function getTrendSeries(): array
    {
        if ($this->period === 'monthly') {
            $labels = collect(range(11, 0))->map(
                fn(int $i) => Carbon::now()->subMonths($i)->format('M Y')
            );

            $series = $labels->map(function (string $label) {
                $month = Carbon::createFromFormat('M Y', $label)->startOfMonth();
                $end   = (clone $month)->endOfMonth();

                return $this->baseShipmentQuery()
                    ->whereBetween('created_at', [$month, $end])
                    ->count();
            });

            return [
                'labels' => $labels->values()->all(),
                'series' => $series->values()->all(),
            ];
        }

        $labels = collect(range(11, 0))->map(function (int $i) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end   = (clone $start)->endOfWeek();

            return $start->format('d M') . '–' . $end->format('d M');
        });

        $series = collect(range(11, 0))->map(function (int $i) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end   = (clone $start)->endOfWeek();

            return $this->baseShipmentQuery()
                ->whereBetween('created_at', [$start, $end])
                ->count();
        });

        return [
            'labels' => $labels->values()->all(),
            'series' => $series->values()->all(),
        ];
    }

    public function getStatusDistribution(): array
    {
        $statuses = [
            ShipmentStatus::Draft,
            ShipmentStatus::Pending,
            ShipmentStatus::Pickup,
            ShipmentStatus::Transit,
            ShipmentStatus::Delivered,
            ShipmentStatus::Hold,
            ShipmentStatus::Cancelled,
        ];

        $labels = [];
        $values = [];

        foreach ($statuses as $st) {
            $labels[] = $st->label();
            $values[] = $this->baseShipmentQuery()
                ->where('status', $st)
                ->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    public function getTopCustomers(): array
    {
        $rows = Customer::query()
            ->select(['customers.id', 'customers.name'])
            ->selectRaw('COUNT(shipments.id) as total')
            ->leftJoin('shipments', 'shipments.customer_id', '=', 'customers.id')
            ->when($this->branchId, fn(Builder $qq) => $qq->where('shipments.branch_id', $this->branchId))
            ->when($this->mode, fn(Builder $qq) => $qq->where('shipments.mode', $this->mode))
            ->whereBetween('shipments.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy(['customers.id', 'customers.name'])
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return $rows
            ->map(fn($r) => [
                'name'  => $r->name,
                'total' => (int) $r->total,
            ])
            ->values()
            ->all();
    }

    public function getLeadTimeSummary(): array
    {
        $avg = $this->baseShipmentQuery()
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (delivered_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

        $avgDays    = $avg ? round($avg, 1) : 0.0;
        $targetDays = 12;
        $progress   = $avgDays > 0
            ? max(0, min(100, (int) round(($targetDays / max($avgDays, 0.01)) * 50)))
            : 0;

        return [
            'avg_days' => $avgDays,
            'target'   => $targetDays,
            'progress' => $progress,
        ];
    }

    public function getRecentActivities(): array
    {
        return ShipmentTrack::query()
            ->with(['shipment:id,code', 'user:id,name'])
            ->when($this->branchId, fn(Builder $qq) => $qq->whereHas('shipment', fn(Builder $s) => $s->where('branch_id', $this->branchId)))
            ->when($this->mode, fn(Builder $qq) => $qq->whereHas('shipment', fn(Builder $s) => $s->where('mode', $this->mode)))
            ->latest('tracked_at')
            ->limit(12)
            ->get()
            ->map(function (ShipmentTrack $t) {
                return [
                    'shipment_code' => $t->shipment?->code ?? '-',
                    'status'        => $t->status instanceof \BackedEnum ? $t->status->value : (string) $t->status,
                    'when'          => Carbon::parse($t->tracked_at)->diffForHumans(),
                    'who'           => $t->user?->name ?? 'Sistem',
                    'note'          => $t->note,
                ];
            })
            ->all();
    }

    public function getTamKpiSummary(): array
    {
        $cfg         = config('jss_kpi.manado', []);
        $thresholds  = $cfg['thresholds'] ?? [];
        $targetTotal = (int) ($thresholds['total_days']['normal'] ?? 19);

        $rows = $this->tamBaseQuery()
            ->whereBetween('delivered_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('tracks')
            ->get();

        $total  = 0;
        $onTime = 0;
        $late   = 0;

        foreach ($rows as $s) {
            if (! method_exists($s, 'evaluateKpiForManado')) {
                continue;
            }

            $ev = $s->evaluateKpiForManado();

            if (! ($ev['applies'] ?? false)) {
                continue;
            }

            $total++;

            $badge = $ev['badge'] ?? null;

            if ($badge === 'On Time') {
                $onTime++;
            } elseif ($badge === 'Late') {
                $late++;
            }
        }

        $onPct   = $total > 0 ? round(($onTime / $total) * 100, 1) : 0.0;
        $latePct = $total > 0 ? round(($late / $total) * 100, 1) : 0.0;

        return [
            'total'        => $total,
            'on_time'      => $onTime,
            'late'         => $late,
            'on_time_pct'  => $onPct,
            'late_pct'     => $latePct,
            'target_total' => $targetTotal,
        ];
    }

    public function getTamLateShipments(): array
    {
        $rows = $this->tamBaseQuery()
            ->latest('delivered_at')
            ->limit(30)
            ->with('tracks')
            ->get();

        $result = [];

        foreach ($rows as $s) {
            if (! method_exists($s, 'evaluateKpiForManado')) {
                continue;
            }

            $ev = $s->evaluateKpiForManado();

            if (! ($ev['applies'] ?? false)) {
                continue;
            }

            if (($ev['badge'] ?? null) !== 'Late') {
                continue;
            }

            $result[] = [
                'code'    => $s->code,
                'late_by' => $ev['late_days'] ?? null,
                'summary' => $s->kpiManadoSummaryText() ?? null,
            ];

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

        $targetDw    = (float) ($thresholds['dwelling_days'] ?? 6);
        $targetSa    = (float) ($thresholds['sailing_days'] ?? 10);
        $targetDo    = (float) ($thresholds['dooring_days'] ?? 2);
        $targetTotal = (float) ($thresholds['total_days']['normal'] ?? 19);

        $rows = $this->tamBaseQuery()
            ->whereBetween('delivered_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('tracks')
            ->get();

        $sumDw    = 0.0;
        $sumSa    = 0.0;
        $sumDo    = 0.0;
        $sumTotal = 0.0;
        $n        = 0;

        foreach ($rows as $s) {
            if (! method_exists($s, 'evaluateKpiForManado')) {
                continue;
            }

            $ev = $s->evaluateKpiForManado();

            if (! ($ev['applies'] ?? false)) {
                continue;
            }

            $sumDw    += (float) ($ev['dwelling_days'] ?? 0);
            $sumSa    += (float) ($ev['sailing_days'] ?? 0);
            $sumDo    += (float) ($ev['dooring_days'] ?? 0);
            $sumTotal += (float) ($ev['total_days'] ?? 0);
            $n++;
        }

        if ($n > 0) {
            $avgDw    = $sumDw / $n;
            $avgSa    = $sumSa / $n;
            $avgDo    = $sumDo / $n;
            $avgTotal = $sumTotal / $n;
        } else {
            $avgDw = $avgSa = $avgDo = $avgTotal = 0.0;
        }

        $dwPct  = $targetDw > 0 ? round(($avgDw / $targetDw) * 100, 1) : 0.0;
        $saPct  = $targetSa > 0 ? round(($avgSa / $targetSa) * 100, 1) : 0.0;
        $doPct  = $targetDo > 0 ? round(($avgDo / $targetDo) * 100, 1) : 0.0;
        $totPct = $targetTotal > 0 ? round(($avgTotal / $targetTotal) * 100, 1) : 0.0;

        return [
            'labels'  => ['Dwelling', 'Sailing', 'Dooring', 'Total'],
            'values'  => [$dwPct, $saPct, $doPct, $totPct],
            'targets' => [
                'dwelling' => $targetDw,
                'sailing'  => $targetSa,
                'dooring'  => $targetDo,
                'total'    => $targetTotal,
            ],
        ];
    }
}

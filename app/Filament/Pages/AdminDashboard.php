<?php

namespace App\Filament\Pages;

use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Customer;
use App\Models\Armada;
use App\Models\Manpower;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentMode;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string $view = 'filament.pages.admin-dashboard';
    protected static ?string $title = 'Dashboard';

    // Global filters state
    public ?int   $branchId = null;     // Cabang (opsional)
    public ?string $mode    = null;     // 'sea' | 'land' (ShipmentMode) | null
    public string $period   = 'weekly'; // 'weekly' | 'monthly'

    // Brand
    public string $brandHex = '#0137A1';

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => null,
            'mode'      => null,
            'period'    => 'weekly',
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(['default' => 3, 'sm' => 3, 'lg' => 6])
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->label('Cabang')
                        ->placeholder('Semua cabang')
                        ->options(
                            DB::table('branches')->orderBy('name')->pluck('name', 'id')
                        )
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->branchId = $state),

                    Forms\Components\Select::make('mode')
                        ->label('Moda')
                        ->placeholder('Semua moda')
                        ->options([
                            ShipmentMode::Sea->value  => 'Laut',
                            ShipmentMode::Land->value => 'Darat',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->mode = $state),

                    Forms\Components\Select::make('period')
                        ->label('Periode')
                        ->options([
                            'weekly'  => 'Mingguan (12 minggu)',
                            'monthly' => 'Bulanan (12 bulan)',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->period = $state)
                        ->columnSpan(['default' => 3, 'lg' => 2]),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('reset')
                            ->label('Reset')
                            ->color('gray')
                            ->action(function () {
                                $this->branchId = null;
                                $this->mode     = null;
                                $this->period   = 'weekly';
                                $this->form->fill([
                                    'branch_id' => null,
                                    'mode'      => null,
                                    'period'    => 'weekly',
                                ]);
                            }),
                    ])->columnSpan(1)->alignEnd(),
                ]),
        ];
    }

    public function getKpis(): array
    {
        $q = $this->baseShipmentQuery();

        $totalAktif = (clone $q)
            ->whereNotIn('status', [ShipmentStatus::Cancelled, ShipmentStatus::Delivered])
            ->count();

        $pendingPickup = (clone $q)
            ->whereIn('status', [ShipmentStatus::Pending, ShipmentStatus::Pickup])
            ->count();

        $armadaAktif = Armada::query()
            ->when($this->branchId, fn ($qq) => $qq->where('branch_id', $this->branchId))
            ->where('is_active', true)
            ->count();

        $aktivitasHariIni = ShipmentTrack::query()
            ->when($this->branchId, fn ($qq) => $qq->whereHas('shipment', fn ($s) => $s->where('branch_id', $this->branchId)))
            ->when($this->mode, fn ($qq) => $qq->whereHas('shipment', fn ($s) => $s->where('mode', $this->mode)))
            ->whereDate('tracked_at', Carbon::today())
            ->count();

        // Sparkline 7 hari terakhir (jumlah aktivitas tracking)
        $sparkline = collect(range(6, 0))->map(function ($i) {
            $day = Carbon::today()->subDays($i);
            $count = ShipmentTrack::query()
                ->when($this->branchId, fn ($qq) => $qq->whereHas('shipment', fn ($s) => $s->where('branch_id', $this->branchId)))
                ->when($this->mode, fn ($qq) => $qq->whereHas('shipment', fn ($s) => $s->where('mode', $this->mode)))
                ->whereDate('tracked_at', $day)
                ->count();
            return ['label' => $day->format('d M'), 'value' => $count];
        });

        return [
            'totalAktif'       => $totalAktif,
            'pendingPickup'    => $pendingPickup,
            'armadaAktif'      => $armadaAktif,
            'aktivitasHariIni' => $aktivitasHariIni,
            'sparkline'        => $sparkline,
        ];
    }

    public function getTrendSeries(): array
    {
        if ($this->period === 'monthly') {
            // 12 bulan terakhir
            $labels = collect(range(11, 0))->map(fn ($i) => Carbon::now()->subMonths($i)->format('M Y'));
            $series = $labels->map(function ($label) {
                $month = Carbon::createFromFormat('M Y', $label)->startOfMonth();
                $end   = (clone $month)->endOfMonth();

                return $this->baseShipmentQuery()
                    ->whereBetween('created_at', [$month, $end])
                    ->count();
            });

            return ['labels' => $labels, 'series' => $series];
        }

        // Default: weekly, 12 minggu
        $labels = collect(range(11, 0))->map(function ($i) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end   = (clone $start)->endOfWeek();
            return $start->format('d M') . '–' . $end->format('d M');
        });

        $series = collect(range(11, 0))->map(function ($i) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end   = (clone $start)->endOfWeek();

            return $this->baseShipmentQuery()
                ->whereBetween('created_at', [$start, $end])
                ->count();
        });

        return ['labels' => $labels, 'series' => $series];
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
            $labels[] = $st->name;
            $values[] = $this->baseShipmentQuery()->where('status', $st)->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getTopCustomers(): array
    {
        $rows = Customer::query()
            ->select(['customers.id', 'customers.name'])
            ->selectRaw('COUNT(shipments.id) as total')
            ->leftJoin('shipments', 'shipments.customer_id', '=', 'customers.id')
            ->when($this->branchId, fn ($qq) => $qq->where('shipments.branch_id', $this->branchId))
            ->when($this->mode, fn ($qq) => $qq->where('shipments.mode', $this->mode))
            ->whereBetween('shipments.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy(['customers.id', 'customers.name'])
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return $rows->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total])->values()->all();
    }

    public function getLeadTimeSummary(): array
    {
        // contoh sederhana: beda hari antara created_at dan delivered_at
        $avg = $this->baseShipmentQuery()
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (delivered_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

        $avgDays = $avg ? round($avg, 1) : 0.0;

        $targetDays = 12; // silakan sesuaikan
        $progress   = $avgDays > 0 ? max(0, min(100, (int) round(($targetDays / max($avgDays, 0.01)) * 50))) : 0;

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
            ->when($this->branchId, fn ($qq) => $qq->whereHas('shipment', fn ($s) => $s->where('branch_id', $this->branchId)))
            ->when($this->mode, fn ($qq) => $qq->whereHas('shipment', fn ($s) => $s->where('mode', $this->mode)))
            ->latest('tracked_at')
            ->limit(12)
            ->get()
            ->map(function ($t) {
                return [
                    'shipment_code' => $t->shipment?->code ?? '-',
                    'status'        => (string) $t->status,
                    'when'          => Carbon::parse($t->tracked_at)->diffForHumans(),
                    'who'           => $t->user?->name ?? 'Sistem',
                    'note'          => $t->note,
                ];
            })->all();
    }

    // ---------- helpers ----------

    protected function baseShipmentQuery(): Builder
    {
        return Shipment::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->mode, fn ($q) => $q->where('mode', $this->mode));
    }
}

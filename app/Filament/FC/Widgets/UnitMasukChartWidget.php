<?php

namespace App\Filament\FC\Widgets;

use App\Models\BriefingSession;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Bar chart tren unit masuk yard per bulan (tahun berjalan).
 * Hanya query briefing_sessions — JANGAN JOIN ke attendance (multiplikasi).
 */
class UnitMasukChartWidget extends ChartWidget
{
    protected static ?string $heading    = 'Actual Unit Handover — Tren Bulanan';
    protected static ?string $maxHeight  = '260px';
    protected int|string|array $columnSpan = 'full';

    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar',  4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul',  8 => 'Ags',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    protected function getData(): array
    {
        $year     = now()->year;
        $depotId  = $this->resolveDepotId();
        $branchId = $this->resolveBranchId();

        // Dual-source: pre-cutoff uses stored unit_masuk_yard; post-cutoff uses Handover tracks.
        $effectiveUnit = BriefingSession::effectiveUnitSqlExpression();
        $rows = DB::table('briefing_sessions')
            ->selectRaw("
                EXTRACT(MONTH FROM date)::int              AS month_num,
                COALESCE(SUM({$effectiveUnit}), 0)::int    AS total_units
            ")
            ->whereYear('date', $year)
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->when(! $depotId && $branchId, fn ($q) => $q->whereIn(
                'depot_id',
                DB::table('depots')->where('branch_id', $branchId)->select('id')
            ))
            ->groupByRaw('EXTRACT(MONTH FROM date)')
            ->orderByRaw('EXTRACT(MONTH FROM date)')
            ->get()
            ->keyBy('month_num');

        $currentMonth = now()->month;
        $labels       = [];
        $data         = [];

        for ($m = 1; $m <= $currentMonth; $m++) {
            $labels[] = self::MONTH_LABELS[$m];
            $data[]   = isset($rows[$m]) ? (int) $rows[$m]->total_units : 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Actual Unit Handover',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(14, 165, 233, 0.75)',  // sky-500
                    'borderColor'     => 'rgb(2, 132, 199)',            // sky-600
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    // ── Scope helpers ────────────────────────────────────────────────────────

    private function resolveDepotId(): ?int
    {
        $u = Filament::auth()->user();
        if (! $u) {
            return null;
        }

        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        if (isset($u->scope_unit_type) && $u->scope_unit_type === 'depot' && $u->scope_unit_id) {
            return (int) $u->scope_unit_id;
        }

        $raw = DB::table('depots')->where('coordinator_user_id', $u->id)->value('id');

        return $raw ? (int) $raw : null;
    }

    private function resolveBranchId(): ?int
    {
        $u = Filament::auth()->user();
        if (! $u) {
            return null;
        }

        if (app()->bound('scope.branch_id') && app('scope.branch_id') !== null) {
            return (int) app('scope.branch_id');
        }

        return method_exists($u, 'effectiveBranchId') ? ($u->effectiveBranchId() ?? null) : null;
    }
}

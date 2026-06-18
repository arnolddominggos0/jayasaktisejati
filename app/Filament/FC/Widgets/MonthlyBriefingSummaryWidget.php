<?php

namespace App\Filament\FC\Widgets;

use App\Models\BriefingSession;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tabel ringkasan bulanan untuk Dashboard FC.
 * Menampilkan: Bulan | Sesi | Unit Masuk | READY | Readiness %
 * Scope: tahun berjalan, filtered by depot/branch user.
 */
class MonthlyBriefingSummaryWidget extends Widget
{
    protected static string $view = 'filament.fc.widgets.monthly-briefing-summary';

    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $depotId  = $this->resolveDepotId();
        $branchId = $this->resolveBranchId();
        $year     = (int) now()->format('Y');
        $month    = (int) now()->format('m');

        $depotFilter = function ($q) use ($depotId, $branchId) {
            if ($depotId) {
                $q->where('depot_id', $depotId);
            } elseif ($branchId) {
                $q->whereIn('depot_id',
                    DB::table('depots')->where('branch_id', $branchId)->select('id')
                );
            }
        };

        // Dual-source: pre-cutoff uses stored unit_masuk_yard; post-cutoff uses Handover tracks.
        $effectiveUnit = BriefingSession::effectiveUnitSqlExpression();

        // Aggregate per bulan
        $rows = DB::table('briefing_sessions')
            ->selectRaw("
                EXTRACT(MONTH FROM date)::int                                     AS bulan,
                COUNT(*)::int                                                      AS total_sesi,
                COALESCE(SUM({$effectiveUnit}), 0)::int                           AS total_unit,
                SUM(CASE WHEN summary_sufficient = true  THEN 1 ELSE 0 END)::int  AS sesi_ready,
                SUM(CASE WHEN summary_sufficient = false THEN 1 ELSE 0 END)::int  AS sesi_ng
            ")
            ->whereYear('date', $year)
            ->when(true, $depotFilter)
            ->groupByRaw('EXTRACT(MONTH FROM date)')
            ->orderByRaw('EXTRACT(MONTH FROM date)')
            ->get()
            ->keyBy('bulan');

        // Build full list Jan–bulan_berjalan
        $months = collect(range(1, $month))->map(function ($m) use ($rows, $year) {
            $row        = $rows->get($m);
            $totalSesi  = (int) ($row->total_sesi  ?? 0);
            $totalUnit  = (int) ($row->total_unit  ?? 0);
            $sesiReady  = (int) ($row->sesi_ready  ?? 0);
            $sesiNg     = (int) ($row->sesi_ng     ?? 0);
            $readiness  = $totalSesi > 0 ? round($sesiReady / $totalSesi * 100, 1) : null;

            return [
                'bulan'       => $m,
                'label'       => Carbon::createFromDate($year, $m, 1)->translatedFormat('F'),
                'total_sesi'  => $totalSesi,
                'total_unit'  => $totalUnit,
                'sesi_ready'  => $sesiReady,
                'sesi_ng'     => $sesiNg,
                'readiness'   => $readiness,
                'has_data'    => $totalSesi > 0,
            ];
        });

        // Totals row
        $grandTotal     = $months->sum('total_sesi');
        $grandUnit      = $months->sum('total_unit');
        $grandReady     = $months->sum('sesi_ready');
        $grandNg        = $months->sum('sesi_ng');
        $grandReadiness = $grandTotal > 0 ? round($grandReady / $grandTotal * 100, 1) : null;

        return [
            'year'            => $year,
            'months'          => $months,
            'grand_total'     => $grandTotal,
            'grand_unit'      => $grandUnit,
            'grand_ready'     => $grandReady,
            'grand_ng'        => $grandNg,
            'grand_readiness' => $grandReadiness,
        ];
    }

    // ── Scope helpers ─────────────────────────────────────────────────────────

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

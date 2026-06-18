<?php

namespace App\Filament\FC\Widgets;

use App\Models\BriefingSession;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * 4 KPI kondisi operasional HARI INI untuk Dashboard FC.
 * Domain: yard + MP — bukan data historis / tahunan.
 * Data historis ada di Monitoring Operasional (OperationalStatsWidget).
 */
class DashboardOperationalWidget extends Widget
{
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan    = 'full';

    protected function getStats(): array
    {
        $depotId  = $this->resolveDepotId();
        $branchId = $this->resolveBranchId();
        $today    = now()->toDateString();

        $depotFilter = function ($q) use ($depotId, $branchId) {
            if ($depotId) {
                $q->where('depot_id', $depotId);
            } elseif ($branchId) {
                $q->whereIn('depot_id',
                    DB::table('depots')->where('branch_id', $branchId)->select('id')
                );
            }
        };

        // ── Sesi hari ini ────────────────────────────────────────────────────
        $session = DB::table('briefing_sessions')
            ->whereDate('date', $today)
            ->when(true, $depotFilter)
            ->select('id', 'unit_masuk_yard', 'summary_headcount', 'summary_sufficient')
            ->first();

        $hasSession = $session !== null;
        $sessionId  = $hasSession ? $session->id : null;
        $needMp     = $hasSession ? (int) ($session->summary_headcount ?? 0) : 0;

        // Dual-source: pre-cutoff → stored value; post-cutoff → Handover track count.
        if (! $hasSession) {
            $unitHariIni = 0;
        } elseif ($today < BriefingSession::YARD_CUTOFF) {
            $unitHariIni = (int) ($session->unit_masuk_yard ?? 0);
        } else {
            $unitHariIni = (int) DB::table('units as u')
                ->join('shipments as s', 's.id', '=', 'u.shipment_id')
                ->join('shipment_tracks as st', function ($j) use ($today) {
                    $j->on('st.shipment_id', '=', 's.id')
                      ->where('st.status', 'handover')
                      ->whereNotNull('st.tracked_at')
                      ->whereDate('st.tracked_at', $today);
                })
                ->where('s.status', '!=', 'draft')
                ->when($depotId, fn ($q) => $q->where('s.assigned_depot_id', $depotId))
                ->when(! $depotId && $branchId, fn ($q) => $q->whereIn(
                    's.assigned_depot_id',
                    DB::table('depots')->where('branch_id', $branchId)->select('id')
                ))
                ->count('u.id');
        }

        // ── MP Hadir + MP Siap Kerja — satu query, dua kolom ────────────────────────
        $mpHadir      = 0;
        $mpSiapKerja  = 0;
        if ($sessionId) {
            $attAgg = DB::table('briefing_attendances')
                ->where('session_id', $sessionId)
                ->selectRaw("
                    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END)::int AS hadir,
                    SUM(CASE
                        WHEN attendance_status = 'present'
                         AND has_ppe = true
                         AND (
                             recheck_result = 'FIT'
                             OR (fit_status = 'FIT' AND recheck_result IS NULL)
                         )
                        THEN 1 ELSE 0
                    END)::int AS siap_kerja
                ")
                ->first();
            $mpHadir     = (int) ($attAgg->hadir      ?? 0);
            $mpSiapKerja = (int) ($attAgg->siap_kerja ?? 0);
        }

        // ── Readiness: Siap Kerja >= Need MP ────────────────────────────────
        $isReady = $hasSession && $needMp > 0 && $mpSiapKerja >= $needMp;

        if (! $hasSession) {
            $readinessLabel = 'Belum Ada Briefing';
            $readinessColor = 'gray';
            $readinessDesc  = now()->translatedFormat('l, d F Y');
        } elseif ($isReady) {
            $readinessLabel = 'SIAP';
            $readinessColor = 'success';
            $readinessDesc  = 'Siap Kerja ≥ Need MP — operasional dapat berjalan';
        } else {
            $readinessLabel = 'BELUM SIAP';
            $readinessColor = 'danger';
            $readinessDesc  = 'Siap Kerja < Need MP — belum memenuhi syarat';
        }

        // ── Warna kartu MP ───────────────────────────────────────────────────
        $hadirColor      = ! $hasSession ? 'gray'
            : ($mpHadir >= $needMp ? 'success' : ($mpHadir >= (int) ceil($needMp * 0.6) ? 'warning' : 'danger'));
        $siapKerjaColor  = ! $hasSession ? 'gray'
            : ($mpSiapKerja >= $needMp ? 'success' : ($mpSiapKerja >= (int) ceil($needMp * 0.6) ? 'warning' : 'danger'));

        return [
            Stat::make('Actual Unit Handover Hari Ini', $hasSession ? number_format($unitHariIni) . ' unit' : '—')
                ->description($hasSession ? now()->translatedFormat('d F Y') : 'Belum ada sesi briefing')
                ->descriptionIcon('heroicon-m-cube')
                ->color($hasSession ? 'info' : 'gray'),

            Stat::make('Need MP', $hasSession ? $needMp . ' orang' : '—')
                ->description('Kebutuhan tim SOP hari ini')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($hasSession ? 'primary' : 'gray'),

            Stat::make('MP Hadir', $hasSession ? $mpHadir . ' orang' : '—')
                ->description($hasSession ? 'attendance_status = present' : 'Belum ada data')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color($hadirColor),

            Stat::make('MP Siap Kerja', $hasSession ? $mpSiapKerja . ' orang' : '—')
                ->description($hasSession ? 'Hadir + APD lengkap + FIT / recheck FIT' : 'Belum ada data')
                ->descriptionIcon('heroicon-m-heart')
                ->color($siapKerjaColor),

            Stat::make('Readiness', $readinessLabel)
                ->description($readinessDesc)
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($readinessColor),
        ];
    }

    // ── Scope helpers ─────────────────────────────────────────────────────────

    private function resolveDepotId(): ?int
    {
        $u = Filament::auth()->user();
        if (! $u) return null;
        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) return (int) app('scope.depot_id');
        if (isset($u->scope_unit_type) && $u->scope_unit_type === 'depot' && $u->scope_unit_id) return (int) $u->scope_unit_id;
        $raw = DB::table('depots')->where('coordinator_user_id', $u->id)->value('id');
        return $raw ? (int) $raw : null;
    }

    private function resolveBranchId(): ?int
    {
        $u = Filament::auth()->user();
        if (! $u) return null;
        if (app()->bound('scope.branch_id') && app('scope.branch_id') !== null) return (int) app('scope.branch_id');
        return method_exists($u, 'effectiveBranchId') ? ($u->effectiveBranchId() ?? null) : null;
    }
}

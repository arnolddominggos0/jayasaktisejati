<?php

namespace App\Filament\FC\Widgets;

use App\Models\BriefingSession;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * KPI periodik (tahunan) untuk halaman Monitoring Operasional.
 * Sumber: briefing_sessions (unit + readiness) + briefing_attendances (kehadiran).
 * Tidak pernah JOIN keduanya sekaligus — mencegah multiplikasi nilai.
 *
 * Domain: HISTORI & ANALISA — bukan data hari ini (itu ada di Dashboard).
 */
class OperationalStatsWidget extends Widget
{
    protected static ?string $pollingInterval = null;
    protected int|string|array $columnSpan    = 'full';

    protected function getStats(): array
    {
        $year     = now()->year;
        $depotId  = $this->resolveDepotId();
        $branchId = $this->resolveBranchId();

        // ── 1. Agregat level sesi — JANGAN JOIN ke attendance ─────────────────
        $effectiveUnit = BriefingSession::effectiveUnitSqlExpression();
        $sess = DB::table('briefing_sessions')
            ->selectRaw("
                COUNT(*)::int                                                     AS total_sessions,
                COALESCE(SUM({$effectiveUnit}), 0)::int                          AS total_units,
                SUM(CASE WHEN summary_sufficient = true THEN 1 ELSE 0 END)::int  AS ok_sessions
            ")
            ->whereYear('date', $year)
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->when(! $depotId && $branchId, fn ($q) => $q->whereIn(
                'depot_id',
                DB::table('depots')->where('branch_id', $branchId)->select('id')
            ))
            ->first();

        // ── 2. Kehadiran MP — query terpisah ──────────────────────────────────
        $attendQ = DB::table('briefing_attendances as ba')
            ->join('briefing_sessions as bs', 'bs.id', '=', 'ba.session_id')
            ->whereYear('bs.date', $year)
            ->where('ba.attendance_status', 'present');

        if ($depotId) {
            $attendQ->where('bs.depot_id', $depotId);
        } elseif ($branchId) {
            $attendQ->whereIn('bs.depot_id',
                DB::table('depots')->where('branch_id', $branchId)->select('id')
            );
        }

        $totalAttend = $attendQ->count();

        // ── 3. Komputasi ──────────────────────────────────────────────────────
        $totalSessions = (int) ($sess->total_sessions ?? 0);
        $totalUnits    = (int) ($sess->total_units    ?? 0);
        $okSessions    = (int) ($sess->ok_sessions    ?? 0);
        $readiness     = $totalSessions > 0
            ? round(($okSessions / $totalSessions) * 100, 1)
            : 0.0;

        $yearLabel     = 'Tahun ' . $year;

        return [
            Stat::make('Total Sesi Briefing', number_format($totalSessions))
                ->description($yearLabel)
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Total Actual Unit Handover', number_format($totalUnits))
                ->description($yearLabel)
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Total Kehadiran MP', number_format($totalAttend))
                ->description('MP hadir (present) · ' . $yearLabel)
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Readiness Rate', $readiness . '%')
                ->description("{$okSessions} dari {$totalSessions} sesi OK · {$yearLabel}")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($readiness >= 80 ? 'success' : ($readiness >= 50 ? 'warning' : 'danger')),
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

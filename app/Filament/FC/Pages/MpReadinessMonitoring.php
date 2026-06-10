<?php

namespace App\Filament\FC\Pages;

use App\Filament\FC\Resources\BriefingSessionResource;
use App\Filament\FC\Resources\ContainerReadinessSessionResource;
use App\Models\BriefingSession;
use App\Models\ContainerReadinessSession;
use App\Models\Depot;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MpReadinessMonitoring extends Page
{
    protected static ?string $navigationGroup = 'Operasional Lapangan';
    protected static ?string $navigationLabel = 'Monitoring Operasional';
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?int    $navigationSort  = 5;

    protected static string $view = 'filament.fc.pages.mp-readiness-monitoring';

    public function getHeading(): string
    {
        return 'Monitoring Operasional';
    }

    public function getSubheading(): ?string
    {
        return 'Briefing · Kehadiran · Kesehatan · APD · Readiness';
    }

    public int  $filterMonth;
    public int  $filterYear;
    public ?int $filterDepotId    = null;
    public ?int $selectedSessionId = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    // ── Header actions — semua action operasional harian ada di sini ────────────
    //    Dashboard hanya monitoring cepat (read only).
    //    Monitoring Operasional adalah command center: input + analisa.

    protected function getHeaderActions(): array
    {
        $today   = today()->toDateString();
        $depotId = $this->resolveDefaultDepotId();

        // ── Briefing action ───────────────────────────────────────────────────
        $existingBriefing = BriefingSession::query()
            ->whereDate('date', $today)
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->select('id')
            ->first();

        $briefingAction = $existingBriefing
            ? Action::make('edit_briefing')
                ->label('Edit Briefing Hari Ini')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->url(BriefingSessionResource::getUrl('edit', ['record' => $existingBriefing->id]))
            : Action::make('create_briefing')
                ->label('+ Mulai Briefing Hari Ini')
                ->icon('heroicon-m-clipboard-document-check')
                ->color('primary')
                ->url(BriefingSessionResource::getUrl('create'));

        // ── Container action ──────────────────────────────────────────────────
        $existingContainer = ContainerReadinessSession::query()
            ->whereDate('session_date', $today)
            ->select('id')
            ->first();

        $containerAction = $existingContainer
            ? Action::make('edit_container')
                ->label('Edit Container Hari Ini')
                ->icon('heroicon-m-archive-box')
                ->color('warning')
                ->url(ContainerReadinessSessionResource::getUrl('edit', ['record' => $existingContainer->id]))
            : Action::make('input_container')
                ->label('+ Input Container Hari Ini')
                ->icon('heroicon-m-archive-box-arrow-down')
                ->color('info')
                ->url(ContainerReadinessSessionResource::getUrl('create'));

        return [$briefingAction, $containerAction];
    }

    protected function getHeaderWidgets(): array
    {
        // KPI summary tahunan dihapus — Dashboard adalah command center harian.
        // Monitoring Operasional = analisa & histori periode (tidak ada widget duplikat).
        return [];
    }

    public function mount(): void
    {
        $this->filterMonth   = (int) now()->format('m');
        $this->filterYear    = (int) now()->format('Y');
        $this->filterDepotId = $this->resolveDefaultDepotId();
    }

    public function updatedFilterDepotId(mixed $value): void
    {
        $this->filterDepotId     = filled($value) ? (int) $value : null;
        $this->selectedSessionId = null;
    }

    public function updatedFilterMonth(mixed $value): void
    {
        $this->filterMonth       = (int) $value;
        $this->selectedSessionId = null;
    }

    public function updatedFilterYear(mixed $value): void
    {
        $this->filterYear        = (int) $value;
        $this->selectedSessionId = null;
    }

    public function selectSession(int $sessionId): void
    {
        $this->selectedSessionId = $this->selectedSessionId === $sessionId ? null : $sessionId;
    }

    public function closeDetail(): void
    {
        $this->selectedSessionId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scope resolution — mirrors BriefingSessionResource pattern
    |--------------------------------------------------------------------------
    */

    protected function resolveDefaultDepotId(): ?int
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return null;
        }

        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        if ($user->scope_unit_type === 'depot' && $user->scope_unit_id) {
            return (int) $user->scope_unit_id;
        }

        $raw = Depot::where('coordinator_user_id', $user->id)->value('id');

        return $raw ? (int) $raw : null;
    }

    protected function resolveBranchId(): ?int
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return null;
        }

        if (app()->bound('scope.branch_id') && app('scope.branch_id') !== null) {
            return (int) app('scope.branch_id');
        }

        return $user->effectiveBranchId() ?? null;
    }

    protected function hasMultiDepotAccess(): bool
    {
        return $this->resolveDefaultDepotId() === null
            && $this->resolveBranchId() !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    */

    protected function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $start    = Carbon::createFromDate($this->filterYear, $this->filterMonth, 1)->startOfMonth();
        $end      = $start->copy()->endOfMonth();
        $depotId  = $this->filterDepotId;
        $branchId = $this->resolveBranchId();

        return BriefingSession::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->when(! $depotId && $branchId, fn ($q) => $q->whereHas(
                'depot',
                fn ($dq) => $dq->where('branch_id', $branchId)
            ))
            ->withCount([
                'attendances as mp_attend' => fn ($q) => $q->where('attendance_status', 'present'),
            ])
            ->with('depot:id,name')
            ->orderBy('date');
    }

    /*
    |--------------------------------------------------------------------------
    | View data
    |--------------------------------------------------------------------------
    */

    protected function getViewData(): array
    {
        $sessions = $this->buildQuery()->get();

        $rows = $sessions->map(function ($session) {
            $need   = (int) ($session->summary_headcount ?? 0);
            $attend = (int) $session->mp_attend;
            $gap    = $attend - $need;
            $ok     = $need > 0 ? ($attend >= $need) : null;

            // Priority 1: dedicated column (new data from Filament).
            // Priority 2: regex on notes (legacy AppSheet data — kept for transition).
            $unit = $session->unit_masuk_yard !== null
                ? (int) $session->unit_masuk_yard
                : (
                    ($session->notes && preg_match('/Unit Masuk Yard\/PDC:\s*(\d+)/i', $session->notes, $m))
                        ? (int) $m[1]
                        : null
                );

            return [
                'session_id' => $session->id,
                'date'       => $session->date,
                'day'        => (int) $session->date->format('j'),
                'date_label' => $session->date->translatedFormat('d M Y'),
                'depot'      => $session->depot?->name ?? '-',
                'mp_need'    => $need,
                'mp_attend'  => $attend,
                'gap'        => $gap,
                'gap_label'  => $gap > 0 ? "+{$gap}" : (string) $gap,
                'status'     => $ok === null ? '-' : ($ok ? 'OK' : 'NG'),
                'ok'         => $ok,
                'unit_masuk' => $unit,
            ];
        });

        // KPI aggregates
        $totalBriefing = $rows->count();
        $totalNeed     = $rows->sum('mp_need');
        $totalAttend   = $rows->sum('mp_attend');
        $okCount       = $rows->where('ok', true)->count();
        $ngCount       = $rows->where('ok', false)->count();
        $totalGap      = $totalAttend - $totalNeed;
        $readinessOk   = $totalBriefing > 0 ? round(($okCount / $totalBriefing) * 100, 1) : 0.0;
        $readinessNg    = $totalBriefing > 0 ? round(($ngCount / $totalBriefing) * 100, 1) : 0.0;
        $totalUnitMasuk = (int) $rows->sum('unit_masuk');

        // Monthly matrix — aggregate across depots if multi-depot
        $daysInMonth = Carbon::createFromDate($this->filterYear, $this->filterMonth, 1)->daysInMonth;

        $matrixByDay = [];
        foreach ($rows as $row) {
            $d = $row['day'];
            if (! isset($matrixByDay[$d])) {
                $matrixByDay[$d] = ['need' => 0, 'attend' => 0, 'unit' => 0, 'date' => $row['date']];
            }
            $matrixByDay[$d]['need']   += $row['mp_need'];
            $matrixByDay[$d]['attend'] += $row['mp_attend'];
            $matrixByDay[$d]['unit']   += ($row['unit_masuk'] ?? 0);
        }

        $matrix = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (isset($matrixByDay[$d])) {
                $agg  = $matrixByDay[$d];
                $gap  = $agg['attend'] - $agg['need'];
                $ok   = $agg['need'] > 0 ? ($agg['attend'] >= $agg['need']) : null;
                $matrix[$d] = [
                    'date'       => $agg['date'],
                    'mp_need'    => $agg['need'],
                    'mp_attend'  => $agg['attend'],
                    'gap'        => $gap,
                    'gap_label'  => $gap >= 0 ? "+{$gap}" : (string) $gap,
                    'status'     => $ok === null ? '-' : ($ok ? 'OK' : 'NG'),
                    'ok'         => $ok,
                    'unit_masuk' => $agg['unit'],
                ];
            } else {
                $matrix[$d] = null;
            }
        }

        // Filter options
        $currentYear  = (int) now()->format('Y');
        $yearOptions  = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
            $yearOptions[$y] = (string) $y;
        }

        $monthOptions = [
            1 => 'Januari',   2 => 'Februari',  3 => 'Maret',    4 => 'April',
            5 => 'Mei',       6 => 'Juni',       7 => 'Juli',     8 => 'Agustus',
            9 => 'September', 10 => 'Oktober',  11 => 'November', 12 => 'Desember',
        ];

        $hasMultiDepot = $this->hasMultiDepotAccess();
        $depotOptions  = [];
        if ($hasMultiDepot) {
            $branchId     = $this->resolveBranchId();
            $depotOptions = Depot::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }

        $containerRows        = $this->getContainerReadinessData();
        $operationalReadiness = $this->getOperationalReadinessData($rows, $containerRows);

        return [
            'rows'               => $rows,
            'total_briefing'     => $totalBriefing,
            'total_unit_masuk'   => $totalUnitMasuk,
            'total_need'         => $totalNeed,
            'total_attend'       => $totalAttend,
            'total_gap'          => $totalGap,
            'ok_count'           => $okCount,
            'ng_count'           => $ngCount,
            'readiness_ok'       => $readinessOk,
            'readiness_ng'       => $readinessNg,
            'matrix'             => $matrix,
            'days_in_month'      => $daysInMonth,
            'month_options'      => $monthOptions,
            'year_options'       => $yearOptions,
            'has_multi_depot'    => $hasMultiDepot,
            'depot_options'      => $depotOptions,
            'month_label'        => Carbon::createFromDate($this->filterYear, $this->filterMonth, 1)
                                         ->translatedFormat('F Y'),
            'selected_session_id'=> $this->selectedSessionId,
            'detail'             => $this->getSelectedSessionDetail(),

            // ── Container Readiness ───────────────────────────────────────────
            'container_rows'          => $containerRows,
            'operational_readiness'   => $operationalReadiness,
            'container_resource_url'  => ContainerReadinessSessionResource::getUrl('create'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ringkasan bulanan — semua bulan tahun berjalan (s/d bulan sekarang)
    |--------------------------------------------------------------------------
    */

    private const MONTH_NAMES_ID = [
        1 => 'Januari',   2 => 'Februari',  3 => 'Maret',    4 => 'April',
        5 => 'Mei',       6 => 'Juni',       7 => 'Juli',     8 => 'Agustus',
        9 => 'September', 10 => 'Oktober',  11 => 'November', 12 => 'Desember',
    ];

    protected function getYearlySummary(): array
    {
        $year         = (int) now()->format('Y');
        $currentMonth = (int) now()->format('m');
        $depotId      = $this->filterDepotId;
        $branchId     = $this->resolveBranchId();

        // Agregat per bulan di level sesi — JANGAN JOIN ke attendance
        $sessionData = DB::table('briefing_sessions')
            ->selectRaw('
                EXTRACT(MONTH FROM date)::int                                     AS month_num,
                COUNT(*)::int                                                     AS session_count,
                COALESCE(SUM(unit_masuk_yard), 0)::int                           AS total_units,
                SUM(CASE WHEN summary_sufficient = true THEN 1 ELSE 0 END)::int  AS ok_count
            ')
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

        $summary = [];

        for ($m = 1; $m <= $currentMonth; $m++) {
            $row      = $sessionData->get($m);
            $sessions = $row ? (int) $row->session_count : 0;
            $units    = $row ? (int) $row->total_units   : 0;
            $ok       = $row ? (int) $row->ok_count      : 0;
            $avg      = $sessions > 0 ? round($units / $sessions, 1) : 0;

            if ($m === $currentMonth) {
                $status = 'LIVE';
            } elseif ($sessions === 0) {
                $status = '-';
            } elseif ($ok < $sessions) {
                $status = 'WARNING';
            } else {
                $status = 'OK';
            }

            $summary[] = [
                'month_num'     => $m,
                'month_label'   => self::MONTH_NAMES_ID[$m],
                'session_count' => $sessions,
                'total_units'   => $units,
                'avg_units'     => $avg,
                'ok_count'      => $ok,
                'status'        => $status,
                'has_data'      => $sessions > 0,
            ];
        }

        return $summary;
    }

    /*
    |--------------------------------------------------------------------------
    | Cakupan historis — bulan mana saja yang sudah ada datanya
    |--------------------------------------------------------------------------
    */

    protected function getHistoricalCoverage(): array
    {
        $year         = (int) now()->format('Y');
        $currentMonth = (int) now()->format('m');
        $depotId      = $this->filterDepotId;
        $branchId     = $this->resolveBranchId();

        $monthsWithData = DB::table('briefing_sessions')
            ->selectRaw('EXTRACT(MONTH FROM date)::int AS month_num')
            ->whereYear('date', $year)
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->when(! $depotId && $branchId, fn ($q) => $q->whereIn(
                'depot_id',
                DB::table('depots')->where('branch_id', $branchId)->select('id')
            ))
            ->groupByRaw('EXTRACT(MONTH FROM date)')
            ->pluck('month_num')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $months       = [];
        $coveredCount = 0;

        for ($m = 1; $m <= $currentMonth; $m++) {
            $has = in_array($m, $monthsWithData, true);
            if ($has) {
                $coveredCount++;
            }

            $months[] = [
                'month_num'  => $m,
                'label'      => self::MONTH_NAMES_ID[$m],
                'has_data'   => $has,
                'is_current' => ($m === $currentMonth),
            ];
        }

        $percent = $currentMonth > 0 ? (int) round(($coveredCount / $currentMonth) * 100) : 0;

        return [
            'months'   => $months,
            'covered'  => $coveredCount,
            'expected' => $currentMonth,
            'percent'  => $percent,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Container Readiness — data per bulan dari tabel container_readiness_sessions
    |--------------------------------------------------------------------------
    */

    protected function getContainerReadinessData(): \Illuminate\Support\Collection
    {
        $start = Carbon::createFromDate($this->filterYear, $this->filterMonth, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // Tidak ada depot_id — satu baris per hari (demand planning global)
        return ContainerReadinessSession::query()
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('session_date')
            ->get()
            ->map(function ($row) {
                return [
                    'id'                  => $row->id,
                    'date'                => $row->session_date,
                    'date_str'            => $row->session_date->toDateString(),
                    'date_label'          => $row->session_date->translatedFormat('d M Y'),
                    'unit_count'          => $row->unit_count,
                    'container_need'      => $row->container_need,
                    'container_available' => $row->container_available,
                    'gap'                 => $row->gap,
                    'gap_label'           => $row->gap >= 0 ? "+{$row->gap}" : (string) $row->gap,
                    'is_ready'            => $row->summary_sufficient,
                    'status'              => $row->summary_sufficient ? 'READY' : 'NOT READY',
                    'notes'               => $row->notes,
                    'edit_url'            => ContainerReadinessSessionResource::getUrl('edit', ['record' => $row->id]),
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Operational Readiness — gabungan MP + Container per tanggal
    |--------------------------------------------------------------------------
    */

    protected function getOperationalReadinessData(
        \Illuminate\Support\Collection $mpRows,
        \Illuminate\Support\Collection $containerRows
    ): array {
        // Index container rows by date string
        $containerByDate = $containerRows->keyBy('date_str');

        // Ambil semua tanggal yang muncul di MP atau Container
        $allDates = $mpRows->pluck('date')
            ->map(fn ($d) => $d instanceof \Illuminate\Support\Carbon ? $d->toDateString() : (string) $d)
            ->merge($containerRows->pluck('date_str'))
            ->unique()
            ->sort()
            ->values();

        $mpByDate = $mpRows->keyBy(fn ($r) => (
            $r['date'] instanceof \Illuminate\Support\Carbon
                ? $r['date']->toDateString()
                : (string) $r['date']
        ));

        $result = [];

        foreach ($allDates as $dateStr) {
            $mp        = $mpByDate->get($dateStr);
            $container = $containerByDate->get($dateStr);

            $mpOk        = $mp        ? ($mp['ok'] === null ? null : (bool) $mp['ok']) : null;
            $containerOk = $container ? (bool) $container['is_ready']                : null;

            // Overall READY hanya jika keduanya OK (atau salah satu OK dan yg lain tidak ada data)
            if ($mpOk === null && $containerOk === null) {
                $overall = null;
            } elseif ($mpOk === false || $containerOk === false) {
                $overall = false;
            } else {
                $overall = true; // keduanya true, atau satu true + satu null (belum ada data)
            }

            $result[] = [
                'date_str'        => $dateStr,
                'date_label'      => Carbon::parse($dateStr)->translatedFormat('d M Y'),
                'mp_ok'           => $mpOk,
                'mp_attend'       => $mp['mp_attend'] ?? null,
                'mp_need'         => $mp['mp_need']   ?? null,
                'container_ok'    => $containerOk,
                'container_need'  => $container['container_need']      ?? null,
                'container_avail' => $container['container_available'] ?? null,
                'overall_ok'      => $overall,
                'has_mp'          => $mp        !== null,
                'has_container'   => $container !== null,
            ];
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Detail drill-down — read-only per-session attendance
    |--------------------------------------------------------------------------
    */

    protected function getSelectedSessionDetail(): ?array
    {
        if (! $this->selectedSessionId) {
            return null;
        }

        $session = BriefingSession::with([
            'attendances' => fn ($q) => $q
                ->with('manpower:id,name')
                ->orderByRaw("CASE WHEN attendance_status = 'present' THEN 0 ELSE 1 END")
                ->orderBy('id'),
            'depot:id,name',
        ])
        ->withCount([
            'attendances as mp_attend' => fn ($q) => $q->where('attendance_status', 'present'),
        ])
        ->find($this->selectedSessionId);

        if (! $session) {
            return null;
        }

        // Priority 1: dedicated column (new data from Filament).
        // Priority 2: regex on notes (legacy AppSheet data — kept for transition).
        $unit = $session->unit_masuk_yard !== null
            ? (int) $session->unit_masuk_yard
            : (
                ($session->notes && preg_match('/Unit Masuk Yard\/PDC:\s*(\d+)/i', $session->notes, $m))
                    ? (int) $m[1]
                    : null
            );

        $evidenceUrl = $session->briefing_evidence_path
            ? Storage::disk('public')->url($session->briefing_evidence_path)
            : null;

        $need   = (int) ($session->summary_headcount ?? 0);
        $attend = (int) $session->mp_attend;
        $gap    = $attend - $need;
        $ok     = $need > 0 ? ($attend >= $need) : null;

        $attendanceRows = $session->attendances->map(function ($att) {
            $statusValue = $att->attendance_status instanceof \App\Enums\AttendanceStatus
                ? $att->attendance_status->value
                : (string) $att->attendance_status;

            $statusLabel = $att->attendance_status instanceof \App\Enums\AttendanceStatus
                ? $att->attendance_status->label()
                : $statusValue;

            $finalStatus = $att->final_mp_status;

            $finalStatusColor = match ($finalStatus) {
                'Siap Kerja'               => 'emerald',
                'Tidak Fit'                => 'rose',
                'APD Tidak Lengkap'        => 'amber',
                'Istirahat 30 Menit'       => 'amber',
                'Perlu Pemeriksaan Ulang'  => 'amber',
                'Tidak Hadir'              => 'gray',
                default                    => 'gray',
            };

            return [
                'name'              => $att->display_name,
                'mp_type'           => $att->mp_type,
                'is_backup'         => $att->is_backup,
                'status_value'      => $statusValue,
                'status_label'      => $statusLabel,
                'temperature'       => $att->temperature
                    ? number_format((float) $att->temperature, 1) . '°C'
                    : null,
                'bp'                => ($att->bp_systolic && $att->bp_diastolic)
                    ? "{$att->bp_systolic}/{$att->bp_diastolic}"
                    : null,
                'fit_status'        => $att->fit_status,
                'recheck_result'    => $att->recheck_result,
                'medical_action'    => $att->medical_action,
                'has_ppe'           => $att->has_ppe,
                'final_status'      => $finalStatus,
                'final_color'       => $finalStatusColor,
                'remark'            => $att->remark,
            ];
        });

        return [
            'session_id'  => $session->id,
            'date_label'  => $session->date->translatedFormat('d M Y'),
            'depot'       => $session->depot?->name ?? '-',
            'unit_masuk'  => $unit,
            'evidence_url'=> $evidenceUrl,
            'mp_need'    => $need,
            'mp_attend'  => $attend,
            'gap'        => $gap,
            'gap_label'  => $gap >= 0 ? "+{$gap}" : (string) $gap,
            'status'     => $ok === null ? '-' : ($ok ? 'OK' : 'NG'),
            'ok'         => $ok,
            'attendances'=> $attendanceRows,
        ];
    }
}

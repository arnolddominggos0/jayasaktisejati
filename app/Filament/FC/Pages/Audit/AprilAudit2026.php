<?php

namespace App\Filament\FC\Pages\Audit;

use App\Enums\AttendanceStatus;
use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\LoadingSession;
use App\Models\Manpower;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class AprilAudit2026 extends Page
{
    protected static ?string $slug = 'audit-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Laporan & Notifikasi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.fc.pages.audit-april';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Audit';
    }

    public $stats = [];

    public $manpowerList = [];

    public $briefings = [];

    public $healthSummary = [];

    public $apdSummary = [];

    public $loadingSummary = [];

    public $depotName = '';

    public $selectedDepotId = null;

    public $selectedDate = '';

    public function mount(): void
    {
        $user = Filament::auth()->user();
        $this->selectedDate = now()->toDateString();

        $depotId = app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : ($user?->scope_unit_type === 'depot' ? $user->scope_unit_id : Depot::where('coordinator_user_id', $user?->id)->value('id'));

        $this->selectedDepotId = $depotId;
        if ($depotId) {
            $this->depotName = Depot::find($depotId)?->name ?? 'Depo Tanjung Priok';
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loadStats();
        $this->loadManpowerAttendance();
        $this->loadBriefings();
        $this->loadHealthSummary();
        $this->loadApdSummary();
        $this->loadLoadingSummary();
    }

    private function getBaseQuery()
    {
        $query = BriefingSession::query();

        if ($this->selectedDepotId) {
            $query->where('depot_id', $this->selectedDepotId);
        }

        if ($this->selectedDate) {
            $startOfMonth = Carbon::parse($this->selectedDate)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($this->selectedDate)->endOfMonth()->toDateString();
            $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
        }

        return $query;
    }

    private function getManpowerQuery()
    {
        $query = Manpower::where('active', true);

        if ($this->selectedDepotId) {
            $query->where('depot_id', $this->selectedDepotId);
        }

        return $query;
    }

    private function loadStats(): void
    {
        $sessions = $this->getBaseQuery()->get();
        $sessionIds = $sessions->pluck('id');

        $mpCount = $this->getManpowerQuery()->count();
        $briefingCount = $sessions->count();

        $attendancePresent = BriefingAttendance::whereIn('session_id', $sessionIds)
            ->where('attendance_status', AttendanceStatus::Present->value)
            ->count();

        $ppeChecked = BriefingAttendancePpeItem::whereHas('attendance', fn ($q) => $q->whereIn('session_id', $sessionIds))
            ->count();
        $ppeOk = BriefingAttendancePpeItem::where('condition', 'baik')
            ->whereHas('attendance', fn ($q) => $q->whereIn('session_id', $sessionIds))
            ->count();

        $loadingQuery = LoadingSession::query();
        if ($this->selectedDepotId) {
            $loadingQuery->where('depot_id', $this->selectedDepotId);
        }
        if ($this->selectedDate) {
            $startOfMonth = Carbon::parse($this->selectedDate)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($this->selectedDate)->endOfMonth()->toDateString();
            $loadingQuery->whereBetween('created_at', [$startOfMonth, $endOfMonth.' 23:59:59']);
        }
        $loadingTotal = $loadingQuery->count();
        $loadingCompleted = (clone $loadingQuery)->where('status', 'completed')->count();

        $healthStats = DB::select('
            SELECT AVG(temperature) as avg_temp, MIN(temperature) as min_temp, MAX(temperature) as max_temp,
                   AVG(bp_systolic) as avg_sys, AVG(bp_diastolic) as avg_dia
            FROM briefing_attendances
            WHERE attendance_status = ? AND session_id IN (?)
        ', [AttendanceStatus::Present->value, $sessionIds->implode(',')]);

        $this->stats = [
            'total_mp' => $mpCount,
            'briefing_sessions' => $briefingCount,
            'attendance_present' => $attendancePresent,
            'ppe_checked' => $ppeChecked,
            'ppe_ok' => $ppeOk,
            'loading_completed' => $loadingCompleted,
            'loading_total' => $loadingTotal,
            'avg_temperature' => round($healthStats[0]->avg_temp ?? 36.5, 1),
            'min_temperature' => round($healthStats[0]->min_temp ?? 36.0, 1),
            'max_temperature' => round($healthStats[0]->max_temp ?? 37.5, 1),
            'avg_sys' => round($healthStats[0]->avg_sys ?? 120),
            'avg_dia' => round($healthStats[0]->avg_dia ?? 80),
        ];
    }

    private function loadManpowerAttendance(): void
    {
        $manpower = $this->getManpowerQuery()->orderBy('name')->get();
        $sessions = $this->getBaseQuery()->pluck('id');
        $totalDays = $sessions->count();

        $this->manpowerList = [];
        foreach ($manpower as $mp) {
            $present = BriefingAttendance::where('manpower_id', $mp->id)
                ->whereIn('session_id', $sessions)
                ->where('attendance_status', AttendanceStatus::Present->value)
                ->count();

            $this->manpowerList[] = [
                'name' => $mp->name,
                'present' => $present,
                'total_days' => $totalDays,
                'percentage' => $totalDays > 0 ? round(($present / $totalDays) * 100) : 0,
                'status' => $totalDays > 0 ? ($present >= $totalDays - 2 ? 'Aktif' : ($present >= $totalDays - 4 ? 'Cukup' : 'Kurang')) : '-',
            ];
        }
    }

    private function loadBriefings(): void
    {
        $sessions = $this->getBaseQuery()->with('coordinator')->orderBy('date')->get();

        $this->briefings = [];
        foreach ($sessions as $session) {
            $attendees = $session->attendances()
                ->where('attendance_status', AttendanceStatus::Present->value)
                ->count();

            $this->briefings[] = [
                'date' => $session->date,
                'notes' => $session->notes,
                'attendees' => $attendees,
                'coordinator' => $session->coordinator?->name ?? '-',
                'mp_check_status' => $session->mp_check_status?->value ?? 'draft',
            ];
        }
    }

    private function loadHealthSummary(): void
    {
        $sessions = $this->getBaseQuery()->pluck('id');

        $attendances = BriefingAttendance::where('attendance_status', AttendanceStatus::Present->value)
            ->whereIn('session_id', $sessions)
            ->get();

        $temps = $attendances->pluck('temperature')->filter()->toArray();
        $sys = $attendances->pluck('bp_systolic')->filter()->toArray();
        $dia = $attendances->pluck('bp_diastolic')->filter()->toArray();

        $this->healthSummary = [
            'temp_min' => count($temps) ? round(min($temps), 1) : '-',
            'temp_max' => count($temps) ? round(max($temps), 1) : '-',
            'temp_avg' => count($temps) ? round(array_sum($temps) / count($temps), 1) : '-',
            'sys_avg' => count($sys) ? round(array_sum($sys) / count($sys)) : '-',
            'dia_avg' => count($dia) ? round(array_sum($dia) / count($dia)) : '-',
            'total_checks' => $attendances->count(),
        ];
    }

    private function loadApdSummary(): void
    {
        $sessions = $this->getBaseQuery()->pluck('id');
        $types = ['helm', 'rompi', 'sepatu', 'sarung_tangan'];

        $this->apdSummary = [];
        foreach ($types as $type) {
            $total = BriefingAttendancePpeItem::where('ppe_type', $type)
                ->whereHas('attendance', fn ($q) => $q->whereIn('session_id', $sessions))
                ->count();

            $ok = BriefingAttendancePpeItem::where('ppe_type', $type)
                ->where('condition', 'baik')
                ->whereHas('attendance', fn ($q) => $q->whereIn('session_id', $sessions))
                ->count();

            $this->apdSummary[$type] = [
                'total' => $total,
                'ok' => $ok,
                'percentage' => $total > 0 ? round(($ok / $total) * 100) : 0,
            ];
        }
    }

    private function loadLoadingSummary(): void
    {
        $query = LoadingSession::query();
        if ($this->selectedDepotId) {
            $query->where('depot_id', $this->selectedDepotId);
        }
        if ($this->selectedDate) {
            $startOfMonth = Carbon::parse($this->selectedDate)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($this->selectedDate)->endOfMonth()->toDateString();
            $query->whereBetween('created_at', [$startOfMonth, $endOfMonth.' 23:59:59']);
        }

        $sessions = $query->get();
        $completed = $sessions->where('status.value', 'completed')->count();
        $go = $sessions->where('final_decision_status.value', 'go')->count();
        $stop = $sessions->where('final_decision_status.value', 'stop')->count();
        $progress = $sessions->whereNull('final_decision_status')->count();

        $this->loadingSummary = [
            'total' => $sessions->count(),
            'completed' => $completed,
            'go' => $go,
            'stop' => $stop,
            'progress' => $progress,
            'percentage' => $sessions->count() > 0 ? round(($completed / $sessions->count()) * 100) : 0,
        ];
    }
}

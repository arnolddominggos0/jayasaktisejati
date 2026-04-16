<?php

namespace App\Filament\FC\Pages\Audit;

use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\BriefingSession;
use App\Models\LoadingSession;
use App\Models\Manpower;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class AprilAudit2026 extends Page
{
    protected static ?string $slug = 'audit-april-2026';

    protected static ?string $navigationIcon = 'heroicon-m-document-chart-bar';

    protected static ?string $navigationLabel = 'Audit April 2026';

    protected static ?string $navigationGroup = 'Laporan Audit';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.fc.pages.audit-april';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Audit April 2026';
    }

    public $stats = [];

    public $briefings = [];

    public $recentLoading = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadBriefings();
        $this->loadRecentLoading();
    }

    private function loadStats(): void
    {
        $user = Filament::auth()->user();

        $this->stats = [
            'total_mp' => Manpower::count(),
            'briefing_sessions' => BriefingSession::count(),
            'total_attendance' => BriefingAttendance::count(),
            'attendance_present' => BriefingAttendance::where('attendance_status', 'present')->count(),
            'ppe_checked' => BriefingAttendancePpeItem::count(),
            'ppe_ok' => BriefingAttendancePpeItem::where('condition', 'baik')->count(),
            'loading_sessions' => LoadingSession::count(),
            'loading_completed' => LoadingSession::where('status', 'completed')->count(),
        ];

        // Health stats
        $healthStats = DB::select("
            SELECT
                AVG(temperature) as avg_temp,
                MIN(temperature) as min_temp,
                MAX(temperature) as max_temp,
                AVG(bp_systolic) as avg_sys,
                AVG(bp_diastolic) as avg_dia
            FROM briefing_attendances
            WHERE attendance_status = 'present'
        ");

        if ($healthStats) {
            $this->stats['avg_temperature'] = round($healthStats[0]->avg_temp ?? 36.5, 1);
            $this->stats['avg_bp'] = round($healthStats[0]->avg_sys ?? 120).'/'.round($healthStats[0]->avg_dia ?? 80);
        } else {
            $this->stats['avg_temperature'] = 36.5;
            $this->stats['avg_bp'] = '120/80';
        }
    }

    private function loadBriefings(): void
    {
        $user = Filament::auth()->user();

        $this->briefings = BriefingSession::with(['attendances', 'depot'])
            ->orderBy('date', 'desc')
            ->limit(11)
            ->get()
            ->map(function ($session) {
                $present = $session->attendances->where('attendance_status', 'present')->count();
                $sick = $session->attendances->where('attendance_status', 'sick')->count();
                $absent = $session->attendances->where('attendance_status', 'absent')->count();
                $total = $session->attendances->count();

                return [
                    'id' => $session->id,
                    'date' => $session->date,
                    'depot' => $session->depot?->name ?? '-',
                    'total_mp' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'sick' => $sick,
                    'mp_check_status' => $session->mp_check_status?->value ?? 'pending',
                ];
            })
            ->toArray();
    }

    private function loadRecentLoading(): void
    {
        $this->recentLoading = LoadingSession::with(['shipment', 'depot', 'briefingSession'])
            ->orderBy('code', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($session) {
                $date = $session->briefingSession?->date ?? $session->created_at->format('Y-m-d');

                return [
                    'code' => $session->code,
                    'date' => $date,
                    'depot' => $session->depot?->name ?? '-',
                    'status' => $session->status?->value ?? 'draft',
                    'mp_required' => $session->mp_required,
                    'mp_present' => $session->mp_present,
                    'mp_attendance' => $session->mp_attendance_completed ? '✅' : '❌',
                    'health_check' => $session->health_check_completed ? '✅' : '❌',
                    'apd_check' => $session->apd_check_completed ? '✅' : '❌',
                    'rack_check' => $session->rack_container_check_completed ? '✅' : '❌',
                    'equipment_check' => $session->equipment_check_completed ? '✅' : '❌',
                    'unit_check' => $session->unit_check_completed ? '✅' : '❌',
                    'final_decision' => $session->final_decision_status?->value ?? '-',
                ];
            })
            ->toArray();
    }
}

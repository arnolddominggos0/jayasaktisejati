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

    public $manpowerList = [];

    public $dailyTotal = [];

    public $healthStats = [];

    public $apdStats = [];

    public $loadingSessions = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadManpowerAttendance();
        $this->loadDailyHealth();
        $this->loadDailyAPD();
        $this->loadLoadingSessions();
    }

    private function loadStats(): void
    {
        $this->stats = [
            'total_mp' => Manpower::count(),
            'briefing_sessions' => BriefingSession::count(),
            'attendance_present' => BriefingAttendance::where('attendance_status', 'present')->count(),
            'ppe_checked' => BriefingAttendancePpeItem::count(),
            'ppe_ok' => BriefingAttendancePpeItem::where('condition', 'baik')->count(),
            'loading_sessions' => LoadingSession::count(),
            'loading_completed' => LoadingSession::where('status', 'completed')->count(),
        ];

        $healthStats = DB::select("
            SELECT AVG(temperature) as avg_temp
            FROM briefing_attendances
            WHERE attendance_status = 'present'
        ");

        $this->stats['avg_temperature'] = round($healthStats[0]->avg_temp ?? 36.5, 1);
    }

    private function loadManpowerAttendance(): void
    {
        $dates = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
        $manpower = Manpower::orderBy('name')->get();

        $this->manpowerList = [];
        foreach ($manpower as $mp) {
            $attendance = [];
            $totalPresent = 0;
            $totalDays = 16;

            foreach ($dates as $day) {
                $dateStr = '2026-04-'.$day;
                $present = BriefingAttendance::where('manpower_id', $mp->id)
                    ->whereHas('session', function ($q) use ($dateStr) {
                        $q->where('date', $dateStr);
                    })
                    ->first();

                if ($present) {
                    if ($present->attendance_status === 'present') {
                        $attendance[$day] = 'hadir';
                        $totalPresent++;
                    } elseif ($present->attendance_status === 'sick') {
                        $attendance[$day] = 'sakit';
                    } elseif ($present->attendance_status === 'absent') {
                        $attendance[$day] = 'alpha';
                    } else {
                        $attendance[$day] = 'izin';
                    }
                } else {
                    $attendance[$day] = 'alpha';
                }
            }

            $this->manpowerList[] = [
                'name' => $mp->name,
                'attendance' => $attendance,
                'total_present' => $totalPresent,
                'total_days' => $totalDays,
            ];
        }

        // Daily totals
        $this->dailyTotal = [];
        foreach ($dates as $day) {
            $dateStr = '2026-04-'.$day;
            $hadir = BriefingAttendance::whereHas('session', function ($q) use ($dateStr) {
                $q->where('date', $dateStr);
            })
                ->where('attendance_status', 'present')
                ->count();

            $this->dailyTotal[$day] = ['hadir' => $hadir];
        }
    }

    private function loadDailyHealth(): void
    {
        $briefings = BriefingSession::orderBy('date')->get();

        $this->healthStats = [];
        foreach ($briefings as $briefing) {
            $day = substr($briefing->date, 8, 2);
            $present = $briefing->attendances->where('attendance_status', 'present');

            $temps = $present->pluck('temperature')->filter()->toArray();
            $sys = $present->pluck('bp_systolic')->filter()->toArray();
            $dia = $present->pluck('bp_diastolic')->filter()->toArray();

            $this->healthStats[] = [
                'date' => $briefing->date,
                'total_present' => $present->count(),
                'avg_temp' => count($temps) ? round(array_sum($temps) / count($temps), 1) : '-',
                'avg_sys' => count($sys) ? round(array_sum($sys) / count($sys)) : '-',
                'avg_dia' => count($dia) ? round(array_sum($dia) / count($dia)) : '-',
            ];
        }
    }

    private function loadDailyAPD(): void
    {
        $briefings = BriefingSession::orderBy('date')->get();

        $this->apdStats = [];
        foreach ($briefings as $briefing) {
            $day = substr($briefing->date, 8, 2);
            $present = $briefing->attendances->where('attendance_status', 'present');

            $totalChecked = $present->count();
            $helmOk = BriefingAttendancePpeItem::whereIn('attendance_id', $present->pluck('id'))
                ->where('ppe_type', 'helm')
                ->where('condition', 'baik')
                ->count();
            $rompiOk = BriefingAttendancePpeItem::whereIn('attendance_id', $present->pluck('id'))
                ->where('ppe_type', 'rompi')
                ->where('condition', 'baik')
                ->count();
            $sepatuOk = BriefingAttendancePpeItem::whereIn('attendance_id', $present->pluck('id'))
                ->where('ppe_type', 'sepatu')
                ->where('condition', 'baik')
                ->count();
            $sarungTanganOk = BriefingAttendancePpeItem::whereIn('attendance_id', $present->pluck('id'))
                ->where('ppe_type', 'sarung_tangan')
                ->where('condition', 'baik')
                ->count();

            $this->apdStats[] = [
                'date' => $briefing->date,
                'total_checked' => $totalChecked,
                'helm_ok' => $helmOk,
                'rompi_ok' => $rompiOk,
                'sepatu_ok' => $sepatuOk,
                'sarung_tangan_ok' => $sarungTanganOk,
            ];
        }
    }

    private function loadLoadingSessions(): void
    {
        $sessions = LoadingSession::with(['briefingSession'])
            ->orderBy('code', 'desc')
            ->limit(20)
            ->get();

        $this->loadingSessions = [];
        foreach ($sessions as $session) {
            $date = $session->briefingSession?->date ?? substr($session->created_at->format('Y-m-d'), 8, 2);

            $this->loadingSessions[] = [
                'code' => $session->code,
                'date' => $date,
                'mp' => $session->mp_present.'/'.$session->mp_required,
                'attendance' => $session->mp_attendance_completed ? '✅' : '❌',
                'health' => $session->health_check_completed ? '✅' : '❌',
                'apd' => $session->apd_check_completed ? '✅' : '❌',
                'rack' => $session->rack_container_check_completed ? '✅' : '❌',
                'equipment' => $session->equipment_check_completed ? '✅' : '❌',
                'unit' => $session->unit_check_completed ? '✅' : '❌',
                'decision' => $session->final_decision_status?->value === 'go' ? 'GO' : ($session->final_decision_status?->value === 'stop' ? 'STOP' : 'PROGRESS'),
            ];
        }
    }
}

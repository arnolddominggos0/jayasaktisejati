<?php

namespace App\Filament\FC\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\BriefingAttendance;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class FcTodayManpower extends Widget
{
    protected static string $view = 'filament.fc.widgets.today-manpower';
    protected static ?string $heading = 'Daftar MP Hari Ini';
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $today = Carbon::today();
        $depotId = $this->getDepotId();

        $countsQuery = BriefingAttendance::query()
            ->with(['manpower', 'session'])
            ->whereHas('session', fn ($q) => $q->whereDate('date', $today));

        if ($depotId) {
            $countsQuery->whereHas('session', fn ($q) => $q->where('depot_id', $depotId));
        }

        $allAttendances = $countsQuery->get();

        $totalPresent = $allAttendances->where('attendance_status', 'present')->count();
        $totalFit = $allAttendances->filter(fn ($a) => $a->final_mp_status === 'Siap Kerja')->count();
        $totalUnfit = $allAttendances->filter(fn ($a) =>in_array($a->final_mp_status, ['Tidak Fit', 'Perlu Pemeriksaan Ulang'], true))->count();
        $totalSick = $allAttendances->where('attendance_status', 'sick')->count();
        $totalAbsent = $allAttendances->where('attendance_status', 'absent')->count();

        $attendances = (clone $countsQuery)->latest('created_at')->limit(20)->get();

        $items = $attendances
            ->sortByDesc(fn ($a) => match (true) {
                $a->attendance_status === 'present' && $a->fit_status === 'FIT' => 0,
                $a->attendance_status === 'present' && $a->recheck_required => 1,
                $a->attendance_status === 'present' => 2,
                $a->attendance_status === 'sick' => 3,
                default => 4,
            })
            ->map(function (BriefingAttendance $r) {
                $isBackup = $r->is_backup;

                $domain = $r->manpower?->domain;
                $role = is_object($domain) && method_exists($domain, 'label')
                    ? $domain->label()
                    : (string) ($domain ?? '—');

                $status = $r->attendance_status instanceof AttendanceStatus
                    ? $r->attendance_status->label()
                    : (string) $r->attendance_status;

                $fitStatus = $r->fit_status;
                $isFit = $fitStatus === 'FIT';
                $needsRecheck = $r->recheck_required ?? false;

                $priority = match (true) {
                    $r->attendance_status === 'present' && $isFit => 'fit',
                    $r->attendance_status === 'present' && $needsRecheck => 'recheck',
                    $r->attendance_status === 'present' && !$isFit => 'unfit',
                    $r->attendance_status === 'sick' => 'sick',
                    default => 'absent',
                };

                return [
                    'name'       => $r->display_name,
                    'role'       => $isBackup ? 'Backup MP' : ($role ?: '—'),
                    'status'     => $status,
                    'fit'        => $isFit,
                    'fit_status' => $fitStatus ?: null,
                    'recheck'    => $needsRecheck,
                    'priority'   => $priority,
                    'is_backup'  => $isBackup,
                    'time'       => optional($r->created_at)->format('H:i'),
                ];
            })
            ->values()
            ->toArray();

        return [
            'items' => $items,
            'totalPresent' => $totalPresent,
            'totalFit' => $totalFit,
            'totalUnfit' => $totalUnfit,
            'totalSick' => $totalSick,
            'totalAbsent' => $totalAbsent,
        ];
    }

    protected function getDepotId(): ?int
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return null;
        }

        return app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : ($user->scope_unit_type === 'depot' ? $user->scope_unit_id : null);
    }
}

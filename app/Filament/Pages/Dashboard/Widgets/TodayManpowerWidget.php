<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\BriefingAttendance;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class TodayManpowerWidget extends Widget
{
    protected static string $view = 'filament.widgets.today-manpower-widget';
    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = ['xl' => 1];

    public function getViewData(): array
    {
        $today = Carbon::today();

        $items = BriefingAttendance::query()
            ->with(['manpower', 'session'])
            ->whereHas('session', fn ($query) => $query->whereDate('date', $today))
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(function (BriefingAttendance $r) {
                $isBackup = $r->is_backup;

                $domain = $r->manpower?->domain;
                $role = is_object($domain) && method_exists($domain, 'label')
                    ? $domain->label()
                    : (string) ($domain ?? '—');

                $status = $r->attendance_status instanceof AttendanceStatus
                    ? $r->attendance_status->label()
                    : (string) $r->attendance_status;

                return [
                    'name'      => $r->display_name,
                    'role'      => $isBackup ? 'Backup MP' : ($role ?: '—'),
                    'status'    => $status,
                    'is_backup' => $isBackup,
                    'time'      => optional($r->created_at)->format('H:i'),
                ];
            })
            ->toArray();

        return compact('items');
    }
}

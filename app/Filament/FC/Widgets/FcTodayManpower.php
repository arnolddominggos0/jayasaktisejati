<?php

namespace App\Filament\FC\Widgets;

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
        $totalUnfit = $allAttendances->filter(fn ($a) => in_array($a->final_mp_status, ['Perlu Pemeriksaan Ulang', 'Tidak Fit'], true))->count();
        $totalPending = $allAttendances->filter(fn ($a) => in_array($a->final_mp_status, ['Belum Dinilai', 'APD Tidak Lengkap', 'Istirahat 30 Menit'], true))->count();
        $totalAbsent = $allAttendances->filter(fn ($a) => $a->final_mp_status === 'Tidak Hadir')->count();

        $attendances = (clone $countsQuery)->latest('created_at')->limit(20)->get();

        $items = $attendances
            ->sortBy(fn ($a) => match ($a->final_mp_status) {
                'Perlu Pemeriksaan Ulang' => 0,
                'Tidak Fit'               => 1,
                'APD Tidak Lengkap'       => 2,
                'Istirahat 30 Menit'      => 3,
                'Belum Dinilai'           => 4,
                'Siap Kerja'              => 5,
                'Tidak Hadir'             => 6,
                default                   => 7,
            })
            ->map(function (BriefingAttendance $r) {
                $isBackup = $r->is_backup;

                $domain = $r->manpower?->domain;
                $role = is_object($domain) && method_exists($domain, 'label')
                    ? $domain->label()
                    : (string) ($domain ?? '—');

                return [
                    'name'            => $r->display_name,
                    'role'            => $isBackup ? 'Backup MP' : ($role ?: '—'),
                    'final_mp_status' => $r->final_mp_status,
                    'is_backup'       => $isBackup,
                    'time'            => optional($r->created_at)->format('H:i'),
                ];
            })
            ->values()
            ->toArray();

        return [
            'items' => $items,
            'totalPresent' => $totalPresent,
            'totalFit' => $totalFit,
            'totalUnfit' => $totalUnfit,
            'totalPending' => $totalPending,
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

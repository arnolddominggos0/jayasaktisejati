<?php

namespace App\Filament\Pages\Dashboard\Widgets;

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

        $items = \App\Models\ManpowerAttendance::query()
            ->with(['manpower', 'session'])
            ->whereHas('session', fn($q) => $q->whereDate('date', $today))
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(function ($r) {
                return [
                    'name'   => $r->manpower?->name ?? '—',
                    'role'   => $r->manpower?->domain ?? '—',
                    'status' => $r->attendance_status,
                    'time'   => optional($r->created_at)->format('H:i'),
                ];
            })
            ->toArray();

        return compact('items');
    }
}

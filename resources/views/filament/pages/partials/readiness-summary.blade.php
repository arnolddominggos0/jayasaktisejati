@php
    /**
     * Simplified readiness summary for monitoring cards.
     * Shows only D-2 / D-1 / H-1 status as compact badges.
     */

    $cpMap = collect($voyage->checkpoints ?? [])
        ->keyBy(fn($cp) => strtolower($cp->code));

    $d2 = $cpMap->get('eta_d2');
    $d1 = $cpMap->get('eta_d1');

    // H-1 vessel check: find the check closest to ETD (within 48h window)
    $h1 = collect($voyage->vesselChecks ?? [])
        ->sortByDesc('check_date')
        ->first(fn($vc) => $vc->day_code && str_starts_with(strtolower($vc->day_code), 'h'));

    $badge = function ($label, $status, $color) {
        return sprintf(
            '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium %s" title="%s">%s</span>',
            $color,
            $status,
            $label
        );
    };

    $checkpointState = function ($cp) use ($badge) {
        if (!$cp) {
            return $badge('—', 'Belum dijadwalkan', 'bg-gray-100 text-gray-400');
        }

        if ($cp->is_completed) {
            return $badge(strtoupper($cp->code) . ' OK', 'Selesai', 'bg-green-100 text-green-700');
        }

        if ($cp->is_late) {
            return $badge(strtoupper($cp->code) . ' OVERDUE', 'Terlambat', 'bg-red-100 text-red-700');
        }

        if ($cp->scheduled_at?->isPast()) {
            return $badge(strtoupper($cp->code) . ' OVERDUE', 'Terlambat', 'bg-red-100 text-red-700');
        }

        return $badge(strtoupper($cp->code) . ' PENDING', 'Menunggu', 'bg-orange-100 text-orange-700');
    };

    $h1State = function ($vc) use ($badge) {
        if (!$vc) {
            return $badge('H-1 —', 'Belum ada pemeriksaan', 'bg-gray-100 text-gray-400');
        }

        $code = strtoupper($vc->day_code ?? 'H-1');

        return match ($vc->status?->value) {
            'on_schedule'     => $badge($code . ' OK', 'Sesuai Jadwal', 'bg-green-100 text-green-700'),
            'potential_delay' => $badge($code . ' RISK', 'Potensi Delay', 'bg-red-100 text-red-700'),
            default           => $badge($code . ' —', 'Belum dicek', 'bg-gray-100 text-gray-400'),
        };
    };
@endphp

@if ($d2 || $d1 || $h1)
    <div class="flex items-center gap-2">
        {!! $checkpointState($d2) !!}
        {!! $checkpointState($d1) !!}
        {!! $h1State($h1) !!}
    </div>
@else
    <div class="text-[11px] text-gray-400 italic">
        Belum ada readiness check.
    </div>
@endif

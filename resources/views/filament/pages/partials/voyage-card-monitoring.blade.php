@php
    $overdue = $v->milestones->where('is_overdue', true)->count();
    $dueToday = $v->milestones->where('is_due_today', true)->count();

    $badges = [];

    if ($v->departure_delay_severity) {
        $badges[] = [
            'text' => 'Telat Berangkat ' . $v->departure_delay_minutes . ' menit',
            'color' => 'bg-amber-100 text-amber-700'
        ];
    }

    if ($v->eta_overdue) {
        $badges[] = [
            'text' => 'ETA Terlewati',
            'color' => 'bg-red-100 text-red-700'
        ];
    } elseif ($v->sailing_risk) {
        $badges[] = [
            'text' => 'ETA < 24 Jam',
            'color' => 'bg-orange-100 text-orange-700'
        ];
    }

    if ($overdue > 0) {
        $badges[] = [
            'text' => $overdue . ' laporan belum diinput',
            'color' => 'bg-yellow-100 text-yellow-700'
        ];
    } elseif ($dueToday > 0) {
        $badges[] = [
            'text' => $dueToday . ' jatuh tempo hari ini',
            'color' => 'bg-orange-100 text-orange-700'
        ];
    } else {
        $badges[] = [
            'text' => 'Monitoring Aman',
            'color' => 'bg-green-100 text-green-700'
        ];
    }

    $sailingDuration = null;

    if ($v->atd_at) {
        $minutes = $v->atd_at->diffInMinutes(now());

        if ($minutes < 60) {
            $sailingDuration = $minutes . ' menit';
        } elseif ($minutes < 1440) {
            $sailingDuration = floor($minutes / 60) . ' jam';
        } else {
            $sailingDuration = floor($minutes / 1440) . ' hari';
        }
    }
@endphp

<div class="bg-white border rounded-xl p-5 mb-5 shadow-sm">

    <div class="flex justify-between items-start">

        <div>
            <div class="font-semibold text-sm">
                {{ $v->vessel?->name }} — {{ $v->voyage_no }}
            </div>

            <div class="text-xs text-gray-500 mt-1">
                {{ $v->pol?->code }} → {{ $v->pod?->code }}
            </div>

            <div class="text-xs text-gray-600 mt-2 space-x-4">

                <span>
                    ATD: {{ optional($v->atd_at)->format('d M H:i') ?? '-' }}
                </span>

                <span>
                    ETA: {{ optional($v->eta)->format('d M H:i') ?? '-' }}
                </span>

                @if ($sailingDuration)
                    <span>
                        Berlayar {{ $sailingDuration }}
                    </span>
                @endif

            </div>

        </div>

        <div class="flex flex-col items-end gap-1">

            @foreach ($badges as $badge)
                <span class="px-2 py-1 text-[11px] rounded {{ $badge['color'] }}">
                    {{ $badge['text'] }}
                </span>
            @endforeach

        </div>

    </div>

    <div class="grid grid-cols-5 gap-2 mt-4 text-xs">

        @foreach ($v->milestones->sortBy('milestone_date') as $m)

            @php
                if ($m->actual_date) {
                    $color = 'bg-green-100 text-green-700 border border-green-200';
                    $icon = '✔';
                } elseif ($m->is_overdue) {
                    $color = 'bg-red-100 text-red-700 border border-red-200';
                    $icon = '✖';
                } elseif ($m->is_due_today) {
                    $color = 'bg-orange-100 text-orange-700 border border-orange-200';
                    $icon = '⏳';
                } else {
                    $color = 'bg-gray-100 text-gray-600 border border-gray-200';
                    $icon = '—';
                }
            @endphp

            <div
                wire:click="showMilestone({{ $m->id }})"
                class="rounded-md py-2 text-center font-semibold {{ $color }} cursor-pointer hover:scale-105 transition"
            >

                <div class="uppercase tracking-wide">
                    {{ strtoupper($m->code) }}
                </div>

                <div class="mt-1 text-base">
                    {{ $icon }}
                </div>

            </div>

        @endforeach

    </div>

</div>
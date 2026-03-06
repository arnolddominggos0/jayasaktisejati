@php
    $overdue = $v->milestones->where('is_overdue', true)->count();
    $dueToday = $v->milestones->where('is_due_today', true)->count();

    $sailingDuration = null;

    if ($v->atd_at) {
        $totalMinutes = $v->atd_at->diffInMinutes(now());

        if ($totalMinutes < 60) {
            $sailingDuration = $totalMinutes . ' menit';
        } elseif ($totalMinutes < 1440) {
            $hours = floor($totalMinutes / 60);
            $sailingDuration = $hours . ' jam';
        } else {
            $days = floor($totalMinutes / 1440);
            $sailingDuration = $days . ' hari';
        }
    }

    $etaOverdue = $v->eta_overdue;
    $etaRisk = $v->sailing_risk;

@endphp

<div class="bg-white border rounded-lg p-4 mb-5 shadow-sm hover:shadow-md transition">

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
                    ATB: {{ optional($v->atb_at)->format('d M H:i') ?? '-' }}
                </span>

                <span>
                    Closing: {{ optional($v->closing_at)->format('d M H:i') ?? '-' }}
                </span>

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


            <div class="text-xs text-gray-600 mt-2 space-x-4">

                @if ($v->cargo_plan)
                    <span>
                        Cargo Plan: {{ $v->cargo_plan }}
                    </span>
                @endif

                @if ($v->cargo_actual)
                    <span>
                        Cargo Actual: {{ $v->cargo_actual }}
                    </span>
                @endif

            </div>


            @if ($etaOverdue)
                <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-red-100 text-red-700 rounded">
                    ETA Terlewati
                </div>
            @elseif($etaRisk)
                <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-orange-100 text-orange-700 rounded">
                    ETA < 24 Jam </div>
            @endif


            @if ($v->delay_reason)
                <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-gray-100 text-gray-700 rounded">
                    Reason: {{ $v->delay_reason->label() }}
                </div>
            @endif

        </div>


        <div class="text-right text-xs space-y-1">

            @if ($overdue > 0)
                <div class="text-red-600 font-semibold">
                    {{ $overdue }} laporan belum diinput
                </div>
            @endif

            @if ($dueToday > 0)
                <div class="text-orange-600 font-semibold">
                    {{ $dueToday }} jatuh tempo hari ini
                </div>
            @endif

            @if ($overdue === 0 && $dueToday === 0)
                <div class="text-green-600 font-semibold">
                    Laporan aman
                </div>
            @endif

        </div>

    </div>


    <div class="grid grid-cols-5 gap-3 mt-4 text-xs">

        @foreach ($v->milestones->sortBy('milestone_date') as $m)
            @php
                if ($m->actual_date) {
                    $icon = '✔';
                    $color = 'bg-green-100 text-green-700 border border-green-200';
                } elseif ($m->is_overdue) {
                    $icon = '✖';
                    $color = 'bg-red-100 text-red-700 border border-red-200';
                } elseif ($m->is_due_today) {
                    $icon = '⏳';
                    $color = 'bg-orange-100 text-orange-700 border border-orange-200';
                } else {
                    $icon = '—';
                    $color = 'bg-gray-100 text-gray-600 border border-gray-200';
                }
            @endphp

            <button wire:click="showMilestone({{ $m->id }})"
                class="rounded-md py-2 text-center font-semibold {{ $color }} hover:scale-105 transition">

                <div class="uppercase tracking-wide">
                    {{ strtoupper($m->code) }}
                </div>

                <div class="mt-1 text-base">
                    {{ $icon }}
                </div>

            </button>
        @endforeach

    </div>

</div>

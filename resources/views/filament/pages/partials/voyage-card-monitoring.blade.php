@php
    $state = $v->operationalState;
    $border = \App\Supports\OperationalUi::severityBorder($state->severity);
@endphp


<div class="bg-white border {{ $border }} rounded-lg p-4 mb-5 shadow-sm hover:shadow-md transition">

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

                @if ($state->sailingDays)
                    <span>
                        Berlayar {{ $state->sailingDays }} hari
                    </span>
                @endif

            </div>


            @if ($state->sailingDays)
                <div class="text-xs text-blue-600 mt-1 font-semibold">
                    Day {{ $state->sailingDays }} / 12
                </div>
            @endif


            @if ($state->hasEtaOverdue)
                <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-red-100 text-red-700 rounded">
                    ETA Terlewati
                </div>
            @elseif($state->hasSailingRisk)
                <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-orange-100 text-orange-700 rounded">
                    ETA &lt; 1 Hari
                </div>
            @endif


            @if ($v->cargo_plan)
                <div class="text-xs text-gray-600 mt-2">
                    Cargo Plan: {{ number_format($v->cargo_plan) }}
                </div>
            @endif


            @if ($v->delay_reason)
                <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-gray-100 text-gray-700 rounded">
                    Reason: {{ $v->delay_reason->label() }}
                </div>
            @endif

        </div>


        <div class="text-right text-xs space-y-1">

            @if ($state->milestoneOverdueCount > 0)
                <div class="text-red-600 font-semibold">
                    {{ $state->milestoneOverdueCount }} laporan belum diinput
                </div>
            @endif

            @if ($state->milestoneDueTodayCount > 0)
                <div class="text-orange-600 font-semibold">
                    {{ $state->milestoneDueTodayCount }} jatuh tempo hari ini
                </div>
            @endif

            @if ($state->milestoneOverdueCount === 0 && $state->milestoneDueTodayCount === 0)
                <div class="text-green-600 font-semibold">
                    Laporan aman
                </div>
            @endif

        </div>

    </div>



    {{-- GRID MILESTONE --}}
    <div class="grid grid-cols-6 gap-3 mt-4 text-xs">

        @foreach ($v->milestones->sortBy(fn($m) => (int) str_replace('d', '', $m->code)) as $m)
            @php $chip = \App\Supports\OperationalUi::milestoneChip($m); @endphp

            <button wire:click="showMilestone({{ $m->id }})"
                class="rounded-md py-2 text-center font-semibold {{ $chip['class'] }} hover:scale-105 transition">

                <div class="uppercase tracking-wide">
                    {{ strtoupper($m->code) }}
                </div>

                <div class="mt-1 text-base">
                    {{ $chip['icon'] }}
                </div>

            </button>
        @endforeach

    </div>

    @include('components.voyage-readiness-timeline', ['voyage' => $v])

</div>

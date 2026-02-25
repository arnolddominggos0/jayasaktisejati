@php
    $milestones = collect($v->milestones);
@endphp

@if($milestones->isNotEmpty())
    <div class="mt-4 border-t pt-3 text-xs space-y-2">

        <div class="font-semibold text-gray-600 uppercase">
            Monitoring Transit (H+)
        </div>

        @foreach($milestones->sortBy('milestone_date') as $ms)
            <div class="bg-slate-50 rounded px-3 py-2 border">

                <div class="font-medium">
                    {{ strtoupper($ms->code) }}
                    — {{ optional($ms->milestone_date)->format('d M Y') }}
                </div>

                @if($ms->position)
                    <div class="text-gray-600">
                        Posisi: {{ $ms->position }}
                    </div>
                @endif

                @if($ms->speed_knots)
                    <div class="text-gray-600">
                        Kecepatan: {{ $ms->speed_knots }} Knots
                    </div>
                @endif

                @if($ms->note)
                    <div class="text-gray-700">
                        {{ $ms->note }}
                    </div>
                @endif

            </div>
        @endforeach

    </div>
@endif
@php
    $milestones = collect($voyage->milestones ?? [])
        ->sortBy(fn($m) => (int) str_replace('d', '', $m->code))
        ->values();

    $total = $milestones->count();
    $completed = $milestones->whereNotNull('actual_date')->count();
    $overdue = $milestones->where('is_overdue', true)->count();
    $dueToday = $milestones->where('is_due_today', true)->count();

    $progressPercent = $total > 0 ? round(($completed / $total) * 100) : 0;
@endphp

<div>
    <div class="flex justify-between items-center mb-2">
        <div class="text-[11px] font-semibold text-gray-600">
            Milestones
        </div>
        <div class="text-[10px] text-gray-400">
            {{ $completed }}/{{ $total }}
        </div>
    </div>

    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2">
        <div class="h-full bg-blue-400 rounded-full transition-all" style="width: {{ $progressPercent }}%"></div>
    </div>

    @if ($milestones->isNotEmpty())
        <div class="grid grid-cols-6 gap-1">
            @foreach ($milestones as $m)
                @php
                    if ($m->actual_date) {
                        $mColor = $m->status === 'ontime' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200';
                        $mIcon = $m->status === 'ontime' ? '✓' : '✗';
                    } elseif ($m->is_overdue) {
                        $mColor = 'bg-red-50 text-red-700 border-red-200';
                        $mIcon = '!';
                    } elseif ($m->is_due_today) {
                        $mColor = 'bg-orange-50 text-orange-700 border-orange-200';
                        $mIcon = '●';
                    } else {
                        $mColor = 'bg-gray-50 text-gray-400 border-gray-100';
                        $mIcon = '—';
                    }
                @endphp

                <div class="rounded border {{ $mColor }} px-1.5 py-1 text-center">
                    <div class="text-[9px] uppercase font-semibold tracking-wide">
                        {{ strtoupper($m->code) }}
                    </div>
                    <div class="text-xs font-bold mt-0.5">
                        {{ $mIcon }}
                    </div>
                    <div class="text-[8px] text-gray-400 mt-0.5">
                        {{ optional($m->milestone_date)->format('d M') }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-[10px] text-gray-400 italic py-2">
            No milestones generated. Set ATD to generate D+ milestones.
        </div>
    @endif
</div>

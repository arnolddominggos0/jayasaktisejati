@php
    $milestones = collect($voyage->milestones ?? [])
        ->sortBy(fn ($m) => (int) str_replace('d', '', $m->code))
        ->values();

    $total = $milestones->count();
    $completed = $milestones->whereNotNull('actual_date')->count();
@endphp

<div>
    <div class="flex items-center justify-between mb-1.5">
        <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">Milestone Rail</span>
        <span class="text-[10px] text-gray-400">{{ $completed }}/{{ $total }}</span>
    </div>

    @if ($milestones->isNotEmpty())
        <div class="flex gap-1 overflow-x-auto pb-1">
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
                <div class="flex-1 min-w-[56px] rounded border {{ $mColor }} px-1.5 py-1.5 text-center">
                    <div class="text-[9px] uppercase font-semibold tracking-wide">{{ strtoupper($m->code) }}</div>
                    <div class="text-sm font-bold mt-0.5 leading-none">{{ $mIcon }}</div>
                    <div class="text-[8px] text-gray-400 mt-0.5 tabular-nums">{{ optional($m->milestone_date)->format('d M') }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-[11px] text-gray-400 italic py-2">Belum ada milestone. Isi ATD untuk generate milestone D+.</div>
    @endif
</div>

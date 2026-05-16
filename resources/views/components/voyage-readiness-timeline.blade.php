@php
    $checkpoints = collect($voyage->checkpoints ?? [])
        ->map(fn($cp) => (object)[
            'type' => 'checkpoint',
            'type_color' => 'bg-blue-100 text-blue-700',
            'code' => $cp->code,
            'date' => $cp->scheduled_at,
            'status' => $cp->is_completed ? 'Done' : ($cp->is_late ? 'Late' : 'Pending'),
            'status_color' => $cp->is_completed ? 'text-green-600' : ($cp->is_late ? 'text-red-600' : 'text-gray-500'),
            'note' => $cp->note,
            'checked_by' => $cp->checked_by,
        ]);

    $vesselChecks = collect($voyage->vesselChecks ?? [])
        ->map(fn($vc) => (object)[
            'type' => 'vessel_check',
            'type_color' => 'bg-purple-100 text-purple-700',
            'code' => $vc->day_code,
            'date' => $vc->check_date?->startOfDay(),
            'status' => match ($vc->status?->value) {
                'on_schedule' => 'OK',
                'potential_delay' => 'Risk',
                default => '-',
            },
            'status_color' => match ($vc->status?->value) {
                'on_schedule' => 'text-green-600',
                'potential_delay' => 'text-orange-600',
                default => 'text-gray-500',
            },
            'note' => $vc->note,
        ]);

    $timeline = $checkpoints
        ->merge($vesselChecks)
        ->sortBy(fn($item) => $item->date?->timestamp ?? PHP_INT_MAX)
        ->values();
@endphp

@if ($timeline->isNotEmpty())
    <div class="space-y-1">
        @foreach ($timeline as $item)
            <div class="flex items-center gap-2 py-1 border-b border-gray-100/40 last:border-0">
                <span class="text-[9px] px-1.5 py-0.5 rounded font-medium {{ $item->type_color }}">
                    {{ $item->type === 'checkpoint' ? 'CP' : 'VC' }}
                </span>

                <span class="text-[11px] font-semibold text-gray-700 w-8">
                    {{ strtoupper($item->code) }}
                </span>

                <span class="text-[11px] {{ $item->status_color }} font-medium flex-1">
                    {{ $item->status }}
                </span>

                <span class="text-[10px] text-gray-400">
                    {{ optional($item->date)->format('d M H:i') }}
                </span>

                @if ($item->note)
                    <span class="text-[9px] text-gray-400 italic truncate max-w-[100px]">
                        {{ $item->note }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="text-xs text-gray-400 italic py-2">
        No readiness data yet.
    </div>
@endif

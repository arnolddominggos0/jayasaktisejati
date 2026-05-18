@php
    $items = collect();

    foreach ($voyage->checkpoints ?? [] as $cp) {
        $items->push((object)[
            'ts' => $cp->scheduled_at?->timestamp ?? PHP_INT_MAX,
            'code' => strtoupper($cp->code),
            'kind' => 'CP',
            'status' => $cp->is_completed ? 'Done' : ($cp->is_late ? 'Late' : 'Open'),
            'statusColor' => $cp->is_completed ? 'text-green-600' : ($cp->is_late ? 'text-red-600' : 'text-gray-400'),
            'detail' => $cp->checked_at ? $cp->checked_at->format('d M H:i') : optional($cp->scheduled_at)->format('d M H:i'),
            'note' => $cp->note,
        ]);
    }

    foreach ($voyage->vesselChecks ?? [] as $vc) {
        $st = match ($vc->status?->value) {
            'on_schedule' => ['OK', 'text-green-600'],
            'potential_delay' => ['Risk', 'text-orange-600'],
            default => ['—', 'text-gray-400'],
        };
        $items->push((object)[
            'ts' => $vc->check_date?->startOfDay()->timestamp ?? PHP_INT_MAX,
            'code' => strtoupper($vc->day_code),
            'kind' => 'VC',
            'status' => $st[0],
            'statusColor' => $st[1],
            'detail' => optional($vc->check_date)->format('d M'),
            'note' => $vc->note,
        ]);
    }

    $items = $items->sortBy('ts')->values();
@endphp

@if ($items->isNotEmpty())
    <div class="space-y-0">
        @foreach ($items as $item)
            <div class="flex items-center gap-2 py-1 px-2 border-l-2 border-l-transparent hover:border-l-gray-200">
                <span class="text-[9px] px-1 rounded bg-gray-100 text-gray-500 font-medium">{{ $item->kind }}</span>
                <span class="text-[11px] font-medium text-gray-700 w-8">{{ $item->code }}</span>
                <span class="text-[11px] {{ $item->statusColor }} font-medium">{{ $item->status }}</span>
                <span class="text-[10px] text-gray-400 ml-auto tabular-nums">{{ $item->detail }}</span>
                @if ($item->note)
                    <span class="text-[9px] text-gray-400 truncate max-w-[120px]">{{ $item->note }}</span>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="text-[11px] text-gray-400 italic py-2">Belum ada data kesiapan.</div>
@endif

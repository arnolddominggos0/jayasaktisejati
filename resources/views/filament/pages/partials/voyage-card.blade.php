@php
    $totalCp = $v->checkpoints?->count() ?? 0;

    $overdueCp = $v->checkpoints?->filter(fn($cp) =>
        !$cp->checked_at && $cp->scheduled_at?->isPast()
    )->count() ?? 0;

    $sla = optional($v->sailingSla)->status;
@endphp

<div class="bg-white rounded-xl p-4 shadow-sm border flex justify-between items-start">

    <div>
        <div class="font-semibold">
            {{ $v->vessel?->name }} — {{ $v->voyage_no }}
        </div>

        <div class="text-sm text-gray-500">
            {{ $v->pol?->code }} → {{ $v->pod?->code }}
        </div>

        @if($v->overdue_days)
            <div class="text-xs text-red-600 mt-1 font-medium">
                Terlambat {{ $v->overdue_days }} hari
            </div>
        @endif

        @if($totalCp > 0)
            <div class="flex gap-2 mt-2 text-xs">
                <span class="px-2 py-1 bg-gray-100 rounded">
                    Checkpoint {{ $totalCp }}
                </span>

                @if($overdueCp > 0)
                    <span class="px-2 py-1 bg-red-100 text-red-600 rounded">
                        {{ $overdueCp }} Terlambat
                    </span>
                @else
                    <span class="px-2 py-1 bg-green-100 text-green-600 rounded">
                        Semua Sesuai
                    </span>
                @endif
            </div>
        @endif
    </div>

    <div class="text-right text-sm space-y-1">
        <div>ETD: {{ optional($v->etd)->format('d M H:i') ?? '-' }}</div>
        <div>ETA: {{ optional($v->eta)->format('d M H:i') ?? '-' }}</div>

        @if($sla)
            <div>
                <span class="px-2 py-1 text-xs rounded {{ $sla->color() }}">
                    {{ $sla->label() }}
                </span>
            </div>
        @endif
    </div>

</div>
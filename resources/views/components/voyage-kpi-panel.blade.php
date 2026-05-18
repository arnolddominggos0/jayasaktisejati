@php
    $otb = $voyage->otb_status;
    $otd = $voyage->otd_status;
    $ota = $voyage->ota_status;
    $sla = $voyage->sla_status;

    $kpis = [];
    foreach ([['OTB', $otb], ['OTD', $otd], ['OTA', $ota]] as [$label, $st]) {
        if ($st) {
            $ok = $st === \App\Enums\SlaStatus::ONTIME;
            $kpis[] = (object)[
                'label' => $label,
                'symbol' => $ok ? '✓' : '✗',
                'color' => $ok ? 'text-green-700' : 'text-red-700',
                'bg' => $ok ? 'bg-green-50/60' : 'bg-red-50/60',
                'border' => $ok ? 'border-green-200' : 'border-red-200',
            ];
        }
    }
@endphp

<div class="flex items-center gap-2 flex-wrap">
    @foreach ($kpis as $kpi)
        <div class="flex items-center gap-1 px-2 py-1 rounded border {{ $kpi->bg }} {{ $kpi->border }}">
            <span class="text-[9px] font-semibold text-gray-500 uppercase">{{ $kpi->label }}</span>
            <span class="text-xs font-bold {{ $kpi->color }}">{{ $kpi->symbol }}</span>
        </div>
    @endforeach

    @if ($sla)
        <div class="flex items-center gap-1 ml-1">
            <span class="text-[10px] text-gray-400">SLA</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $sla === \App\Enums\SlaStatus::ONTIME ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                {{ $sla->label() }}
            </span>
            @if ($voyage->actual_sailing_days && $voyage->planned_sailing_days)
                <span class="text-[10px] text-gray-400">{{ $voyage->actual_sailing_days }}d / {{ $voyage->planned_sailing_days }}d</span>
            @endif
        </div>
    @endif
</div>

@php
    $otb = $voyage->otb_status;
    $otd = $voyage->otd_status;
    $ota = $voyage->ota_status;
    $sla = $voyage->sla_status;

    $kpiBadge = function (?\App\Enums\SlaStatus $status, string $label) {
        if (!$status) {
            return (object)[
                'label' => $label,
                'status' => '—',
                'color' => 'bg-gray-50 text-gray-400 border-gray-100',
                'dot' => 'bg-gray-300',
            ];
        }

        $ok = $status === \App\Enums\SlaStatus::ONTIME;

        return (object)[
            'label' => $label,
            'status' => $ok ? '✓' : '✗',
            'color' => $ok ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100',
            'dot' => $ok ? 'bg-green-500' : 'bg-red-500',
        ];
    };

    $kpis = [
        $kpiBadge($otb, 'OTB'),
        $kpiBadge($otd, 'OTD'),
        $kpiBadge($ota, 'OTA'),
    ];
@endphp

<div class="flex items-center gap-2">
    @foreach ($kpis as $kpi)
        <div class="{{ $kpi->color }} rounded border px-2 py-1 text-center">
            <div class="text-[9px] uppercase font-medium opacity-70">{{ $kpi->label }}</div>
            <div class="mt-0.5 flex items-center justify-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full {{ $kpi->dot }}"></span>
                <span class="text-sm font-bold">{{ $kpi->status }}</span>
            </div>
        </div>
    @endforeach

    @if ($sla)
        <div class="ml-2 text-[10px] text-gray-500">
            SLA: <span class="px-1 py-0.5 rounded text-[10px] font-medium {{ $sla->color() }}">{{ $sla->label() }}</span>
            @if ($voyage->actual_sailing_days && $voyage->planned_sailing_days)
                <span class="ml-1 text-gray-400">({{ $voyage->actual_sailing_days }}d vs {{ $voyage->planned_sailing_days }}d)</span>
            @endif
        </div>
    @endif
</div>

@php
    use App\Enums\SlaStatus;
    use App\Supports\OperationalUi;

    $otb = $voyage->otb_status;
    $otd = $voyage->otd_status;
    $ota = $voyage->ota_status;
    $sla = $voyage->sla_status;
@endphp

<div class="flex items-center gap-2 flex-wrap">
    {!! OperationalUi::kpiBadge($otb, 'OTB') !!}
    {!! OperationalUi::kpiBadge($otd, 'OTD') !!}
    {!! OperationalUi::kpiBadge($ota, 'OTA') !!}

    @if ($sla)
        <div class="flex items-center gap-1 ml-1">
            <span class="text-[10px] text-gray-400">SLA</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $sla === SlaStatus::ONTIME ? 'text-emerald-700 bg-emerald-50' : 'text-red-700 bg-red-50' }}">
                {{ $sla->label() }}
            </span>
            @if ($voyage->actual_sailing_days && $voyage->planned_sailing_days)
                <span class="text-[10px] text-gray-400">{{ $voyage->actual_sailing_days }}d / {{ $voyage->planned_sailing_days }}d</span>
            @endif
        </div>
    @endif
</div>

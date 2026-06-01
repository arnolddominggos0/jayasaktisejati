@php
    $state = $voyage->operationalState;
@endphp

<div class="flex items-center gap-2 flex-wrap">
    {!! \App\Supports\OperationalUi::kpiBadge($state->otb, 'OTB') !!}
    {!! \App\Supports\OperationalUi::kpiBadge($state->otd, 'OTD') !!}
    {!! \App\Supports\OperationalUi::kpiBadge($state->ota, 'OTA') !!}

    @if ($state->sla)
        <div class="flex items-center gap-1 ml-1">
            <span class="text-[10px] text-gray-400">SLA</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ \App\Supports\OperationalUi::severityBadge($state->sla->value === 'ontime' ? 'success' : 'danger') }}">
                {{ $state->sla->label() }}
            </span>
            @if ($voyage->actual_sailing_days && $voyage->planned_sailing_days)
                <span class="text-[10px] text-gray-400">{{ $voyage->actual_sailing_days }}d / {{ $voyage->planned_sailing_days }}d</span>
            @endif
        </div>
    @endif
</div>
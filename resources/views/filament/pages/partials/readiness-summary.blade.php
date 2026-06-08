@php
    use App\Supports\OperationalUi;

    $cpMap = collect($voyage->checkpoints ?? [])
        ->keyBy(fn($cp) => strtolower($cp->code));

    $d2 = $cpMap->get('eta_d2');
    $d1 = $cpMap->get('eta_d1');

    $h1 = collect($voyage->vesselChecks ?? [])
        ->sortByDesc('check_date')
        ->first(fn($vc) => $vc->day_code && str_starts_with(strtolower($vc->day_code), 'h'));
@endphp

@if ($d2 || $d1 || $h1)
    <div class="flex items-center gap-2">
        @if ($d2)
            @php $cell = OperationalUi::checkpointCell($d2); @endphp
            <x-operational.badge :label="$cell['label']" :color="OperationalUi::indicatorClasses($cell['state'])" size="xs" />
        @endif
        @if ($d1)
            @php $cell = OperationalUi::checkpointCell($d1); @endphp
            <x-operational.badge :label="$cell['label']" :color="OperationalUi::indicatorClasses($cell['state'])" size="xs" />
        @endif
        @if ($h1)
            @php $cell = OperationalUi::vesselCheckCell($h1); @endphp
            <x-operational.badge :label="$cell['label']" :color="OperationalUi::indicatorClasses($cell['state'])" size="xs" />
            @if ($h1->status?->value === 'late' && $h1->delay_reason)
                <span class="text-[10px] text-red-600 italic">{{ $h1->delay_reason }}</span>
            @endif
        @endif
    </div>
@else
    <div class="text-[11px] text-gray-400 italic">
        Belum ada readiness check.
    </div>
@endif

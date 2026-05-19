@php
    $state = $v->operationalState;
    $statusBadge = \App\Supports\OperationalUi::operationalStatusLight($state->status);
@endphp

<div class="bg-white rounded-xl p-4 shadow-sm border {{ \App\Supports\OperationalUi::severityBorder($state->severity) }}">

    <div class="flex justify-between items-start">

        <div>

            <div class="font-semibold text-base">
                {{ $v->vessel?->name }} — {{ $v->voyage_no }}
            </div>

            <div class="text-sm text-gray-500">
                {{ $v->pol?->code }} → {{ $v->pod?->code }}
            </div>

            @if ($state->delayOverdueDays())
                <x-operational.badge label="TERLAMBAT {{ $state->delayOverdueDays() }} HARI" color="bg-red-100 text-red-700 border-red-200" size="xs" />
            @endif

            @if ($state->etaStatusLabel())
                <x-operational.badge :label="$state->etaStatusLabel()" :color="\App\Supports\OperationalUi::severityBadge($state->etaStatusSeverity())" size="xs" />
            @endif

        </div>

        <div class="text-right text-sm space-y-1">

            <div>ETD: {{ optional($v->etd)->format('d M H:i') ?? '-' }}</div>
            <div>ETA: {{ optional($v->eta)->format('d M H:i') ?? '-' }}</div>

            <x-operational.badge :label="$statusBadge['label']" :color="$statusBadge['class']" size="xs" />

        </div>

    </div>

    @include('components.voyage-readiness-timeline', ['voyage' => $v])

</div>
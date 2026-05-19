@php
    $state = $v->operationalState;
    $statusBadge = \App\Supports\OperationalUi::operationalStatusLight($state->status);
@endphp

<div class="bg-white rounded-xl p-4 shadow-sm border">

    <div class="flex justify-between items-start">

        <div>

            <div class="font-semibold text-base">
                {{ $v->vessel?->name }} — {{ $v->voyage_no }}
            </div>

            <div class="text-sm text-gray-500">
                {{ $v->pol?->code }} → {{ $v->pod?->code }}
            </div>

            @if ($state->voyage->overdue_days)
                <div class="text-sm text-red-600 font-bold mt-1">
                    TERLAMBAT {{ $state->voyage->overdue_days }} HARI
                </div>
            @endif

            @if ($state->hasSailingRisk)
                <div class="text-sm text-orange-600 font-semibold mt-1">
                    ⚠ ETA kurang dari 24 jam
                </div>
            @endif

            @if ($state->hasEtaOverdue)
                <div class="text-sm text-red-600 font-semibold mt-1">
                    ETA Terlewati
                </div>
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

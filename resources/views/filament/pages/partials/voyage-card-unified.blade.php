@php
    use App\Supports\OperationalUi;

    $state = $v->operationalState;
    $severity = $state->severity;
    $statusBadge = OperationalUi::operationalStatusLight($state->status);

    $milestones = $v->milestones->sortBy(fn($m) => (int) str_replace('d', '', $m->code));
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 {{ OperationalUi::severityBorder($severity) }} p-4 hover:shadow-md transition">

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- HEADER: Vessel-centric primary info                     --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="flex justify-between items-start gap-3">

        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-bold text-base text-gray-900 truncate">
                    {{ $v->vessel?->name }}
                </h3>
                <span class="text-sm text-gray-500 font-medium">
                    {{ $v->voyage_no }}
                </span>
            </div>

            <div class="text-xs text-gray-500 mt-0.5">
                {{ $v->pol?->code ?? '-' }} → {{ $v->pod?->code ?? '-' }}
                @if ($v->shippingLine?->name)
                    <span class="mx-1 text-gray-300">|</span>
                    {{ $v->shippingLine->name }}
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-shrink-0 flex-wrap justify-end">
            <x-operational.badge :label="$statusBadge['label']" :color="$statusBadge['class']" size="xs" />
            <x-operational.severity-badge :severity="$severity" size="xs" />
            {!! OperationalUi::kpiBadge($state->otb, 'OTB') !!}
            {!! OperationalUi::kpiBadge($state->otd, 'OTD') !!}
            {!! OperationalUi::kpiBadge($state->ota, 'OTA') !!}
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- PLANNING STRIP                                          --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
        <div class="flex items-center gap-1">
            <span class="text-[10px] uppercase text-gray-400">ETB</span>
            <span class="text-gray-600">{{ optional($v->etb)->format('d M H:i') ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="text-[10px] uppercase text-gray-400">ETD</span>
            <span class="text-gray-900 font-medium">{{ optional($v->etd)->format('d M H:i') ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="text-[10px] uppercase text-gray-400">ETA</span>
            <span class="text-gray-900 font-medium">{{ optional($v->eta)->format('d M H:i') ?? '—' }}</span>
        </div>
        @if ($v->cargo_plan)
            <div class="flex items-center gap-1">
                <span class="text-[10px] uppercase text-gray-400">Cargo Plan</span>
                <span class="text-gray-700 font-medium">{{ number_format($v->cargo_plan) }}</span>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- ACTUAL STRIP                                            --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if ($v->atb_at || $v->closing_at || $v->atd_at || $v->ata_at)
        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
            @if ($v->atb_at)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">ATB</span>
                    <span class="text-green-700 font-medium">{{ $v->atb_at->format('d M H:i') }}</span>
                    @if ($state->otb)
                        <span class="text-[9px] {{ $state->kpiOk('otb') ? 'text-green-600' : 'text-red-600' }}">
                            ({{ $state->kpiBadge('otb') }})
                        </span>
                    @endif
                </div>
            @endif
            @if ($v->closing_at)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">Closing</span>
                    <span class="text-gray-700">{{ $v->closing_at->format('d M H:i') }}</span>
                </div>
            @endif
            @if ($v->atd_at)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">ATD</span>
                    <span class="text-blue-700 font-medium">{{ $v->atd_at->format('d M H:i') }}</span>
                    @if ($state->otd)
                        <span class="text-[9px] {{ $state->kpiOk('otd') ? 'text-green-600' : 'text-red-600' }}">
                            ({{ $state->kpiBadge('otd') }})
                        </span>
                    @endif
                </div>
            @endif
            @if ($v->ata_at)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">ATA</span>
                    <span class="text-green-700 font-medium">{{ $v->ata_at->format('d M H:i') }}</span>
                    @if ($state->ota)
                        <span class="text-[9px] {{ $state->kpiOk('ota') ? 'text-green-600' : 'text-red-600' }}">
                            ({{ $state->kpiBadge('ota') }})
                        </span>
                    @endif
                </div>
            @endif
            @if ($v->cargo_actual)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">Cargo Actual</span>
                    <span class="text-green-700 font-medium">{{ number_format($v->cargo_actual) }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- ISSUE & DELAY SECTION                                   --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="mt-3">
        @if ($state->delayOverdueDays())
            <div class="flex items-center gap-2 flex-wrap">
                <x-operational.badge label="TERLAMBAT {{ $state->delayOverdueDays() }} HARI" color="bg-red-600 text-white border-red-600" size="xs" />
                @if ($v->manual_delay_reason)
                    <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                @endif
            </div>
        @endif

        @if ($state->isSailing())
            <div class="flex items-center gap-2 flex-wrap">
                @if ($state->sailingDayLabel())
                    <span class="text-[11px] text-blue-700 font-semibold">
                        {{ $state->sailingDayLabel() }}
                    </span>
                @endif

                @if ($state->etaStatusLabel())
                    <x-operational.badge :label="$state->etaStatusLabel()" :color="OperationalUi::severityBadge($state->etaStatusSeverity())" size="xs" />
                @endif

                @if ($state->milestoneOverdueLabel())
                    <x-operational.badge :label="$state->milestoneOverdueLabel()" :color="OperationalUi::indicatorClasses($state->milestoneSeverity())" size="xs" />
                @endif

                @if ($v->manual_delay_reason)
                    <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                @endif
            </div>
        @endif

        @if ($state->isScheduled())
            <div class="flex items-center gap-2 flex-wrap">
                @if ($state->daysUntilEtdLabel())
                    @if ($state->daysUntilEtd === 0)
                        <x-operational.badge :label="$state->daysUntilEtdLabel()" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                    @elseif ($state->daysUntilEtd <= 2)
                        <x-operational.badge :label="$state->daysUntilEtdLabel()" color="bg-blue-100 text-blue-700 border-blue-200" size="xs" />
                    @else
                        <span class="text-[11px] text-gray-500">
                            ETD {{ $state->daysUntilEtd }} hari lagi
                        </span>
                    @endif
                @endif

                @if ($state->readinessIssueLabel())
                    <x-operational.badge :label="$state->readinessIssueLabel()" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                @endif

                @if ($v->manual_delay_reason)
                    <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                @endif
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- MILESTONE SUMMARY STRIP                                 --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if ($milestones->count())
        <div class="mt-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10px] uppercase text-gray-400 font-medium">Milestones:</span>
                @foreach ($milestones as $m)
                    <x-operational.milestone-chip :milestone="$m" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- READINESS SUMMARY                                       --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="mt-3 pt-3 border-t border-gray-100">
        <div class="flex items-center justify-between gap-3">
            <div>
                @include('filament.pages.partials.readiness-summary', ['voyage' => $v])
            </div>

            @if ($v->cargo_plan || $v->cargo_actual)
                <div class="text-[11px] text-gray-500 whitespace-nowrap">
                    @if ($v->cargo_plan)
                        Plan: <span class="font-medium text-gray-700">{{ number_format($v->cargo_plan) }}</span>
                    @endif
                    @if ($v->cargo_actual)
                        <span class="text-gray-300 mx-1">|</span>
                        Actual: <span class="font-medium text-green-700">{{ number_format($v->cargo_actual) }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- QUICK ACTIONS BAR                                       --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2">

        <a href="{{ \App\Filament\Resources\VoyageResource::getUrl('view', ['record' => $v]) }}"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800 transition"
            target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
            </svg>
            Detail
        </a>

        @if ($state->canShowMilestone && $state->isSailing())
            <button wire:click="showMilestone({{ $milestones->firstWhere('actual_date', null)?->id ?? $milestones->last()->id }})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
                Milestone
            </button>
        @endif

        @if ($state->canAcknowledge && !in_array($v->id, $acknowledged, true))
            <button wire:click="acknowledgeVoyage({{ $v->id }})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition
                {{ $severity === 'critical' ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-orange-200 text-orange-700 hover:bg-orange-50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Acknowledge
            </button>
        @endif

    </div>

</div>
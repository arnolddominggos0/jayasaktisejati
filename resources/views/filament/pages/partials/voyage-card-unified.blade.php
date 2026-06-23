@php
    use App\Supports\OperationalUi;

    $state = $v->operationalState;
    $severity = $state->severity;
    $statusBadge = OperationalUi::operationalStatusLight($status);

    $milestones = $v->milestones->sortBy(fn($m) => (int) str_replace('d', '', $m->code));
@endphp

<div class="rounded-xl border border-gray-200 bg-white {{ OperationalUi::severityBorder($severity) }} p-4 hover:shadow-sm transition">

    {{-- HEADER: Vessel + Severity focal --}}
    <div class="flex justify-between items-start gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                @if ($severity === 'critical')
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200 uppercase tracking-wide">urgent</span>
                @elseif ($severity === 'warning')
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-orange-100 text-orange-700 border border-orange-200 uppercase tracking-wide">perhatian</span>
                @endif
                <h3 class="font-bold text-[14px] text-gray-900 truncate">
                    {{ $v->vessel?->name }}
                </h3>
                <span class="text-[11px] font-mono font-semibold text-gray-700">
                    {{ $v->code ?? $v->voyage_no }}
                </span>
                @if ($v->code)
                    <span class="text-[10px] text-gray-400 font-mono">({{ $v->voyage_no }})</span>
                @endif
            </div>
            <div class="mt-1 text-[11px] text-gray-400">
                {{ \App\Supports\BusinessRouteResolver::forVoyage($v) }}
                @if ($v->shippingLine?->name)
                    <span class="mx-0.5 text-gray-300">·</span>
                    {{ $v->shippingLine->name }}
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-shrink-0 flex-wrap justify-end">
            {{-- Operational status badge --}}
            <x-operational.badge :label="$statusBadge['label']" :color="$statusBadge['class']" size="xs" />

            {{-- Severity badge --}}
            <x-operational.severity-badge :severity="$severity" size="xs" />

            {{-- KPI badges (compact) --}}
            {!! OperationalUi::kpiBadge($state->otb, 'OTB') !!}
            {!! OperationalUi::kpiBadge($state->otd, 'OTD') !!}
            {!! OperationalUi::kpiBadge($state->ota, 'OTA') !!}
        </div>
    </div>

    {{-- TIMELINE STRIP: compact ETB → ETA --}}
    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
        <div class="flex items-center gap-1">
            <span class="text-[10px] uppercase text-gray-400">ETB</span>
            <span class="text-gray-500">{{ optional($v->etb)->format('d M') ?? '—' }}</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="text-[10px] uppercase text-gray-400">ETD</span>
            <span class="{{ $v->atd_at ? 'text-gray-500' : 'text-gray-900 font-semibold' }}">{{ optional($v->etd)->format('d M') ?? '—' }}</span>
        </div>
        @if ($v->atd_at)
            <div class="flex items-center gap-1">
                <span class="text-[10px] uppercase text-gray-400">ATD</span>
                <span class="text-blue-700 font-semibold">{{ $v->atd_at->format('d M') }}</span>
            </div>
        @endif
        <div class="flex items-center gap-1">
            <span class="text-[10px] uppercase text-gray-400">ETA</span>
            <span class="text-gray-900 font-medium">{{ optional($v->eta)->format('d M') ?? '—' }}</span>
        </div>
        @if ($v->ata_at)
            <div class="flex items-center gap-1">
                <span class="text-[10px] uppercase text-gray-400">ATA</span>
                <span class="text-green-700 font-semibold">{{ $v->ata_at->format('d M') }}</span>
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
        @if ($status === VoyageOperationalStatus::DELAYED && $state->voyage->overdue_days)
            <div class="flex items-center gap-2 flex-wrap">
                <x-operational.badge label="TERLAMBAT {{ $state->voyage->overdue_days }} HARI" color="bg-red-600 text-white border-red-600" size="xs" />
                @if ($v->manual_delay_reason)
                    <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                @endif
            </div>
        @endif

        @if ($status === VoyageOperationalStatus::SAILING)
            <div class="flex items-center gap-2 flex-wrap">
                @if ($state->sailingDays)
                    <span class="text-[11px] text-blue-700 font-semibold">
                        Berlayar hari ke-{{ $state->sailingDays }}
                    </span>
                @endif

                @if ($state->hasEtaOverdue)
                    <x-operational.badge label="ETA TERLEWATI" color="bg-red-100 text-red-700 border-red-200" size="xs" />
                @elseif ($state->hasSailingRisk)
                    <x-operational.badge label="RISIKO ETA &lt; 24 JAM" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                @endif

                @if ($state->milestoneOverdueCount > 0)
                    <x-operational.badge :label="$state->milestoneOverdueCount . ' MILESTONE LEWAT'" color="bg-red-50 text-red-600 border-red-100" size="xs" />
                @elseif ($state->milestoneDueTodayCount > 0)
                    <x-operational.badge :label="$state->milestoneDueTodayCount . ' MILESTONE HARI INI'" color="bg-orange-50 text-orange-600 border-orange-100" size="xs" />
                @endif

                @if ($v->manual_delay_reason)
                    <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                @endif
            </div>
        @endif

        @if ($status === VoyageOperationalStatus::SCHEDULED)
            <div class="flex items-center gap-2 flex-wrap">
                @if ($state->daysUntilEtd !== null)
                    @if ($state->daysUntilEtd === 0)
                        <x-operational.badge label="ETD HARI INI" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                    @elseif ($state->daysUntilEtd <= 2)
                        <x-operational.badge label="ETD {{ $state->daysUntilEtd }} HARI LAGI" color="bg-blue-100 text-blue-700 border-blue-200" size="xs" />
                    @else
                        <span class="text-[11px] text-gray-500">
                            ETD {{ $state->daysUntilEtd }} hari lagi
                        </span>
                    @endif
                @endif

                @if ($state->hasReadinessIssue)
                    <x-operational.badge label="MASALAH KESIAPAN" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                @endif
                @if ($h1)
                    @php $cell = OperationalUi::vesselCheckCell($h1); @endphp
                    <x-operational.badge :label="$cell['label']" :color="OperationalUi::indicatorClasses($cell['state'])" size="xs" />
                @endif
            @endif
            @if ($state->milestoneTotalCount > 0)
                <span class="text-[10px] uppercase text-gray-400 font-medium ml-auto">Milest.</span>
                @foreach ($v->milestones->sortBy(fn($m) => (int) str_replace('d', '', $m->code))->take(4) as $m)
                    <x-operational.milestone-chip :milestone="$m" />
                @endforeach
            @endif
        </div>
    @endif

    {{-- CARGO (only if exists) --}}
    @if ($v->cargo_plan || $v->cargo_actual)
        <div class="mt-2 text-[11px] text-gray-400">
            @if ($v->cargo_plan)
                Plan <span class="font-medium text-gray-600">{{ number_format($v->cargo_plan) }}</span>
            @endif
            @if ($v->cargo_actual)
                <span class="ml-2">Actual <span class="font-medium text-green-700">{{ number_format($v->cargo_actual) }}</span></span>
            @endif
        </div>
    @endif

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

        @if ($state->canShowMilestone && $status === VoyageOperationalStatus::SAILING)
            <button wire:click="showMilestone({{ $milestones->firstWhere('actual_date', null)?->id ?? $milestones->last()->id }})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                </svg>
                Input ATA
            </button>
        @elseif ($state->canAcknowledge)
            <button wire:click="acknowledgeVoyage({{ $v->id }})"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border {{ $severity === 'critical' ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-orange-200 text-orange-700 hover:bg-orange-50' }} text-[11px] font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Acknowledge
            </button>
        @endif

        <a href="{{ \App\Filament\Resources\VoyageResource::getUrl('view', ['record' => $v]) }}"
            target="_blank"
            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white text-[11px] text-gray-600 font-medium hover:bg-gray-50 transition">
            Detail
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
    </div>

</div>
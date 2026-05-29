@php
    use App\Enums\VoyageOperationalStatus;
    use App\Supports\OperationalUi;

    $state = $v->operationalState;
    $status = $state->status;
    $severity = $state->severity;
    $statusBadge = OperationalUi::operationalStatusLight($status);
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
                <span class="text-[11px] text-gray-500 font-medium">{{ $v->voyage_no }}</span>
            </div>
            <div class="mt-1 text-[11px] text-gray-400">
                {{ $v->pol?->code ?? '-' }} → {{ $v->pod?->code ?? '-' }}
                @if ($v->shippingLine?->name)
                    <span class="mx-0.5 text-gray-300">·</span>
                    {{ $v->shippingLine->name }}
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-shrink-0">
            <x-operational.badge :label="$statusBadge['label']" :color="$statusBadge['class']" size="xs" />
            @if ($state->otd)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $state->kpiOk('otd') ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200' }}">OTD {{ $state->kpiOk('otd') ? 'ok' : 'ng' }}</span>
            @endif
            @if ($state->ota)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $state->kpiOk('ota') ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200' }}">OTA {{ $state->kpiOk('ota') ? 'ok' : 'ng' }}</span>
            @endif
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

    {{-- STATUS-SPECIFIC STRIP --}}
    @if ($status === VoyageOperationalStatus::DELAYED && $state->voyage->overdue_days)
        <div class="mt-2 flex items-center gap-2">
            <x-operational.badge label="TERLAMBAT {{ $state->voyage->overdue_days }} HARI" color="bg-red-600 text-white border-red-600" size="xs" />
            @if ($v->manual_delay_reason)
                <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
            @endif
        </div>
    @endif

    @if ($status === VoyageOperationalStatus::SAILING)
        <div class="mt-2 flex items-center gap-2 flex-wrap">
            @if ($state->sailingDays)
                <span class="text-[11px] text-blue-700 font-semibold">Hari ke-{{ $state->sailingDays }}</span>
            @endif
            @if ($state->hasEtaOverdue)
                <x-operational.badge label="ETA TERLEWATI" color="bg-red-100 text-red-700 border-red-200" size="xs" />
            @elseif ($state->hasSailingRisk)
                <x-operational.badge label="RISIKO ETA" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
            @endif
            @if ($state->milestoneOverdueCount > 0)
                <x-operational.badge :label="$state->milestoneOverdueCount . ' MILESTONE LEWAT'" color="bg-red-50 text-red-600 border-red-100" size="xs" />
            @endif
            @if ($v->manual_delay_reason)
                <x-operational.badge :label="$v->manual_delay_reason->label()" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
            @endif
        </div>
    @endif

    @if ($status === VoyageOperationalStatus::SCHEDULED && $state->daysUntilEtd !== null)
        <div class="mt-2 flex items-center gap-2">
            @if ($state->daysUntilEtd === 0)
                <x-operational.badge label="ETD HARI INI" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
            @elseif ($state->daysUntilEtd <= 2)
                <x-operational.badge label="ETD {{ $state->daysUntilEtd }} HARI" color="bg-blue-100 text-blue-700 border-blue-200" size="xs" />
            @else
                <span class="text-[11px] text-gray-500">ETD {{ $state->daysUntilEtd }} hari</span>
            @endif
            @if ($state->hasReadinessIssue)
                <x-operational.badge label="KESIAPAN" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
            @endif
        </div>
    @endif

    {{-- MILESTONE + READINESS COMPACT --}}
    @if ($state->hasReadinessIssue || $state->milestoneTotalCount > 0)
        <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center gap-2 flex-wrap">
            @if ($state->hasReadinessIssue)
                <span class="text-[10px] uppercase text-gray-400 font-medium">Kesiap.</span>
                @php
                    $cpMap = collect($v->checkpoints ?? [])->keyBy(fn($cp) => strtolower($cp->code));
                    $d1 = $cpMap->get('eta_d1');
                    $h1 = collect($v->vesselChecks ?? [])->sortByDesc('check_date')->first(fn($vc) => str_starts_with(strtolower($vc->day_code ?? ''), 'h'));
                @endphp
                @if ($d1)
                    @php $cell = OperationalUi::checkpointCell($d1); @endphp
                    <x-operational.badge :label="$cell['label']" :color="OperationalUi::indicatorClasses($cell['state'])" size="xs" />
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

    {{-- SINGLE CTA --}}
    <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center gap-2">
        @if ($state->canInputAtd && !$v->atd_at)
            <button wire:click="openInlineModal('atd', {{ $v->id }})"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-[11px] font-semibold hover:bg-blue-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                </svg>
                Input ATD
            </button>
        @elseif ($state->canInputAta && !$v->ata_at)
            <button wire:click="openInlineModal('ata', {{ $v->id }})"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-[11px] font-semibold hover:bg-blue-700 transition">
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
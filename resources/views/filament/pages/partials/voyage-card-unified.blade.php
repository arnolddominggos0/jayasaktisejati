@php
    $status = $v->operational_status_enum;

    // ── Severity / Issue Level ───────────────────────────────
    $severity = match (true) {
        $status === \App\Enums\VoyageOperationalStatus::DELAYED && $v->overdue_days > 0 => 'critical',
        $v->eta_overdue => 'critical',
        $v->sailing_risk => 'warning',
        $v->milestones->where('is_overdue', true)->count() > 0 => 'warning',
        $v->checkpoints->contains(fn($cp) => !$cp->is_completed && $cp->scheduled_at?->isPast()) => 'warning',
        $v->vesselChecks->contains(fn($vc) => $vc->status?->value === 'potential_delay') => 'warning',
        default => 'normal',
    };

    $severityColors = [
        'critical' => 'bg-red-100 text-red-700 border-red-200',
        'warning'  => 'bg-orange-100 text-orange-700 border-orange-200',
        'normal'   => 'bg-gray-100 text-gray-700 border-gray-200',
    ];

    $severityLabel = [
        'critical' => 'KRITIS',
        'warning'  => 'PERHATIAN',
        'normal'   => 'NORMAL',
    ];

    $cardBorder = match ($severity) {
        'critical' => 'border-l-4 border-l-red-500',
        'warning'  => 'border-l-4 border-l-orange-500',
        default    => 'border-l-4 border-l-gray-300',
    };

    // ── Milestones ──────────────────────────────────────────
    $milestones = $v->milestones->sortBy(fn($m) => (int) str_replace('d', '', $m->code));
    $milestoneOverdue = $v->milestones->where('is_overdue', true)->count();
    $milestoneDueToday = $v->milestones->where('is_due_today', true)->count();

    // ── Sailing Progress ────────────────────────────────────
    $sailingDays = null;
    if ($v->atd_at) {
        $sailingDays = max(1, (int) $v->atd_at->diffInDays(now()));
    }

    // ── Days until ETD (for scheduled) ──────────────────────
    $daysUntilEtd = null;
    if ($status === \App\Enums\VoyageOperationalStatus::SCHEDULED && $v->etd) {
        $daysUntilEtd = (int) now()->diffInDays($v->etd, false);
        if ($daysUntilEtd < 0) $daysUntilEtd = 0;
    }

    // ── Readiness issue detection ───────────────────────────
    $hasReadinessIssue = $v->checkpoints->contains(fn($cp) => !$cp->is_completed && $cp->scheduled_at?->isPast())
        || $v->vesselChecks->contains(fn($vc) => $vc->status?->value === 'potential_delay');

    // ── KPI helpers ─────────────────────────────────────────
    $kpiBadge = function (mixed $slaStatus, string $label) {
        if (!$slaStatus) {
            return (object)['html' => '<span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-400 border border-gray-200">' . $label . ' —</span>'];
        }
        $ok = $slaStatus->value === 'ontime';
        $color = $ok ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200';
        return (object)['html' => '<span class="px-1.5 py-0.5 rounded text-[10px] font-medium ' . $color . '">' . $label . ' ' . ($ok ? 'OK' : 'NG') . '</span>'];
    };
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 {{ $cardBorder }} p-4 hover:shadow-md transition">

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
            {{-- Operational status badge --}}
            <span class="px-2 py-0.5 rounded text-[11px] font-semibold whitespace-nowrap {{ $status->color() }}">
                {{ $status->label() }}
            </span>

            {{-- Severity badge --}}
            <span class="px-2 py-0.5 rounded text-[11px] font-semibold border whitespace-nowrap {{ $severityColors[$severity] }}">
                {{ $severityLabel[$severity] }}
            </span>

            {{-- KPI badges (compact) --}}
            {!! $kpiBadge($v->otb_status, 'OTB')->html !!}
            {!! $kpiBadge($v->otd_status, 'OTD')->html !!}
            {!! $kpiBadge($v->ota_status, 'OTA')->html !!}
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- PLANNING STRIP (Excel parity: ETB / ETD / ETA / Cargo)  --}}
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
    {{-- ACTUAL STRIP (Excel parity: ATB / Closing / ATD / ATA)  --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if ($v->atb_at || $v->closing_at || $v->atd_at || $v->ata_at)
        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
            @if ($v->atb_at)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">ATB</span>
                    <span class="text-green-700 font-medium">{{ $v->atb_at->format('d M H:i') }}</span>
                    @if ($v->otb_status)
                        <span class="text-[9px] {{ $v->otb_status->value === 'ontime' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $v->otb_status->value === 'ontime' ? '(OK)' : '(NG)' }}
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
                    @if ($v->otd_status)
                        <span class="text-[9px] {{ $v->otd_status->value === 'ontime' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $v->otd_status->value === 'ontime' ? '(OK)' : '(NG)' }}
                        </span>
                    @endif
                </div>
            @endif
            @if ($v->ata_at)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] uppercase text-gray-400">ATA</span>
                    <span class="text-green-700 font-medium">{{ $v->ata_at->format('d M H:i') }}</span>
                    @if ($v->ota_status)
                        <span class="text-[9px] {{ $v->ota_status->value === 'ontime' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $v->ota_status->value === 'ontime' ? '(OK)' : '(NG)' }}
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
    {{-- ISSUE & DELAY SECTION (issue-first, prominent)          --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="mt-3">
        @if ($status === \App\Enums\VoyageOperationalStatus::DELAYED && $v->overdue_days)
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-600 text-white text-xs font-bold">
                    TERLAMBAT {{ $v->overdue_days }} HARI
                </span>
                @if ($v->manual_delay_reason)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px] font-medium border border-gray-200">
                        {{ $v->manual_delay_reason->label() }}
                    </span>
                @endif
            </div>
        @endif

        @if ($status === \App\Enums\VoyageOperationalStatus::SAILING)
            <div class="flex items-center gap-2 flex-wrap">
                @if ($sailingDays)
                    <span class="text-[11px] text-blue-700 font-semibold">
                        Berlayar hari ke-{{ $sailingDays }}
                    </span>
                @endif

                @if ($v->eta_overdue)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-100 text-red-700 text-[11px] font-bold border border-red-200">
                        ETA TERLEWATI
                    </span>
                @elseif ($v->sailing_risk)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[11px] font-bold border border-orange-200">
                        ETA RISK &lt; 24 JAM
                    </span>
                @endif

                @if ($milestoneOverdue > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-50 text-red-600 text-[11px] font-medium border border-red-100">
                        {{ $milestoneOverdue }} milestone overdue
                    </span>
                @elseif ($milestoneDueToday > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-orange-50 text-orange-600 text-[11px] font-medium border border-orange-100">
                        {{ $milestoneDueToday }} milestone hari ini
                    </span>
                @endif

                @if ($v->manual_delay_reason)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px] font-medium border border-gray-200">
                        {{ $v->manual_delay_reason->label() }}
                    </span>
                @endif
            </div>
        @endif

        @if ($status === \App\Enums\VoyageOperationalStatus::SCHEDULED)
            <div class="flex items-center gap-2 flex-wrap">
                @if ($daysUntilEtd !== null)
                    @if ($daysUntilEtd === 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[11px] font-bold border border-orange-200">
                            ETD HARI INI
                        </span>
                    @elseif ($daysUntilEtd <= 2)
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-[11px] font-bold border border-blue-200">
                            ETD {{ $daysUntilEtd }} HARI LAGI
                        </span>
                    @else
                        <span class="text-[11px] text-gray-500">
                            ETD {{ $daysUntilEtd }} hari lagi
                        </span>
                    @endif
                @endif

                @if ($hasReadinessIssue)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[11px] font-medium border border-orange-200">
                        READINESS ISSUE
                    </span>
                @endif

                @if ($v->manual_delay_reason)
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px] font-medium border border-gray-200">
                        {{ $v->manual_delay_reason->label() }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- MILESTONE SUMMARY STRIP (D+4 / D+6 / D+8 / D+10 / D+12) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if ($milestones->count())
        <div class="mt-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10px] uppercase text-gray-400 font-medium">Milestones:</span>
                @foreach ($milestones as $m)
                    @php
                        if ($m->actual_date) {
                            $mColor = $m->status === 'ontime' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200';
                        } elseif ($m->is_overdue) {
                            $mColor = 'bg-red-100 text-red-700 border-red-200';
                        } elseif ($m->is_due_today) {
                            $mColor = 'bg-orange-100 text-orange-700 border-orange-200';
                        } else {
                            $mColor = 'bg-gray-100 text-gray-400 border-gray-200';
                        }
                    @endphp
                    <button wire:click="showMilestone({{ $m->id }})"
                        class="rounded px-1.5 py-0.5 border text-[10px] font-semibold {{ $mColor }} hover:scale-105 transition cursor-pointer"
                        title="{{ $m->port?->name ?? 'Milestone ' . strtoupper($m->code) }} — Target: {{ optional($m->milestone_date)->format('d M') }}">
                        {{ strtoupper($m->code) }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- READINESS SUMMARY (simplified, compact)                 --}}
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

        @if ($status === \App\Enums\VoyageOperationalStatus::SAILING && $milestones->count())
            <button wire:click="showMilestone({{ $milestones->firstWhere('actual_date', null)?->id ?? $milestones->last()->id }})"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
                Milestone
            </button>
        @endif

        @if ($severity !== 'normal' && !in_array($v->id, $acknowledged, true))
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

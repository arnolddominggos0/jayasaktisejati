<x-filament-panels::page>
    @php
        $v = $this->record;

        if (!$v) {
            echo '<div class="text-sm text-gray-500 py-8">Tidak ada data voyage.</div>';
            return;
        }

        $status = $v->operational_status_enum;
        $isDelayed = $v->is_delayed;
        $delayDays = $v->departure_delay_days;
        $overdueDays = $v->overdue_days;
        $delaySeverity = $v->departure_delay_severity;

        $headerBorder = match (true) {
            $overdueDays > 0 => 'border-l-red-500',
            $isDelayed && $delaySeverity === 'critical' => 'border-l-red-500',
            $isDelayed => 'border-l-orange-400',
            $status->value === 'sailing' => 'border-l-blue-400',
            $status->value === 'completed' => 'border-l-green-400',
            default => 'border-l-gray-300',
        };

        $headerBg = match (true) {
            $overdueDays > 0 => 'bg-red-50/20',
            $isDelayed && $delaySeverity === 'critical' => 'bg-red-50/20',
            $isDelayed => 'bg-orange-50/10',
            $status->value === 'sailing' => 'bg-blue-50/10',
            $status->value === 'completed' => 'bg-green-50/10',
            default => 'bg-white',
        };

        $status = $v->operational_status_enum;
        $isDelayed = $v->is_delayed;
        $delayDays = $v->departure_delay_days;
        $overdueDays = $v->overdue_days;
        $delaySeverity = $v->departure_delay_severity;

        $headerBorder = match (true) {
            $overdueDays > 0 => 'border-l-red-500',
            $isDelayed && $delaySeverity === 'critical' => 'border-l-red-500',
            $isDelayed => 'border-l-orange-400',
            $status->value === 'sailing' => 'border-l-blue-400',
            $status->value === 'completed' => 'border-l-green-400',
            default => 'border-l-gray-300',
        };

        $headerBg = match (true) {
            $overdueDays > 0 => 'bg-red-50/20',
            $isDelayed && $delaySeverity === 'critical' => 'bg-red-50/20',
            $isDelayed => 'bg-orange-50/10',
            $status->value === 'sailing' => 'bg-blue-50/10',
            $status->value === 'completed' => 'bg-green-50/10',
            default => 'bg-white',
        };

        $statusBadge = match ($status->value) {
            'scheduled' => 'bg-gray-50 text-gray-600 border-gray-200',
            'sailing' => 'bg-blue-50 text-blue-700 border-blue-200',
            'delayed' => 'bg-red-50 text-red-700 border-red-200',
            'completed' => 'bg-green-50 text-green-700 border-green-200',
            default => 'bg-gray-50 text-gray-600 border-gray-200',
        };

        $kpis = [];
        foreach ([['OTB', $v->otb_status], ['OTD', $v->otd_status], ['OTA', $v->ota_status]] as [$label, $st]) {
            if ($st) {
                $ok = $st === \App\Enums\SlaStatus::ONTIME;
                $kpis[] = (object) [
                    'label' => $label,
                    'ok' => $ok,
                    'symbol' => $ok ? '✓' : '✗',
                    'color' => $ok ? 'text-green-700' : 'text-red-700',
                    'bg' => $ok ? 'bg-green-50/60' : 'bg-red-50/60',
                    'border' => $ok ? 'border-green-200' : 'border-red-200',
                ];
            }
        }

        $anomalies = [];
        if ($overdueDays) {
            $anomalies[] = (object) [
                'text' => "Overdue {$overdueDays}d",
                'class' => 'text-red-700 bg-red-50 border-red-200',
            ];
        } elseif ($delayDays > 0) {
            $anomalies[] = (object) [
                'text' => "Delay +{$delayDays}d",
                'class' => 'text-orange-700 bg-orange-50 border-orange-200',
            ];
        }
        if ($v->milestone_severity === 'critical') {
            $anomalies[] = (object) [
                'text' => 'Milestone Critical',
                'class' => 'text-red-700 bg-red-50 border-red-200',
            ];
        } elseif ($v->milestone_severity === 'warning') {
            $anomalies[] = (object) [
                'text' => 'Milestone Due',
                'class' => 'text-orange-700 bg-orange-50 border-orange-200',
            ];
        }
        if ($v->eta_overdue) {
            $anomalies[] = (object) ['text' => 'ETA Overdue', 'class' => 'text-red-700 bg-red-50 border-red-200'];
        }
        if ($v->sailing_risk) {
            $anomalies[] = (object) [
                'text' => 'Sailing Risk',
                'class' => 'text-amber-700 bg-amber-50 border-amber-200',
            ];
        }

        $cargoPct = $v->cargo_plan > 0 ? round(($v->cargo_actual / $v->cargo_plan) * 100) : null;

        // ── Build unified operational timeline ───────────────────────────
        $events = collect();

        foreach ($v->checkpoints ?? [] as $cp) {
            $events->push(
                (object) [
                    'ts' => $cp->scheduled_at?->timestamp ?? PHP_INT_MAX,
                    'date' => $cp->scheduled_at,
                    'type' => 'readiness',
                    'code' => strtoupper($cp->code),
                    'label' => strtoupper($cp->code),
                    'state' => $cp->is_completed ? '✓' : ($cp->is_late ? '!' : '•'),
                    'stateColor' => $cp->is_completed
                        ? 'text-green-600'
                        : ($cp->is_late
                            ? 'text-red-600'
                            : 'text-gray-400'),
                    'detail' => $cp->checked_at
                        ? $cp->checked_at->format('d M H:i')
                        : optional($cp->scheduled_at)->format('d M H:i'),
                    'note' => $cp->note,
                    'priority' => $cp->is_late ? 3 : ($cp->is_completed ? 1 : 2),
                ],
            );
        }

        foreach ($v->vesselChecks ?? [] as $vc) {
            $st = match ($vc->status?->value) {
                'on_schedule' => ['✓', 'text-green-600', 1],
                'potential_delay' => ['!', 'text-orange-600', 3],
                default => ['•', 'text-gray-400', 2],
            };
            $events->push(
                (object) [
                    'ts' => $vc->check_date?->startOfDay()->timestamp ?? PHP_INT_MAX,
                    'date' => $vc->check_date?->startOfDay(),
                    'type' => 'readiness',
                    'code' => strtoupper($vc->day_code),
                    'label' => strtoupper($vc->day_code),
                    'state' => $st[0],
                    'stateColor' => $st[1],
                    'detail' => optional($vc->etd_plan)->format('d M H:i'),
                    'note' => $vc->note,
                    'priority' => $st[2],
                ],
            );
        }

        if ($v->etb) {
            $events->push(
                (object) [
                    'ts' => $v->etb->timestamp,
                    'date' => $v->etb,
                    'type' => 'plan',
                    'code' => 'ETB',
                    'label' => 'ETB',
                    'state' => 'P',
                    'stateColor' => 'text-indigo-500',
                    'detail' => $v->etb->format('d M H:i'),
                    'note' => null,
                    'priority' => 1,
                ],
            );
        }

        if ($v->atb_at) {
            $ok = $v->otb_status === \App\Enums\SlaStatus::ONTIME;
            $events->push(
                (object) [
                    'ts' => $v->atb_at->timestamp,
                    'date' => $v->atb_at,
                    'type' => 'actual',
                    'code' => 'ATB',
                    'label' => 'ATB',
                    'state' => $ok ? '✓' : '✗',
                    'stateColor' => $ok ? 'text-green-600' : 'text-red-600',
                    'detail' => $v->atb_at->format('d M H:i'),
                    'note' => null,
                    'priority' => $ok ? 1 : 3,
                ],
            );
        }

        if ($v->closing_at) {
            $events->push(
                (object) [
                    'ts' => $v->closing_at->timestamp,
                    'date' => $v->closing_at,
                    'type' => 'actual',
                    'code' => 'CL',
                    'label' => 'Closing',
                    'state' => '✓',
                    'stateColor' => 'text-gray-500',
                    'detail' => $v->closing_at->format('d M H:i'),
                    'note' => null,
                    'priority' => 1,
                ],
            );
        }

        if ($v->atd_at) {
            $ok = $v->otd_status === \App\Enums\SlaStatus::ONTIME;
            $events->push(
                (object) [
                    'ts' => $v->atd_at->timestamp,
                    'date' => $v->atd_at,
                    'type' => 'actual',
                    'code' => 'ATD',
                    'label' => 'ATD',
                    'state' => $ok ? '✓' : '✗',
                    'stateColor' => $ok ? 'text-green-600' : 'text-red-600',
                    'detail' => $v->atd_at->format('d M H:i'),
                    'note' => null,
                    'priority' => $ok ? 1 : 3,
                ],
            );
        }

        foreach ($v->milestones ?? [] as $m) {
            if ($m->actual_date) {
                $ok = $m->status === 'ontime';
                $events->push(
                    (object) [
                        'ts' => $m->actual_date->timestamp,
                        'date' => $m->actual_date,
                        'type' => 'milestone',
                        'code' => strtoupper($m->code),
                        'label' => strtoupper($m->code),
                        'state' => $ok ? '✓' : '✗',
                        'stateColor' => $ok ? 'text-green-600' : 'text-red-600',
                        'detail' => $m->actual_date->format('d M') . ($m->speed_knots ? " · {$m->speed_knots}kn" : ''),
                        'note' => $m->note,
                        'priority' => $ok ? 1 : 3,
                    ],
                );
            } else {
                $prio = $m->is_overdue ? 3 : ($m->is_due_today ? 3 : 2);
                $events->push(
                    (object) [
                        'ts' => optional($m->milestone_date)->timestamp ?? PHP_INT_MAX,
                        'date' => $m->milestone_date,
                        'type' => 'milestone',
                        'code' => strtoupper($m->code),
                        'label' => strtoupper($m->code),
                        'state' => $m->is_overdue ? '!' : ($m->is_due_today ? '●' : '•'),
                        'stateColor' => $m->is_overdue
                            ? 'text-red-600'
                            : ($m->is_due_today
                                ? 'text-orange-500'
                                : 'text-gray-300'),
                        'detail' => optional($m->milestone_date)->format('d M') . ' (plan)',
                        'note' => $m->note,
                        'priority' => $prio,
                    ],
                );
            }
        }

        if ($v->ata_at) {
            $ok = $v->ota_status === \App\Enums\SlaStatus::ONTIME;
            $events->push(
                (object) [
                    'ts' => $v->ata_at->timestamp,
                    'date' => $v->ata_at,
                    'type' => 'actual',
                    'code' => 'ATA',
                    'label' => 'ATA',
                    'state' => $ok ? '✓' : '✗',
                    'stateColor' => $ok ? 'text-green-600' : 'text-red-600',
                    'detail' => $v->ata_at->format('d M H:i'),
                    'note' => null,
                    'priority' => $ok ? 1 : 3,
                ],
            );
        }

        foreach ($v->delayLogs ?? [] as $log) {
            $events->push(
                (object) [
                    'ts' => $log->created_at->timestamp,
                    'date' => $log->created_at,
                    'type' => 'delay',
                    'code' => '!',
                    'label' => 'Delay',
                    'state' => '!',
                    'stateColor' => 'text-red-600',
                    'detail' =>
                        optional($log->old_etd)->format('d M H:i') . ' → ' . optional($log->new_etd)->format('d M H:i'),
                    'note' => $log->reason,
                    'priority' => 3,
                ],
            );
        }

        $timeline = $events->sortBy([fn($item) => -$item->priority, fn($item) => $item->ts])->values();

        // ── Milestones for rail ──────────────────────────────────────────
        $milestones = collect($v->milestones ?? [])
            ->sortBy(fn($m) => (int) str_replace('d', '', $m->code))
            ->values();
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 1 — Compact Operational Header                          --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="rounded border border-gray-100 border-l-4 {{ $headerBorder }} {{ $headerBg }} px-4 py-3 mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-base font-bold text-gray-900 truncate">
                        {{ $v->vessel?->name ?? '—' }}
                    </span>
                    <span class="text-xs font-mono text-gray-500 tabular-nums">
                        {{ $v->voyage_no }}
                    </span>
                    <span class="text-[10px] text-gray-400">
                        {{ $v->pol?->code ?? '—' }} → {{ $v->pod?->code ?? '—' }}
                    </span>
                    @if ($v->shippingLine?->code)
                        <span class="text-[10px] text-gray-400">· {{ $v->shippingLine->code }}</span>
                    @endif
                </div>

                <div class="mt-1.5 flex items-center gap-2 flex-wrap text-[11px] text-gray-500">
                    <span>ETD <span
                            class="font-medium text-gray-700">{{ optional($v->etd)->format('d M H:i') ?? '—' }}</span></span>
                    <span class="text-gray-300">›</span>
                    <span>ETA <span
                            class="font-medium text-gray-700">{{ optional($v->eta)->format('d M H:i') ?? '—' }}</span></span>
                    @if ($v->atd_at)
                        <span class="text-gray-300">·</span>
                        <span>ATD <span
                                class="font-medium {{ $v->otd_status === \App\Enums\SlaStatus::ONTIME ? 'text-green-700' : 'text-red-700' }}">{{ $v->atd_at->format('d M H:i') }}</span></span>
                    @endif
                    @if ($v->ata_at)
                        <span class="text-gray-300">·</span>
                        <span>ATA <span
                                class="font-medium {{ $v->ota_status === \App\Enums\SlaStatus::ONTIME ? 'text-green-700' : 'text-red-700' }}">{{ $v->ata_at->format('d M H:i') }}</span></span>
                    @endif
                    @if ($cargoPct !== null)
                        <span class="text-gray-300">·</span>
                        <span>Muatan <span
                                class="font-medium text-gray-700">{{ $v->cargo_actual }}/{{ $v->cargo_plan }}
                                TEU</span> <span class="text-gray-400">({{ $cargoPct }}%)</span></span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border {{ $statusBadge }}">
                    {{ strtoupper($status->value) }}
                </span>
                @if ($overdueDays)
                    <span
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700 border border-red-200">+{{ $overdueDays }}d
                        overdue</span>
                @elseif ($delayDays > 0)
                    <span
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 border border-orange-200">+{{ $delayDays }}d
                        delay</span>
                @endif
            </div>
        </div>

        @if (count($anomalies))
            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                @foreach ($anomalies as $a)
                    <span
                        class="text-[10px] font-semibold px-1.5 py-0.5 rounded border {{ $a->class }}">{{ $a->text }}</span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 2 — Operational Status Summary                          --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-2 flex-wrap mb-4">
        @foreach ($kpis as $kpi)
            <div class="flex items-center gap-1 px-2 py-1 rounded border {{ $kpi->bg }} {{ $kpi->border }}">
                <span class="text-[9px] font-semibold text-gray-500 uppercase">{{ $kpi->label }}</span>
                <span class="text-xs font-bold {{ $kpi->color }}">{{ $kpi->symbol }}</span>
            </div>
        @endforeach

        @if ($v->sla_status)
            <div class="flex items-center gap-1 ml-1">
                <span class="text-[10px] text-gray-400">SLA</span>
                <span
                    class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $v->sla_status === \App\Enums\SlaStatus::ONTIME ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                    {{ $v->sla_status->label() }}
                </span>
                @if ($v->actual_sailing_days && $v->planned_sailing_days)
                    <span class="text-[10px] text-gray-400">{{ $v->actual_sailing_days }}d /
                        {{ $v->planned_sailing_days }}d</span>
                @endif
            </div>
        @endif

        @if ($v->delay_root_cause && $v->delay_root_cause !== 'ONTIME')
            <span class="text-[10px] text-gray-400 ml-auto">
                Penyebab: <span class="font-medium text-gray-600">{{ $v->delay_root_cause_label }}</span>
            </span>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 3 — Operational Timeline                                --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">Timeline Operasional</span>
            <span class="text-[10px] text-gray-400">{{ $timeline->count() }} kejadian</span>
        </div>

        @if ($timeline->isNotEmpty())
            <div class="space-y-0">
                @foreach ($timeline as $item)
                    @php
                        $rowBg = $item->priority >= 3 ? 'bg-red-50/30' : '';
                        $rowBorder =
                            $item->priority >= 3 ? 'border-l-2 border-l-red-300' : 'border-l-2 border-l-transparent';
                    @endphp
                    <div class="flex items-center gap-2 py-1.5 px-2 {{ $rowBg }} {{ $rowBorder }}">
                        <div
                            class="w-5 h-5 rounded flex items-center justify-center text-[9px] font-bold flex-shrink-0
                            {{ match ($item->type) {
                                'readiness' => 'bg-blue-100 text-blue-700',
                                'plan' => 'bg-indigo-100 text-indigo-700',
                                'actual' => 'bg-gray-100 text-gray-700',
                                'milestone' => 'bg-amber-100 text-amber-700',
                                'delay' => 'bg-red-100 text-red-700',
                            } }}">
                            {{ $item->code }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] font-medium text-gray-800">{{ $item->label }}</span>
                                <span class="text-xs {{ $item->stateColor }}">{{ $item->state }}</span>
                            </div>
                            @if ($item->note)
                                <div class="text-[10px] text-gray-400 truncate">{{ $item->note }}</div>
                            @endif
                        </div>

                        <div class="text-[10px] text-gray-500 tabular-nums flex-shrink-0 text-right">
                            <div>{{ $item->detail }}</div>
                            @if ($item->date)
                                <div class="text-[9px] text-gray-400">{{ $item->date->format('d M H:i') }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-[11px] text-gray-400 italic py-3 px-2">Belum ada kejadian operasional.</div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 4 — Readiness Feed                                       --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @php
        $readinessItems = collect();
        foreach ($v->checkpoints ?? [] as $cp) {
            $readinessItems->push(
                (object) [
                    'ts' => $cp->scheduled_at?->timestamp ?? PHP_INT_MAX,
                    'code' => strtoupper($cp->code),
                    'kind' => 'CP',
                    'status' => $cp->is_completed ? 'Done' : ($cp->is_late ? 'Late' : 'Open'),
                    'statusColor' => $cp->is_completed
                        ? 'text-green-600'
                        : ($cp->is_late
                            ? 'text-red-600'
                            : 'text-gray-400'),
                    'detail' => $cp->checked_at
                        ? $cp->checked_at->format('d M H:i')
                        : optional($cp->scheduled_at)->format('d M H:i'),
                    'note' => $cp->note,
                ],
            );
        }
        foreach ($v->vesselChecks ?? [] as $vc) {
            $st = match ($vc->status?->value) {
                'on_schedule' => ['OK', 'text-green-600'],
                'potential_delay' => ['Risk', 'text-orange-600'],
                default => ['—', 'text-gray-400'],
            };
            $readinessItems->push(
                (object) [
                    'ts' => $vc->check_date?->startOfDay()->timestamp ?? PHP_INT_MAX,
                    'code' => strtoupper($vc->day_code),
                    'kind' => 'VC',
                    'status' => $st[0],
                    'statusColor' => $st[1],
                    'detail' => optional($vc->check_date)->format('d M'),
                    'note' => $vc->note,
                ],
            );
        }
        $readinessItems = $readinessItems->sortBy('ts')->values();
    @endphp

    @if ($readinessItems->isNotEmpty())
        <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">Kesiapan</span>
                <span class="text-[10px] text-gray-400">{{ $readinessItems->count() }} pemeriksaan</span>
            </div>

            <div class="space-y-0">
                @foreach ($readinessItems as $item)
                    <div
                        class="flex items-center gap-2 py-1 px-2 border-l-2 border-l-transparent hover:border-l-gray-200">
                        <span
                            class="text-[9px] px-1 rounded bg-gray-100 text-gray-500 font-medium">{{ $item->kind }}</span>
                        <span class="text-[11px] font-medium text-gray-700 w-8">{{ $item->code }}</span>
                        <span class="text-[11px] {{ $item->statusColor }} font-medium">{{ $item->status }}</span>
                        <span class="text-[10px] text-gray-400 ml-auto tabular-nums">{{ $item->detail }}</span>
                        @if ($item->note)
                            <span class="text-[9px] text-gray-400 truncate max-w-[120px]">{{ $item->note }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 5 — Delay Incident Log                                   --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if ($v->delayLogs && $v->delayLogs->isNotEmpty())
        <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">Log Insiden Delay</span>
                <span class="text-[10px] text-gray-400">{{ $v->delayLogs->count() }} kejadian</span>
            </div>

            <div class="space-y-0">
                @foreach ($v->delayLogs as $log)
                    <div class="flex items-center gap-2 py-1.5 px-2 border-l-2 border-l-red-300 bg-red-50/20">
                        <span
                            class="w-5 h-5 rounded bg-red-100 text-red-600 flex items-center justify-center text-[9px] font-bold flex-shrink-0">!</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-[11px] text-gray-800 font-medium truncate">
                                {{ $log->reason ?: 'Schedule change' }}</div>
                            <div class="text-[10px] text-gray-500 tabular-nums">
                                ETD {{ optional($log->old_etd)->format('d M H:i') ?? '—' }} →
                                {{ optional($log->new_etd)->format('d M H:i') ?? '—' }}
                            </div>
                        </div>
                        <div class="text-[9px] text-gray-400 flex-shrink-0 tabular-nums">
                            {{ $log->created_at?->format('d M H:i') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 6 — Milestone Rail                                       --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">Milestone</span>
            @php
                $totalM = $milestones->count();
                $completedM = $milestones->whereNotNull('actual_date')->count();
            @endphp
            <span class="text-[10px] text-gray-400">{{ $completedM }}/{{ $totalM }}</span>
        </div>

        @if ($milestones->isNotEmpty())
            <div class="flex gap-1 overflow-x-auto pb-1">
                @foreach ($milestones as $m)
                    @php
                        if ($m->actual_date) {
                            $mColor =
                                $m->status === 'ontime'
                                    ? 'bg-green-50 text-green-700 border-green-200'
                                    : 'bg-red-50 text-red-700 border-red-200';
                            $mIcon = $m->status === 'ontime' ? '✓' : '✗';
                        } elseif ($m->is_overdue) {
                            $mColor = 'bg-red-50 text-red-700 border-red-200';
                            $mIcon = '!';
                        } elseif ($m->is_due_today) {
                            $mColor = 'bg-orange-50 text-orange-700 border-orange-200';
                            $mIcon = '●';
                        } else {
                            $mColor = 'bg-gray-50 text-gray-400 border-gray-100';
                            $mIcon = '—';
                        }
                    @endphp
                    <div class="flex-1 min-w-[56px] rounded border {{ $mColor }} px-1.5 py-1.5 text-center">
                        <div class="text-[9px] uppercase font-semibold tracking-wide">{{ strtoupper($m->code) }}</div>
                        <div class="text-sm font-bold mt-0.5 leading-none">{{ $mIcon }}</div>
                        <div class="text-[8px] text-gray-400 mt-0.5 tabular-nums">
                            {{ optional($m->milestone_date)->format('d M') }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-[11px] text-gray-400 italic py-2">Belum ada milestone. Isi ATD untuk generate D+
                milestone.</div>
        @endif
    </div>
</x-filament-panels::page>

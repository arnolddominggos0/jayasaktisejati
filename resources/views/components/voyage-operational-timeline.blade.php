@php
    use App\Supports\OperationalUi;
    use App\Enums\SlaStatus;

    $events = collect();

    foreach ($voyage->checkpoints ?? [] as $cp) {
        $events->push((object)[
            'ts' => $cp->scheduled_at?->timestamp ?? PHP_INT_MAX,
            'date' => $cp->scheduled_at,
            'type' => 'readiness',
            'code' => strtoupper($cp->code),
            'label' => strtoupper($cp->code),
            'state' => $cp->is_completed ? '✓' : ($cp->is_late ? '!' : '•'),
            'stateColor' => $cp->is_completed ? 'text-green-600' : ($cp->is_late ? 'text-red-600' : 'text-gray-400'),
            'detail' => $cp->checked_at ? $cp->checked_at->format('d M H:i') : optional($cp->scheduled_at)->format('d M H:i'),
            'note' => $cp->note,
            'priority' => $cp->is_late ? 3 : ($cp->is_completed ? 1 : 2),
        ]);
    }

@foreach ($voyage->vesselChecks ?? [] as $vc)
        @php
        $vcTimeline = OperationalUi::vesselCheckTimelineState($vc);
        @endphp
        $events->push((object)[
            'ts' => $vc->check_date?->startOfDay()->timestamp ?? PHP_INT_MAX,
            'date' => $vc->check_date?->startOfDay(),
            'type' => 'readiness',
            'code' => strtoupper($vc->day_code),
            'label' => strtoupper($vc->day_code),
            'state' => $vcTimeline['state'],
            'stateColor' => $vcTimeline['color'],
            'detail' => optional($vc->etd_plan)->format('d M H:i'),
            'note' => $vc->note,
            'priority' => $vcTimeline['priority'],
        ]);
    @endforeach

    if ($voyage->etb) {
        $events->push((object)[
            'ts' => $voyage->etb->timestamp,
            'date' => $voyage->etb,
            'type' => 'plan',
            'code' => 'ETB',
            'label' => 'ETB',
            'state' => 'P',
            'stateColor' => 'text-indigo-500',
            'detail' => $voyage->etb->format('d M H:i'),
            'priority' => 1,
        ]);
    }

    if ($voyage->atb_at) {
        $otbDisplay = OperationalUi::slaStatusDisplay($voyage->otb_status);
        $events->push((object)[
            'ts' => $voyage->atb_at->timestamp,
            'date' => $voyage->atb_at,
            'type' => 'actual',
            'code' => 'ATB',
            'label' => 'ATB',
            'state' => $otbDisplay['icon'],
            'stateColor' => $otbDisplay['color'],
            'detail' => $voyage->atb_at->format('d M H:i'),
            'priority' => $otbDisplay['priority'],
        ]);
    }

    if ($voyage->closing_at) {
        $events->push((object)[
            'ts' => $voyage->closing_at->timestamp,
            'date' => $voyage->closing_at,
            'type' => 'actual',
            'code' => 'CL',
            'label' => 'Closing',
            'state' => '✓',
            'stateColor' => 'text-gray-500',
            'detail' => $voyage->closing_at->format('d M H:i'),
            'priority' => 1,
        ]);
    }

    if ($voyage->atd_at) {
        $otdDisplay = OperationalUi::slaStatusDisplay($voyage->otd_status);
        $events->push((object)[
            'ts' => $voyage->atd_at->timestamp,
            'date' => $voyage->atd_at,
            'type' => 'actual',
            'code' => 'ATD',
            'label' => 'ATD',
            'state' => $otdDisplay['icon'],
            'stateColor' => $otdDisplay['color'],
            'detail' => $voyage->atd_at->format('d M H:i'),
            'priority' => $otdDisplay['priority'],
        ]);
    }

    foreach ($voyage->milestones ?? [] as $m) {
        if ($m->actual_date) {
            $mState = OperationalUi::milestoneIndicatorState($m);
            $mIcon = OperationalUi::milestoneChip($m)['icon'];
            $events->push((object)[
                'ts' => $m->actual_date->timestamp,
                'date' => $m->actual_date,
                'type' => 'milestone',
                'code' => strtoupper($m->code),
                'label' => strtoupper($m->code),
                'state' => $mIcon,
                'stateColor' => match ($mState) {
                    'success' => 'text-green-600',
                    'danger'  => 'text-red-600',
                    default   => 'text-gray-400',
                },
                'detail' => $m->actual_date->format('d M') . ($m->speed_knots ? " · {$m->speed_knots}kn" : ''),
                'note' => $m->note,
                'priority' => $mState === 'success' ? 1 : 3,
            ]);
        } else {
            $mState = OperationalUi::milestoneIndicatorState($m);
            $prio = $mState === 'danger' ? 3 : ($mState === 'warning' ? 3 : 2);
            $events->push((object)[
                'ts' => optional($m->milestone_date)->timestamp ?? PHP_INT_MAX,
                'date' => $m->milestone_date,
                'type' => 'milestone',
                'code' => strtoupper($m->code),
                'label' => strtoupper($m->code),
                'state' => match ($mState) {
                    'danger'  => '!',
                    'warning' => '●',
                    default   => '•',
                },
                'stateColor' => match ($mState) {
                    'danger'  => 'text-red-600',
                    'warning' => 'text-orange-500',
                    default   => 'text-gray-300',
                },
                'detail' => optional($m->milestone_date)->format('d M') . ' (plan)',
                'note' => $m->note,
                'priority' => $prio,
            ]);
        }
    }

    if ($voyage->ata_at) {
        $otaDisplay = OperationalUi::slaStatusDisplay($voyage->ota_status);
        $events->push((object)[
            'ts' => $voyage->ata_at->timestamp,
            'date' => $voyage->ata_at,
            'type' => 'actual',
            'code' => 'ATA',
            'label' => 'ATA',
            'state' => $otaDisplay['icon'],
            'stateColor' => $otaDisplay['color'],
            'detail' => $voyage->ata_at->format('d M H:i'),
            'priority' => $otaDisplay['priority'],
        ]);
    }

    foreach ($voyage->delayLogs ?? [] as $log) {
        $events->push((object)[
            'ts' => $log->created_at->timestamp,
            'date' => $log->created_at,
            'type' => 'delay',
            'code' => '!',
            'label' => 'Delay',
            'state' => '!',
            'stateColor' => 'text-red-600',
            'detail' => optional($log->old_etd)->format('d M H:i') . ' → ' . optional($log->new_etd)->format('d M H:i'),
            'note' => $log->reason,
            'priority' => 3,
        ]);
    }

    $timeline = $events
        ->sortBy([
            fn ($item) => -$item->priority,
            fn ($item) => $item->ts,
        ])
        ->values();
@endphp

@if ($timeline->isNotEmpty())
    <div class="space-y-0">
        @foreach ($timeline as $item)
            @php
                $rowBg = $item->priority >= 3 ? 'bg-red-50/30' : '';
                $rowBorder = $item->priority >= 3 ? 'border-l-2 border-l-red-300' : 'border-l-2 border-l-transparent';
            @endphp
            <div class="flex items-center gap-2 py-1.5 px-2 {{ $rowBg }} {{ $rowBorder }}">
                <div class="w-5 h-5 rounded flex items-center justify-center text-[9px] font-bold flex-shrink-0
                    {{ match($item->type) {
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

@php
    use Illuminate\Support\Carbon;

    $events = collect();

    // 1. Readiness: Checkpoints (D-2, D-1)
    foreach ($voyage->checkpoints ?? [] as $cp) {
        $events->push((object)[
            'type' => 'readiness',
            'icon' => 'CP',
            'icon_color' => 'bg-blue-400',
            'label' => strtoupper($cp->code),
            'date' => $cp->scheduled_at,
            'status' => $cp->is_completed
                ? (object)['text' => 'Done', 'color' => 'text-green-600', 'bg' => 'bg-green-50 border-green-200']
                : ($cp->is_late
                    ? (object)['text' => 'Late', 'color' => 'text-red-600', 'bg' => 'bg-red-50 border-red-200']
                    : (object)['text' => 'Pending', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50 border-gray-200']
                ),
            'detail' => $cp->checked_at
                ? 'Checked: ' . $cp->checked_at->format('d M H:i')
                : 'Scheduled: ' . optional($cp->scheduled_at)->format('d M H:i'),
            'note' => $cp->note,
        ]);
    }

    // 2. Readiness: Vessel Checks (H-3, H-2, H-1)
    foreach ($voyage->vesselChecks ?? [] as $vc) {
        $statusLabel = match ($vc->status?->value) {
            'on_schedule' => 'OK',
            'potential_delay' => 'Risk',
            default => '-',
        };
        $statusColor = match ($vc->status?->value) {
            'on_schedule' => 'text-green-600',
            'potential_delay' => 'text-orange-600',
            default => 'text-gray-500',
        };
        $statusBg = match ($vc->status?->value) {
            'on_schedule' => 'bg-green-50 border-green-200',
            'potential_delay' => 'bg-orange-50 border-orange-200',
            default => 'bg-gray-50 border-gray-200',
        };

        $events->push((object)[
            'type' => 'readiness',
            'icon' => 'VC',
            'icon_color' => 'bg-purple-400',
            'label' => strtoupper($vc->day_code),
            'date' => $vc->check_date?->startOfDay(),
            'status' => (object)[
                'text' => $statusLabel,
                'color' => $statusColor,
                'bg' => $statusBg,
            ],
            'detail' => 'ETD: ' . optional($vc->etd_plan)->format('d M H:i'),
            'note' => $vc->note,
        ]);
    }

    // 3. Actual Operation: ETB
    if ($voyage->etb) {
        $events->push((object)[
            'type' => 'plan',
            'icon' => 'EB',
            'icon_color' => 'bg-indigo-400',
            'label' => 'ETB (Plan)',
            'date' => $voyage->etb,
            'status' => (object)[
                'text' => 'Plan',
                'color' => 'text-indigo-600',
                'bg' => 'bg-indigo-50 border-indigo-200',
            ],
            'detail' => $voyage->etb->format('d M H:i'),
            'note' => null,
        ]);
    }

    // 4. Actual Operation: ATB
    if ($voyage->atb_at) {
        $atbStatus = $voyage->otb_status;
        $events->push((object)[
            'type' => 'actual',
            'icon' => 'AB',
            'icon_color' => 'bg-green-500',
            'label' => 'ATB (Actual)',
            'date' => $voyage->atb_at,
            'status' => (object)[
                'text' => $atbStatus?->label() ?? 'OK',
                'color' => $atbStatus === \App\Enums\SlaStatus::ONTIME ? 'text-green-600' : ($atbStatus === \App\Enums\SlaStatus::LATE ? 'text-red-600' : 'text-gray-500'),
                'bg' => $atbStatus === \App\Enums\SlaStatus::ONTIME ? 'bg-green-50 border-green-200' : ($atbStatus === \App\Enums\SlaStatus::LATE ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'),
            ],
            'detail' => $voyage->atb_at->format('d M H:i'),
            'note' => null,
        ]);
    }

    // 5. Actual Operation: Closing
    if ($voyage->closing_at) {
        $events->push((object)[
            'type' => 'actual',
            'icon' => 'CL',
            'icon_color' => 'bg-gray-500',
            'label' => 'Closing',
            'date' => $voyage->closing_at,
            'status' => (object)[
                'text' => 'Done',
                'color' => 'text-gray-600',
                'bg' => 'bg-gray-50 border-gray-200',
            ],
            'detail' => $voyage->closing_at->format('d M H:i'),
            'note' => null,
        ]);
    }

    // 6. Actual Operation: ATD
    if ($voyage->atd_at) {
        $otdStatus = $voyage->otd_status;
        $events->push((object)[
            'type' => 'actual',
            'icon' => 'AD',
            'icon_color' => 'bg-blue-500',
            'label' => 'ATD (Actual)',
            'date' => $voyage->atd_at,
            'status' => (object)[
                'text' => $otdStatus?->label() ?? 'OK',
                'color' => $otdStatus === \App\Enums\SlaStatus::ONTIME ? 'text-green-600' : ($otdStatus === \App\Enums\SlaStatus::LATE ? 'text-red-600' : 'text-gray-500'),
                'bg' => $otdStatus === \App\Enums\SlaStatus::ONTIME ? 'bg-green-50 border-green-200' : ($otdStatus === \App\Enums\SlaStatus::LATE ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'),
            ],
            'detail' => $voyage->atd_at->format('d M H:i'),
            'note' => null,
        ]);
    }

    // 7. Milestones (D+2 s.d. D+12)
    foreach ($voyage->milestones ?? [] as $m) {
        $mStatus = (object)[
            'text' => 'Pending',
            'color' => 'text-gray-500',
            'bg' => 'bg-gray-50 border-gray-200',
        ];

        if ($m->actual_date) {
            $mStatus = (object)[
                'text' => $m->status === 'ontime' ? 'OK' : 'Late',
                'color' => $m->status === 'ontime' ? 'text-green-600' : 'text-red-600',
                'bg' => $m->status === 'ontime' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200',
            ];
        } elseif ($m->is_overdue) {
            $mStatus = (object)[
                'text' => 'Overdue',
                'color' => 'text-red-600',
                'bg' => 'bg-red-50 border-red-200',
            ];
        } elseif ($m->is_due_today) {
            $mStatus = (object)[
                'text' => 'Today',
                'color' => 'text-orange-600',
                'bg' => 'bg-orange-50 border-orange-200',
            ];
        }

        $events->push((object)[
            'type' => 'milestone',
            'icon' => strtoupper($m->code),
            'icon_color' => 'bg-amber-400',
            'label' => strtoupper($m->code) . ' Milestone',
            'date' => $m->actual_date ?? $m->milestone_date,
            'status' => $mStatus,
            'detail' => $m->actual_date
                ? 'Actual: ' . $m->actual_date->format('d M')
                : 'Target: ' . optional($m->milestone_date)->format('d M'),
            'note' => $m->note ? $m->note : ($m->speed_knots ? 'Speed: ' . $m->speed_knots . 'kn' : null),
        ]);
    }

    // 8. Actual Operation: ATA
    if ($voyage->ata_at) {
        $otaStatus = $voyage->ota_status;
        $events->push((object)[
            'type' => 'actual',
            'icon' => 'AA',
            'icon_color' => 'bg-emerald-500',
            'label' => 'ATA (Actual)',
            'date' => $voyage->ata_at,
            'status' => (object)[
                'text' => $otaStatus?->label() ?? 'OK',
                'color' => $otaStatus === \App\Enums\SlaStatus::ONTIME ? 'text-green-600' : ($otaStatus === \App\Enums\SlaStatus::LATE ? 'text-red-600' : 'text-gray-500'),
                'bg' => $otaStatus === \App\Enums\SlaStatus::ONTIME ? 'bg-green-50 border-green-200' : ($otaStatus === \App\Enums\SlaStatus::LATE ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'),
            ],
            'detail' => $voyage->ata_at->format('d M H:i'),
            'note' => null,
        ]);
    }

    // 9. Delay Events
    foreach ($voyage->delayLogs ?? [] as $log) {
        $events->push((object)[
            'type' => 'delay',
            'icon' => '!',
            'icon_color' => 'bg-red-500',
            'label' => 'Delay',
            'date' => $log->created_at,
            'status' => (object)[
                'text' => 'Changed',
                'color' => 'text-red-600',
                'bg' => 'bg-red-50 border-red-200',
            ],
            'detail' => 'ETD: ' . optional($log->old_etd)->format('d M H:i') . ' → ' . optional($log->new_etd)->format('d M H:i'),
            'note' => $log->reason ? 'Reason: ' . $log->reason : null,
        ]);
    }

    // Sort by date
    $timeline = $events
        ->sortBy(fn($item) => $item->date?->timestamp ?? PHP_INT_MAX)
        ->values();
@endphp

@if ($timeline->isNotEmpty())
    <div class="space-y-1">
        @foreach ($timeline as $item)
            <div class="flex items-center gap-2 py-1 border-b border-gray-100/40 last:border-0">
                <div class="w-7 h-7 rounded-full {{ $item->icon_color }} text-white flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                    {{ $item->icon }}
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-semibold text-gray-800 truncate">{{ $item->label }}</span>
                        <span class="text-[10px] font-medium {{ $item->status->color }} px-1.5 py-0.5 rounded bg-white/60 flex-shrink-0">
                            {{ $item->status->text }}
                        </span>
                    </div>
                    <div class="text-[10px] text-gray-500 truncate">{{ $item->detail }}</div>
                    @if ($item->note)
                        <div class="text-[10px] text-gray-400 truncate italic">{{ $item->note }}</div>
                    @endif
                </div>

                @if ($item->date)
                    <div class="text-[9px] text-gray-400 flex-shrink-0">
                        {{ $item->date->format('d M H:i') }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="text-xs text-gray-400 italic py-3">
        No operational data yet.
    </div>
@endif

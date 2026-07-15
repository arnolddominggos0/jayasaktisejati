{{--
    Operational Pipeline — Sprint WX1/WX2, composition finalized WX4.

    The workspace's primary axis is the operational LIFECYCLE, not
    severity and not stacked widgets. Every voyage lives in exactly ONE
    of four sequential zones (Persiapan → Keberangkatan → Pelayaran →
    Kedatangan), determined purely from already-loaded timestamps. A
    voyage is never rendered twice; it relocates from zone to zone as its
    own actual-time fields fill in over its life.

    This partial is presentation only:
      • Zone assignment ($pipelineGroups) is computed in the parent page
        from $rows via a match() over atb/atd/ata/closing — the same
        "safe path" independent derivation the old Fleet Board used
        (never TaskClassifier, never a new query).
      • Severity (WX2) is a SECONDARY visual layer: it only re-orders
        voyages inside a zone and tints the accent/action — it is not the
        organizing structure.
      • Every recording entry point is preserved: the zone's primary
        button covers the most-likely next action; all other actions
        (ATB, ATA, Delay, Readiness, Cargo, Milestone) remain reachable
        through the unchanged Drawer (vessel name → openDrawer).

    WX4 additions (composition only, no logic touched):
      • One unified empty state when the whole period is empty, instead
        of four repeated "tidak ada" messages (WX3.10 finding).
      • Zone headers lost their border-bottom — they are chapter markers
        now (title + count), not section dividers.
      • A quiet "↓" connector between zones reinforces the single flow
        (Persiapan → Keberangkatan → Pelayaran → Kedatangan) instead of
        four independent-feeling blocks.
      • Each row carries id="pv-{id}" so the header's Decision Pointer
        (WX4 §6, in the parent page) can anchor-scroll straight to it.

    Glyph grammar (monochrome; VR1's no-green rule holds — green is never
    "done"): ✓ done (gray) · ○ pending (gray) · ! late (red, severity) ·
    — not-applicable / no data (gray).
--}}
@php
    $zones = [
        1 => ['title' => 'Persiapan Keberangkatan', 'empty' => 'Tidak ada kapal yang perlu dipersiapkan.'],
        2 => ['title' => 'Keberangkatan',           'empty' => 'Tidak ada keberangkatan.'],
        3 => ['title' => 'Pelayaran',               'empty' => 'Tidak ada kapal dalam pelayaran.'],
        4 => ['title' => 'Kedatangan',              'empty' => 'Tidak ada kedatangan.'],
    ];

    $glyphOf = fn ($state) => match ($state) {
        'done'    => ['✓', 'text-gray-600'],
        'pending' => ['○', 'text-gray-400'],
        'late'    => ['!', 'text-red-600 font-bold'],
        default   => ['—', 'text-gray-300'],
    };

    $msState = function ($m) {
        if (! $m) return 'none';
        if ($m->actual_date) return 'done';
        if ($m->is_overdue) return 'late';
        return 'pending';
    };

    // Severity score — same facts the Fleet Board derived independently
    // (overdue / eta-overdue = critical, risk / milestone-overdue = watch).
    // Used only to sort within a zone and tint the accent; NOT structure.
    $sevScore = fn ($v) => match (true) {
        $v->overdue_days > 0 || $v->eta_overdue => 3,
        $v->sailing_risk || $v->milestones->where('is_overdue', true)->count() > 0 => 2,
        default => 1,
    };
@endphp

@if ($pipelineGroups->isEmpty())
    {{-- WX4 §5 — one workspace-level message, not four repeated ones. --}}
    <div class="py-6 text-center">
        <p class="text-[12px] font-medium text-gray-500">
            Tidak ada aktivitas operasional untuk {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}.
        </p>
        <p class="text-[11px] text-gray-400 mt-1">
            Tidak ada kapal yang terjadwal untuk persiapan, keberangkatan, pelayaran, atau kedatangan saat ini.
        </p>
    </div>
@else
    <div>
        @foreach ($zones as $zoneNum => $zoneMeta)
            @php
                $group = $pipelineGroups->get($zoneNum);
                $voyages = $group ? $group->sortByDesc($sevScore)->values() : collect();
            @endphp

            <section wire:key="zone-{{ $zoneNum }}" class="{{ $zoneNum > 1 ? 'mt-4' : '' }}">
                {{-- Chapter marker — title + count only, no border, no
                     subtitle, no copy (WX4 §4). --}}
                <div class="flex items-baseline gap-2 mb-1.5">
                    <h2 class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $zoneMeta['title'] }}</h2>
                    @if ($voyages->count())
                        <span class="text-[10px] text-gray-300 tabular-nums">{{ $voyages->count() }}</span>
                    @endif
                </div>

                @if ($voyages->isEmpty())
                    <p class="text-[11px] text-gray-400 py-1">{{ $zoneMeta['empty'] }}</p>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach ($voyages as $v)
                            @php
                                $sev = $sevScore($v);
                                $accent = match ($sev) {
                                    3       => 'border-l-red-500',
                                    2       => 'border-l-amber-400',
                                    default => 'border-l-transparent',
                                };

                                // Zone-relevant date. Its color IS the delay signal
                                // (red when the window has passed and the actual is
                                // not yet recorded) — no paragraph, no "X hari" text;
                                // exact figures live in the Drawer (WX2 row rules).
                                if ($zoneNum <= 2) {
                                    $date = $v->etd; $dateLabel = 'ETD';
                                    $dateOverdue = $date && $date->isPast() && ! $v->atd_at;
                                } else {
                                    $date = $v->eta; $dateLabel = 'ETA';
                                    $dateOverdue = $date && $date->isPast() && ! $v->ata_at;
                                }

                                // D-2 / D-1 folded in from the retired Carrier
                                // Readiness table — per-voyage, from already-loaded
                                // vesselChecks (H-2/H-1), labelled D-2/D-1 per WX2.
                                $vcCol = collect($v->vesselChecks ?? []);
                                $checkState = function ($code) use ($vcCol) {
                                    $c = $vcCol->first(fn ($x) => $x->day_code && strtoupper($x->day_code) === $code);
                                    if (! $c) return 'none';
                                    return match ($c->status?->value) {
                                        'ok'   => 'done',
                                        'late' => 'late',
                                        default => 'pending',
                                    };
                                };

                                $mMap = collect($v->milestones ?? [])->keyBy(fn ($m) => strtolower($m->code));

                                // Checklist items per zone: [label, state, milestoneId?].
                                // Each zone shows ONLY its own stage's items (WX2).
                                $items = match ($zoneNum) {
                                    1 => [
                                        ['Cargo Plan', $v->cargo_plan !== null ? 'done' : 'pending', null],
                                        ['Assign',     $v->vessel ? 'done' : 'pending', null],
                                        ['D-2',        $checkState('H-2'), null],
                                        ['D-1',        $checkState('H-1'), null],
                                    ],
                                    2 => [
                                        ['ATB', $v->atb_at ? 'done' : 'pending', null],
                                        ['ATD', $v->atd_at ? 'done' : ($dateOverdue ? 'late' : 'pending'), null],
                                    ],
                                    3 => [
                                        ['D+2', $msState($mMap->get('d2')), $mMap->get('d2')?->id],
                                        ['D+4', $msState($mMap->get('d4')), $mMap->get('d4')?->id],
                                        ['D+6', $msState($mMap->get('d6')), $mMap->get('d6')?->id],
                                    ],
                                    4 => [
                                        ['ATA',     $v->ata_at ? 'done' : ($dateOverdue ? 'late' : 'pending'), null],
                                        ['Closing', $v->closing_at ? 'done' : 'pending', null],
                                    ],
                                };

                                // One primary action = the most-likely next event to
                                // record in this zone. Everything else via the Drawer.
                                $primary = match ($zoneNum) {
                                    1 => ['label' => 'Kesiapan', 'modal' => 'readiness'],
                                    2 => ['label' => 'ATD',      'modal' => 'atd'],
                                    3 => ['label' => 'ATA',      'modal' => 'ata'],
                                    4 => $v->closing_at ? null : ['label' => 'Closing', 'modal' => 'closing'],
                                };
                            @endphp

                            {{-- One operational worksheet row. id= is the anchor
                                 target for the header's Decision Pointer (WX4 §6).
                                 Verification settle (VR1 item 6) reused unchanged. --}}
                            <div id="pv-{{ $v->id }}" wire:key="pv-{{ $v->id }}"
                                @if ($recentlyUpdatedVoyageId === $v->id)
                                    x-data
                                    x-init="$el.classList.add('ring-1', 'ring-inset', 'ring-gray-400');
                                            setTimeout(() => $el.classList.remove('ring-1', 'ring-inset', 'ring-gray-400'), 1600)"
                                @endif
                                class="scroll-mt-4 border-l-[3px] {{ $accent }} flex items-center gap-4 px-3 py-2.5 hover:bg-gray-50/40 transition">

                                {{-- IDENTITY (→ Drawer, where all detail + all actions live) --}}
                                <button wire:click="openDrawer({{ $v->id }})" class="w-44 shrink-0 text-left">
                                    <span class="block text-[13px] font-bold text-gray-900 hover:text-blue-700 transition leading-tight">{{ $v->vessel?->name }}</span>
                                    <span class="block text-[10px] text-gray-400 leading-tight">{{ $v->shippingLine?->name ?? '—' }} · V.{{ $v->voyage_no }}</span>
                                </button>

                                {{-- ZONE-RELEVANT DATE (red = window passed, actual not recorded) --}}
                                <div class="w-20 shrink-0 text-[10px] tabular-nums {{ $dateOverdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                    {{ $dateLabel }} {{ $date?->format('d M') ?? '—' }}
                                </div>

                                {{-- CHECKLIST — the row's status, no paragraphs --}}
                                <div class="flex items-center gap-x-5 gap-y-1 flex-wrap flex-1">
                                    @foreach ($items as [$label, $state, $msId])
                                        @php [$g, $gc] = $glyphOf($state); @endphp
                                        @if ($msId)
                                            <button wire:click="showMilestone({{ $msId }})"
                                                class="inline-flex items-center gap-1 text-[10px] text-gray-500 hover:text-blue-700 transition">
                                                <span class="{{ $gc }} w-3 text-center font-bold">{{ $g }}</span>{{ $label }}
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] text-gray-500">
                                                <span class="{{ $gc }} w-3 text-center font-bold">{{ $g }}</span>{{ $label }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>

                                {{-- PRIMARY ACTION (severity tints it; critical = filled red) --}}
                                <div class="shrink-0 w-20 text-right">
                                    @if ($primary)
                                        <button wire:click="openOpModal({{ $v->id }}, '{{ $primary['modal'] }}')"
                                            class="px-2.5 py-1 rounded border text-[10px] font-semibold transition
                                                {{ $sev === 3 ? 'border-red-300 bg-red-600 text-white hover:bg-red-700' : 'border-gray-200 text-gray-600 hover:border-gray-400' }}">
                                            {{ $primary['label'] }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- WX4 §1/§8 — quiet flow connector between zones. Not a
                 divider (no line, no border): a single muted glyph that
                 reads as "leads into", reinforcing the pipeline direction
                 without adding visual weight or a section boundary. --}}
            @if (! $loop->last)
                <div class="flex justify-center text-gray-200 text-[10px] leading-none my-0.5 select-none" aria-hidden="true">↓</div>
            @endif
        @endforeach
    </div>
@endif

@php
    use App\Enums\VoyageOperationalStatus;

    $dateFmt = fn($dt) => $dt ? $dt->format('d M') : '—';

    // Priority sorting: DELAYED → ETA overdue → ETA risk → SAILING → COMPLETED → SCHEDULED
    // UNCHANGED since Sprint B2/B3 — Sprint UI1 touches presentation only.
    $sorted = $rows->sortByDesc(function ($v) {
        return match (true) {
            $v->operational_status_enum === VoyageOperationalStatus::DELAYED => 100,
            $v->eta_overdue => 90,
            $v->sailing_risk => 80,
            $v->operational_status_enum === VoyageOperationalStatus::SAILING => 70,
            $v->checkpoints->contains(fn($cp) => !$cp->is_completed && $cp->scheduled_at?->isPast()) => 60,
            $v->vesselChecks->contains(fn($vc) => $vc->status?->value === 'late') => 50,
            $v->operational_status_enum === VoyageOperationalStatus::COMPLETED => 30,
            $v->operational_status_enum === VoyageOperationalStatus::SCHEDULED => 20,
            default => 10,
        };
    })->values();
@endphp

{{-- Sprint WD2/WD3/UI1 Phase 4-5: the Matrix is now "Papan Operasional"
     (Fleet Board) — the operational center, not a deliberately-reached
     Reference table. Visual weight lowered relative to Fleet Status/
     Operational Brief above it (VD1 Conflict 1 resolution: this heading
     is no longer "the strongest on the page"). --}}
<h2 class="text-[13px] font-semibold text-gray-700 tracking-tight mb-3">Papan Operasional</h2>

@if ($sorted->isEmpty())
    <div class="bg-white border rounded-lg p-6 text-center text-xs text-gray-500">
        Tidak ada voyage aktif untuk periode {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}.
    </div>
@else
    <div class="bg-white border border-gray-200/40 rounded-lg overflow-hidden divide-y divide-gray-100/60">
        @foreach ($sorted as $index => $v)
            @php
                // ── Every computation below is byte-identical to the
                //    pre-UI1 implementation (Sprint B2/B3/B4). Sprint UI1
                //    only changes how these already-derived facts are
                //    grouped into markup (WD3's "safe path": Fleet Board
                //    derives its own condition independently, never via
                //    TaskClassifier/getBrief() — D3 §9 / ES1 §3 boundary). ──
                $cpMap = collect($v->checkpoints ?? [])->keyBy(fn($cp) => strtolower($cp->code));
                $d1 = $cpMap->get('eta_d1');

                $h1 = collect($v->vesselChecks ?? [])
                    ->sortByDesc('check_date')
                    ->first(fn($vc) => $vc->day_code && str_starts_with(strtolower($vc->day_code), 'h'));

                $mMap = collect($v->milestones ?? [])->keyBy(fn($m) => strtolower($m->code));
                $m2 = $mMap->get('d2');
                $m4 = $mMap->get('d4');
                $m6 = $mMap->get('d6');

                $criticalIssues = [];
                $secondaryIssues = [];

                if ($v->overdue_days) $criticalIssues[] = 'Terlambat ' . $v->overdue_days . ' Hari';
                if ($v->eta_overdue) $criticalIssues[] = 'Belum Tiba';
                if ($v->sailing_risk) $secondaryIssues[] = 'Risiko Telat';
                if ($v->milestones->where('is_overdue', true)->count()) $secondaryIssues[] = 'Update Terlambat';
                if ($d1 && !$d1->is_completed && $d1->scheduled_at?->isPast()) $secondaryIssues[] = 'D-1 Terlambat';
                if ($h1 && $h1->status?->value === 'late') $secondaryIssues[] = 'H-1 Terlambat';

                $hasIssues = count($criticalIssues) > 0 || count($secondaryIssues) > 0;

                $rowBorder = match (true) {
                    $v->operational_status_enum === VoyageOperationalStatus::DELAYED => 'border-l-red-500',
                    $v->eta_overdue => 'border-l-red-500',
                    $v->sailing_risk => 'border-l-orange-400',
                    $hasIssues => 'border-l-amber-400',
                    default => 'border-l-transparent',
                };

                $rowBg = match (true) {
                    $v->operational_status_enum === VoyageOperationalStatus::DELAYED => 'bg-red-50/30',
                    $v->eta_overdue => 'bg-red-50/20',
                    $v->sailing_risk => 'bg-orange-50/15',
                    $hasIssues => 'bg-amber-50/10',
                    default => '',
                };

                $hasMilestones = $v->operational_status_enum === VoyageOperationalStatus::SAILING && $v->milestones->count() > 0;
                $firstMilestone = $hasMilestones ? $v->milestones->first() : null;

                // Current Position (WD3 §4) — same $statusBadge vocabulary
                // (Berlayar/Selesai/Terjadwal, Sprint B4) plus the DELAYED
                // case, which previously hid the badge entirely — now it
                // becomes the Position label itself, since "where is this
                // voyage" must always answer something.
                // VI Sprint / VR1 WARNING 1 — Position colors unified to the
                // reserve policy (VM2 §0.4): only DELAYED stays red (severity).
                // Berlayar/Selesai/Terjadwal are healthy states → quiet
                // graphite, differentiated by weight, never by blue/green
                // decoration. "Healthy = quiet" (VP1 Phase 9).
                $position = match (true) {
                    $v->operational_status_enum === VoyageOperationalStatus::DELAYED => ['label' => 'Terlambat Berangkat', 'class' => 'text-red-600 border-red-200/50 bg-red-50/30'],
                    $v->operational_status_enum === VoyageOperationalStatus::SAILING => ['label' => 'Berlayar', 'class' => 'text-gray-700 border-gray-200/60 bg-gray-50/40'],
                    $v->operational_status_enum === VoyageOperationalStatus::COMPLETED => ['label' => 'Selesai', 'class' => 'text-gray-500 border-gray-200/50 bg-gray-50/30'],
                    default => ['label' => 'Terjadwal', 'class' => 'text-gray-400 border-gray-200/50 bg-gray-50/20'],
                };

                $causeLabel = $v->manual_delay_reason?->label();

                // ── VP3 TEMPORAL REGISTER (anticipation layer) ──────────────
                // Pure Blade derivation from already-loaded etd/eta vs now().
                // Never TaskClassifier, never a new query (VP3 Phase 15's
                // explicit boundary — the "safe path", same as $criticalIssues
                // above). Shown ONLY for voyages with no active severity issue:
                // severity always outranks temporal weight (VP3 Phase 3/7), so
                // a flagged voyage never carries an anticipation marker too.
                // Non-alarm, subordinate: amber-for-today (imminent), muted
                // gray-for-tomorrow (soon). Distant future stays silent (null).
                $temporal = null;
                if (! $hasIssues) {
                    if ($v->etd?->isSameDay(now()) || $v->eta?->isSameDay(now())) {
                        $temporal = ['label' => 'Jadwal hari ini', 'class' => 'text-amber-600 font-medium'];
                    } elseif ($v->etd?->isSameDay(now()->addDay()) || $v->eta?->isSameDay(now()->addDay())) {
                        $temporal = ['label' => 'Jadwal besok', 'class' => 'text-gray-500'];
                    }
                }

                // ── VM2 §0.6 MONOCHROME PROGRESS RAIL — 4-glyph grammar ─────
                // ▰ completed (gray-600) · ▱ current/you-are-here (gray-900)
                // · future (gray-300) · ◌ missing-expected/late (red-600).
                // Absence-as-signal (VP2 Q5): a stage that should have happened
                // and didn't becomes the one loud mark. Same underlying facts
                // as before (atb/atd/ata/closing timestamps, milestone
                // actual_date/is_overdue, OTD/OTA late) — only the visual
                // language changes from green-✓ pills to monochrome glyphs.
                $stageDefs = [
                    ['key' => 'ATB',     'done' => (bool) $v->atb_at,                        'late' => false],
                    ['key' => 'ATD',     'done' => (bool) $v->atd_at,                        'late' => $v->otd_status?->value === 'late'],
                    ['key' => 'D+2',     'done' => (bool) ($m2 && $m2->actual_date),         'late' => (bool) ($m2 && $m2->is_overdue)],
                    ['key' => 'D+4',     'done' => (bool) ($m4 && $m4->actual_date),         'late' => (bool) ($m4 && $m4->is_overdue)],
                    ['key' => 'D+6',     'done' => (bool) ($m6 && $m6->actual_date),         'late' => (bool) ($m6 && $m6->is_overdue)],
                    ['key' => 'ATA',     'done' => (bool) $v->ata_at,                        'late' => $v->ota_status?->value === 'late'],
                    ['key' => 'Closing', 'done' => (bool) $v->closing_at,                    'late' => false],
                ];
                $currentStageIdx = null;
                foreach ($stageDefs as $i => $s) {
                    if (! $s['done'] && ! $s['late']) { $currentStageIdx = $i; break; }
                }
            @endphp

            {{-- ═══ ONE VOYAGE BLOCK (WD3) ═══ --}}
            {{-- VI Sprint / VR1 item 6 — verification settle. After a
                 successful RecordingModal save, the just-updated voyage
                 briefly receives elevated weight (a quiet inset ring +
                 raised surface), then settles back to its constitutional
                 resting state after ~1.6s via the `transition` class. No
                 keyframe animation, no color — weight only (VP2 Q4/Q13:
                 the operator SEES the state change land). --}}
            <div wire:key="voyage-row-{{ $v->id }}"
                @if ($recentlyUpdatedVoyageId === $v->id)
                    x-data
                    x-init="$el.classList.add('ring-1', 'ring-inset', 'ring-gray-400');
                            setTimeout(() => $el.classList.remove('ring-1', 'ring-inset', 'ring-gray-400'), 1600)"
                @endif
                class="border-l-[3px] {{ $rowBorder }} {{ $rowBg }} hover:bg-gray-50/40 transition">
                {{-- VC1 Phase 5/6: more breathing room (py-3.5) and clearer
                     group separation (gap-x-8) — spacing/grouping only,
                     no new border, no new color, no card-inside-card. --}}
                <div class="px-4 py-3.5 flex flex-wrap items-start gap-x-8 gap-y-2.5">

                    {{-- IDENTITY (WD3 §3) --}}
                    <div class="w-52 shrink-0">
                        <button wire:click="openDrawer({{ $v->id }})" class="font-bold text-gray-900 text-[13px] text-left hover:text-blue-700 transition">
                            {{ $v->vessel?->name }}
                        </button>
                        <div class="text-[10px] text-gray-500 mt-0.5">
                            {{ $v->shippingLine?->name ?? '—' }} · V.{{ $v->voyage_no }}
                        </div>
                        <div class="text-[9px] text-gray-400 mt-0.5">{{ \App\Supports\BusinessRouteResolver::forVoyage($v) }}</div>
                        <div class="text-[9px] text-gray-400 mt-0.5">
                            ETD {{ $dateFmt($v->etd) }} · ETA {{ $dateFmt($v->eta) }}
                        </div>
                    </div>

                    {{-- CURRENT POSITION (WD3 §4) --}}
                    <div class="w-28 shrink-0">
                        <div class="text-[9px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Posisi</div>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border {{ $position['class'] }}">
                            {{ $position['label'] }}
                        </span>
                        @if ($d1 || $h1)
                            {{-- VR1 WARNING 2 — readiness completion is monochrome
                                 (done = quiet gray-600, not green). Only late stays
                                 red (severity). --}}
                            <div class="text-[9px] text-gray-400 mt-1">
                                Readiness:
                                @if ($d1)
                                    <span class="{{ $d1->is_completed ? 'text-gray-600 font-medium' : ($d1->is_late || $d1->scheduled_at?->isPast() ? 'text-red-600 font-semibold' : 'text-gray-400') }}">D-1</span>
                                @endif
                                @if ($h1)
                                    <span class="{{ $h1->status?->value === 'ok' ? 'text-gray-600 font-medium' : ($h1->status?->value === 'late' ? 'text-red-600 font-semibold' : 'text-gray-400') }}">H-1</span>
                                @endif
                            </div>
                        @endif

                        @if ($temporal)
                            {{-- VP3 anticipation marker — non-alarm, subordinate,
                                 temporary. Only present for healthy voyages whose
                                 ETD/ETA falls today (amber) or tomorrow (gray). --}}
                            <div class="text-[9px] {{ $temporal['class'] }} mt-1">{{ $temporal['label'] }}</div>
                        @endif
                    </div>

                    {{-- OPERATIONAL CONDITION (WD3 §5) — same computation,
                         renamed group label, wording/colors unchanged. --}}
                    <div class="w-48 shrink-0">
                        <div class="text-[9px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Kondisi</div>
                        @if (count($criticalIssues) || count($secondaryIssues))
                            <div class="flex flex-col items-start gap-[2px]">
                                @foreach ($criticalIssues as $issue)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-50/70 text-red-600 border border-red-200/40 whitespace-nowrap">
                                        {{ $issue }}
                                    </span>
                                @endforeach
                                @foreach ($secondaryIssues as $issue)
                                    <span class="text-[9px] font-medium text-amber-600/80 whitespace-nowrap leading-tight">
                                        ↳ {{ $issue }}
                                    </span>
                                @endforeach
                                @if ($causeLabel)
                                    <span class="text-[9px] text-gray-400 italic whitespace-nowrap leading-tight" title="Penyebab: {{ $causeLabel }}">
                                        {{ $causeLabel }}
                                    </span>
                                @endif
                            </div>
                        @else
                            {{-- VR1 WARNING 1 — healthy condition is quiet, not
                                 green. "On Track" recedes at W0 (gray-400). --}}
                            <span class="text-gray-400 text-[10px] font-medium">On Track</span>
                        @endif
                    </div>

                    {{-- OPERATIONAL RESPONSIBILITY (WD3 §6) + Muatan +
                         Quick Action (WD3 §10) — every existing button,
                         same wire:click calls, none removed, grouped
                         under one label instead of a bare "Aksi" column.
                         Severity accent border reused unchanged. --}}
                    <div class="flex-1 min-w-[220px] {{ $hasIssues ? 'border-l pl-3 ' . str_replace('border-l-', 'border-l-', $rowBorder) : '' }}">
                        <div class="text-[9px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Tanggung Jawab</div>
                        <div class="flex items-center flex-wrap gap-2">

                            {{-- Muatan: same cargo plan/actual + input button as before --}}
                            <div class="text-[10px]">
                                @if ($v->cargo_actual !== null)
                                    <span class="font-semibold text-gray-800 tabular-nums">{{ $v->cargo_actual }} unit</span>
                                    @if ($v->cargo_plan !== null)
                                        @php $variance = $v->cargo_actual - $v->cargo_plan; @endphp
                                        {{-- VR1 WARNING 2 — cargo variance is informational,
                                             not a severity alarm: monochrome, never green/red.
                                             A shortfall reads as heavier gray (notable), a
                                             surplus/exact as quiet gray. --}}
                                        <span class="tabular-nums {{ $variance < 0 ? 'text-gray-600 font-medium' : 'text-gray-400' }}">
                                            ({{ $variance >= 0 ? '+' : '' }}{{ $variance }})
                                        </span>
                                    @endif
                                @else
                                    @if ($v->cargo_plan !== null)
                                        <span class="text-gray-400">Rencana {{ $v->cargo_plan }} unit ·</span>
                                    @endif
                                    <button wire:click="openOpModal({{ $v->id }}, 'cargo')"
                                        class="px-1.5 py-0.5 rounded border border-gray-200 text-[9px] text-gray-500 hover:border-blue-300 hover:text-blue-700 transition">
                                        Input Cargo
                                    </button>
                                @endif
                            </div>

                            <div class="flex items-center gap-0.5">
                                <button wire:click="openDrawer({{ $v->id }})"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500 hover:text-blue-600 hover:bg-blue-50/60 transition"
                                    title="Detail Voyage">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>

                                {{-- VR1 WARNING 2 — a recorded actual is confirmation,
                                     not celebration: filled quiet gray, never green.
                                     Unrecorded stays a neutral affordance. --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'atd')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-[8px] font-bold
                                        {{ $v->atd_at ? 'text-gray-600 bg-gray-100 border border-gray-200/70' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50/60' }} transition"
                                    title="Input ATD">
                                    <span>D</span>
                                </button>

                                <button wire:click="openOpModal({{ $v->id }}, 'ata')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-[8px] font-bold
                                        {{ $v->ata_at ? 'text-gray-600 bg-gray-100 border border-gray-200/70' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50/60' }} transition"
                                    title="Input ATA">
                                    <span>A</span>
                                </button>

                                <button wire:click="openOpModal({{ $v->id }}, 'atb')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-[8px] font-bold
                                        {{ $v->atb_at ? 'text-gray-600 bg-gray-100 border border-gray-200/70' : 'text-gray-500 hover:text-gray-600 hover:bg-gray-100/60' }} transition"
                                    title="Input ATB">
                                    <span>B</span>
                                </button>

                                <button wire:click="openOpModal({{ $v->id }}, 'closing')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500 hover:text-gray-700 hover:bg-gray-100/60 transition"
                                    title="Closing">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>

                                <button wire:click="openOpModal({{ $v->id }}, 'delay')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500 hover:text-red-600 hover:bg-red-50/60 transition"
                                    title="Catat Penyebab Delay">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </button>

                                <button wire:click="openOpModal({{ $v->id }}, 'readiness')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500 hover:text-orange-600 hover:bg-orange-50/60 transition"
                                    title="Readiness Check">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>

                                @if ($hasMilestones && $firstMilestone)
                                    <button wire:click="showMilestone({{ $firstMilestone->id }})"
                                        class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-500 hover:text-gray-600 hover:bg-gray-100/60 transition"
                                        title="Milestone">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- OPERATIONAL PROGRESS (WD3 §7 · VM2 §0.6) — monochrome
                         lifecycle rail, 5-stage flow: ATB → ATD →
                         Sailing[D+2→D+4→D+6] → ATA → Closing. 4-glyph
                         grammar: ▰ completed (gray-600) · ▱ current
                         (gray-900) · · future (gray-300) · ◌ missing-
                         expected/late (red-600). The old green-✓ marks and
                         red OTD/OTA pills are unified into this one glyph
                         language — same underlying facts, no data dropped;
                         a late departure/arrival now reads as the red ◌ (or
                         a red ▰ if recorded-but-late) at its own stage. --}}
                    <div class="w-64 shrink-0">
                        <div class="text-[9px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Progress</div>
                        <div class="flex items-center gap-x-1 gap-y-0.5 flex-wrap text-[9px] tabular-nums">
                            @foreach ($stageDefs as $i => $s)
                                @php
                                    [$glyph, $glyphClass] = match (true) {
                                        $s['late'] && $s['done']  => ['▰', 'text-red-600 font-semibold'],   // completed but late
                                        $s['late'] && ! $s['done'] => ['◌', 'text-red-600 font-semibold'],   // overdue / missing expected
                                        $s['done']                => ['▰', 'text-gray-600'],                 // completed, quiet
                                        $i === $currentStageIdx   => ['▱', 'text-gray-900 font-semibold'],   // you-are-here
                                        default                   => ['·', 'text-gray-300'],                 // future
                                    };
                                @endphp
                                @if ($i === 2)
                                    <span class="text-gray-400 mr-0.5">Sailing</span>
                                @endif
                                <span class="{{ $glyphClass }}">{{ $glyph }}&nbsp;{{ $s['key'] }}</span>
                                @if ($i < count($stageDefs) - 1)
                                    <span class="text-gray-200">→</span>
                                @endif
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- Persistent legend — now explains the VM2 §0.6 progress glyph
         grammar (Recognition over Recall, VP2 Q11). Monochrome, one
         reserve hue. --}}
    <div class="mt-2 flex flex-wrap items-center gap-3 text-[9px] text-gray-400 tabular-nums">
        <span class="text-gray-600">▰ Selesai</span>
        <span class="text-gray-900 font-semibold">▱ Tahap sekarang</span>
        <span class="text-gray-300">· Belum</span>
        <span class="text-red-600 font-semibold">◌ Terlambat / belum tercatat</span>
    </div>
@endif

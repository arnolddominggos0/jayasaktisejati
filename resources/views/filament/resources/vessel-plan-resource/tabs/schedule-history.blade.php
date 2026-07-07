@php
use Carbon\Carbon;

// ── Build draft index dari snapshot ────────────────────────────────────────
$draftSnapshot   = $record->draftSnapshot();
$finalSnapshot   = $record->finalSnapshot();
$hasDraftSnapshot = $draftSnapshot !== null;

$draftMap = [];
if ($draftSnapshot) {
    foreach ($draftSnapshot->schedule_payload ?? [] as $row) {
        $draftMap[$row['item_id']] = $row;
    }
}

// ── Build history rows ──────────────────────────────────────────────────────
$historyRows = $items->map(function ($item) use ($draftMap) {
    $draftRow = $draftMap[$item->id] ?? null;

    $draftEtdStr = $draftRow['planned_etd'] ?? null;
    $draftEtaStr = $draftRow['planned_eta'] ?? null;

    $draftEtd = $draftEtdStr ? Carbon::parse($draftEtdStr) : null;
    $draftEta = $draftEtaStr ? Carbon::parse($draftEtaStr) : null;

    $finalEtd = $item->planned_etd;
    $finalEta = $item->planned_eta;

    // Delta: positif = final lebih lambat dari draft (mundur jadwal)
    $deltaEtd = ($draftEtd && $finalEtd) ? (int) $draftEtd->diffInDays($finalEtd, false) : null;
    $deltaEta = ($draftEta && $finalEta) ? (int) $draftEta->diffInDays($finalEta, false) : null;

    $draftSailing = ($draftEtd && $draftEta)
        ? (int) $draftEtd->diffInDays($draftEta)
        : null;

    $finalSailing = ($finalEtd && $finalEta)
        ? (int) $finalEtd->diffInDays($finalEta)
        : null;

    // Sprint 12.9 — Voyage format kanon: V.NNN · Shipping Line
    $voyageCanon = collect([
        $item->voyage_no ? 'V.' . $item->voyage_no : null,
        $item->shippingLine?->name,
    ])->filter()->implode(' · ') ?: '—';

    return [
        'id'             => $item->id,
        'vessel'         => $item->vessel?->name ?? '—',
        'voyage_label'   => $voyageCanon,
        'shipping_line'  => $item->shippingLine?->name ?? '—',

        // Draft
        'draft_etd'      => $draftEtd?->format('d M Y'),
        'draft_eta'      => $draftEta?->format('d M Y'),
        'draft_sailing'  => $draftSailing,
        'has_draft'      => $draftRow !== null,

        // Final
        'final_etd'      => $finalEtd?->format('d M Y'),
        'final_eta'      => $finalEta?->format('d M Y'),
        'final_sailing'  => $finalSailing,

        // Delta
        'delta_etd'      => $deltaEtd,
        'delta_eta'      => $deltaEta,
        'delta_sailing'  => ($draftSailing !== null && $finalSailing !== null)
                                ? $finalSailing - $draftSailing
                                : null,
    ];
})->values()->all();

// ── Alpine data — di-encode aman untuk JS ──────────────────────────────────
$alpineData = json_encode($historyRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

// ── Delta label helper ──────────────────────────────────────────────────────
$deltaLabel = function (?int $d, bool $short = false): array {
    if ($d === null) return ['text' => '—',                          'class' => 'text-gray-400'];
    if ($d === 0)    return ['text' => '±0',                         'class' => 'text-gray-500'];
    if ($d > 0)      return ['text' => '+' . $d . ($short ? '' : ' hari'), 'class' => 'text-amber-600 font-semibold'];
    return                  ['text' =>       $d . ($short ? '' : ' hari'), 'class' => 'text-emerald-600 font-semibold'];
};
@endphp

{{-- Alpine root — table + detail drawer --}}
<div
    x-data="{
        open: false,
        selected: null,
        rows: {{ $alpineData }},
        showDetail(row) { this.selected = row; this.open = true; },
        deltaClass(d) {
            if (d === null) return 'text-gray-400';
            if (d === 0)    return 'text-gray-500';
            return d > 0    ? 'text-amber-600 font-semibold' : 'text-emerald-600 font-semibold';
        },
        deltaText(d, unit = ' hari') {
            if (d === null) return '—';
            if (d === 0)    return '±0';
            return (d > 0 ? '+' : '') + d + unit;
        }
    }"
    class="space-y-2.5"
>

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
                Riwayat Jadwal
            </div>
            <p class="text-sm text-gray-500">
                Perbandingan
                <span class="font-semibold text-blue-600">Jadwal Draft</span>
                vs
                <span class="font-semibold text-emerald-600">Jadwal Final</span>
                per kapal.
                Klik baris untuk melihat detail perubahan.
            </p>
        </div>

        {{-- Snapshot meta --}}
        <div class="shrink-0 text-right">
            @if ($draftSnapshot)
            <div class="text-xs text-gray-500">
                Draft dikirim:
                <span class="font-semibold text-gray-700">
                    {{ $draftSnapshot->created_at?->format('d M Y H:i') }}
                </span>
            </div>
            @endif
            @if ($finalSnapshot)
            <div class="text-xs text-gray-500 mt-0.5">
                Difinalisasi:
                <span class="font-semibold text-gray-700">
                    {{ $finalSnapshot->created_at?->format('d M Y H:i') }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- History Table --}}
    @if (count($historyRows) === 0)
    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
        <div class="text-sm text-gray-500">Belum ada perubahan jadwal.</div>
    </div>
    @else
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    {{-- Vessel --}}
                    <th class="text-left px-4 py-2.5 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Kapal
                    </th>
                    {{-- Draft --}}
                    <th class="text-center px-3 py-2.5 text-[11px] uppercase tracking-wider font-bold">
                        <span class="text-blue-500">Draft</span> ETD
                    </th>
                    <th class="text-center px-3 py-2.5 text-[11px] uppercase tracking-wider font-bold">
                        <span class="text-blue-500">Draft</span> ETA
                    </th>
                    {{-- Final --}}
                    <th class="text-center px-3 py-2.5 text-[11px] uppercase tracking-wider font-bold">
                        <span class="text-emerald-600">Final</span> ETD
                    </th>
                    <th class="text-center px-3 py-2.5 text-[11px] uppercase tracking-wider font-bold">
                        <span class="text-emerald-600">Final</span> ETA
                    </th>
                    {{-- Delta --}}
                    <th class="text-center px-3 py-2.5 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Δ ETD
                    </th>
                    <th class="text-center px-3 py-2.5 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Δ ETA
                    </th>
                    {{-- Action --}}
                    <th class="px-4 py-2.5 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($historyRows as $i => $row)
                @php
                    $de = $deltaLabel($row['delta_etd']);
                    $da = $deltaLabel($row['delta_eta']);
                @endphp
                <tr
                    class="hover:bg-gray-50 cursor-pointer transition-colors"
                    @click="showDetail(rows[{{ $i }}])"
                >
                    {{-- Vessel --}}
                    <td class="px-4 py-2.5">
                        <div class="font-semibold text-gray-800">{{ $row['vessel'] }}</div>
                        <div class="text-[11px] text-gray-500 font-mono">{{ $row['voyage_label'] }}</div>
                    </td>

                    {{-- Draft --}}
                    <td class="px-3 py-2.5 text-center font-mono text-blue-600 text-xs">
                        {{ $row['draft_etd'] ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-center font-mono text-blue-600 text-xs">
                        {{ $row['draft_eta'] ?? '—' }}
                    </td>

                    {{-- Final --}}
                    <td class="px-3 py-2.5 text-center font-mono text-emerald-700 text-xs font-semibold">
                        {{ $row['final_etd'] ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-center font-mono text-emerald-700 text-xs font-semibold">
                        {{ $row['final_eta'] ?? '—' }}
                    </td>

                    {{-- Delta --}}
                    <td class="px-3 py-2.5 text-center text-xs {{ $de['class'] }}">{{ $de['text'] }}</td>
                    <td class="px-3 py-2.5 text-center text-xs {{ $da['class'] }}">{{ $da['text'] }}</td>

                    {{-- Arrow --}}
                    <td class="px-4 py-2.5 text-gray-300 hover:text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ─── Detail Drawer ──────────────────────────────────────────────── --}}
    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.self="open = false"
        class="fixed inset-0 bg-black/30 z-40"
        x-cloak
    ></div>

    {{-- Drawer panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 h-full w-full max-w-sm bg-white shadow-2xl z-50 overflow-y-auto"
        x-cloak
    >
        <template x-if="selected">
            <div class="p-6 space-y-6">

                {{-- Drawer header --}}
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-[10px] text-gray-400 uppercase tracking-wider">Detail Perubahan</div>
                        <div class="text-xl font-bold text-gray-900 mt-0.5" x-text="selected.vessel"></div>
                        <div class="text-xs font-mono text-gray-400 mt-0.5" x-text="selected.voyage_label"></div>
                    </div>
                    <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Draft Schedule --}}
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                        <div class="text-xs font-bold text-blue-600 uppercase tracking-wider">Jadwal Draft</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-[10px] text-blue-400 uppercase mb-1">ETD</div>
                            <div class="font-semibold text-blue-800 text-sm"
                                 x-text="selected.draft_etd || '—'"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-blue-400 uppercase mb-1">ETA</div>
                            <div class="font-semibold text-blue-800 text-sm"
                                 x-text="selected.draft_eta || '—'"></div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-[10px] text-blue-400 uppercase mb-1">Sailing</div>
                            <div class="font-semibold text-blue-800 text-sm"
                                 x-text="selected.draft_sailing !== null ? selected.draft_sailing + ' hari' : '—'"></div>
                        </div>
                    </div>
                </div>

                {{-- Final Schedule --}}
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Jadwal Final</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-[10px] text-emerald-400 uppercase mb-1">ETD</div>
                            <div class="font-semibold text-emerald-800 text-sm"
                                 x-text="selected.final_etd || '—'"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-emerald-400 uppercase mb-1">ETA</div>
                            <div class="font-semibold text-emerald-800 text-sm"
                                 x-text="selected.final_eta || '—'"></div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-[10px] text-emerald-400 uppercase mb-1">Sailing</div>
                            <div class="font-semibold text-emerald-800 text-sm"
                                 x-text="selected.final_sailing !== null ? selected.final_sailing + ' hari' : '—'"></div>
                        </div>
                    </div>
                </div>

                {{-- Perubahan / Delta --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-gray-500"></div>
                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Perubahan</div>
                    </div>
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">ETD</span>
                            <span class="text-sm font-semibold"
                                  :class="deltaClass(selected.delta_etd)"
                                  x-text="deltaText(selected.delta_etd)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">ETA</span>
                            <span class="text-sm font-semibold"
                                  :class="deltaClass(selected.delta_eta)"
                                  x-text="deltaText(selected.delta_eta)"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Sailing</span>
                                <span class="text-sm font-semibold"
                                      :class="deltaClass(selected.delta_sailing)"
                                      x-text="deltaText(selected.delta_sailing)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shipping line --}}
                <div class="text-xs text-gray-400 text-center">
                    Shipping Line: <span class="text-gray-600 font-medium" x-text="selected.shipping_line"></span>
                </div>

            </div>
        </template>
    </div>

</div>{{-- /x-data --}}

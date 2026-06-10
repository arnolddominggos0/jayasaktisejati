@php
/**
* Tab: Schedule History Logbook — Vessel Plan
*
* Menampilkan perbandingan Draft vs Final Schedule per vessel.
*
* Sumber data:
* Draft = VesselPlanSnapshot(stage='draft_submitted').schedule_payload
* Final = VesselPlanItem.planned_etd / planned_eta (current state)
*
* Actual TIDAK ditampilkan di sini — actual adalah domain Voyage.
*
* Delta = Final - Draft (dalam hari, positif = terlambat/naik).
*
* Klik baris → detail drawer Alpine.js
* Backfill ready: seeder bisa mengisi draft dari snapshot historis.
*/

@php

use Carbon\Carbon;

$historyRows = $items->map(function ($item) {

```
$voyage = $item->voyage;

$histories = $voyage?->scheduleHistories ?? collect();

$draft = $histories->firstWhere('schedule_type', 'draft');
$final = $histories->firstWhere('schedule_type', 'final');
$actual = $histories->firstWhere('schedule_type', 'actual');

return [

'id' => $item->id,

'vessel' => $item->vessel?->name ?? '—',

'voyage_no' => $item->voyage_no ?? '—',

'shipping_line' => $item->shippingLine?->name ?? '—',

// Draft
'draft_etd' => $draft?->etd?->format('d M Y'),
'draft_eta' => $draft?->eta?->format('d M Y'),
'draft_sailing' => $draft?->sailing_days,

// Final
'final_etd' => $final?->etd?->format('d M Y'),
'final_eta' => $final?->eta?->format('d M Y'),
'final_sailing' => $final?->sailing_days,

// Actual
'actual_etd' => $actual?->etd?->format('d M Y'),
'actual_eta' => $actual?->eta?->format('d M Y'),
'actual_sailing' => $actual?->sailing_days,

// Variance
'delta_df' => (
$draft && $final
)
? round($final->sailing_days - $draft->sailing_days, 1)
: null,

'delta_fa' => (
$final && $actual
)
? round($actual->sailing_days - $final->sailing_days, 1)
: null,

'delta_da' => (
$draft && $actual
)
? round($actual->sailing_days - $draft->sailing_days, 1)
: null,

];
```

});

$alpineData = $historyRows->values()->toJson();

$varianceClass = function ($v) {

```
if ($v === null) {
return 'text-gray-400';
}

if ($v == 0) {
return 'text-gray-500';
}

return $v > 0
? 'text-red-600 font-semibold'
: 'text-emerald-600 font-semibold';
```

};

$varianceLabel = function ($v) {

```
if ($v === null) {
return '—';
}

if ($v == 0) {
return '±0';
}

return ($v > 0 ? '+' : '') . $v . ' hr';
```

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
    class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
                Schedule History Logbook
            </div>
            <p class="text-sm text-gray-500">
                Perbandingan
                <span class="font-semibold text-blue-600">Draft Schedule</span>
                vs
                <span class="font-semibold text-emerald-600">Final Schedule</span>
                per vessel.
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
        <div class="text-sm text-gray-400 italic">Belum ada item jadwal di vessel plan ini.</div>
    </div>
    @else
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    {{-- Vessel --}}
                    <thead>
                        <tr class="bg-gray-50 border-b">

                            ```
                            <th class="px-4 py-3 text-left">
                                Vessel
                            </th>

                            <th class="px-3 py-3 text-center">
                                Draft
                            </th>

                            <th class="px-3 py-3 text-center">
                                Final
                            </th>

                            <th class="px-3 py-3 text-center">
                                Actual
                            </th>

                            <th class="px-3 py-3 text-center">
                                Draft→Final
                            </th>

                            <th class="px-3 py-3 text-center">
                                Final→Actual
                            </th>

                            <th class="px-3 py-3 text-center">
                                Draft→Actual
                            </th>
                            ```

                        </tr>
                    </thead>
                    {{-- Action --}}
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <tbody>

                @foreach ($historyRows as $row)

                <tr class="hover:bg-gray-50">

                    ```
                    <td class="px-4 py-3">

                        <div class="font-semibold">
                            {{ $row['vessel'] }}
                        </div>

                        <div class="text-xs text-gray-400">
                            {{ $row['voyage_no'] }}
                        </div>

                    </td>

                    <td class="px-3 py-3 text-center text-xs">

                        <div>{{ $row['draft_etd'] ?? '—' }}</div>
                        <div>{{ $row['draft_eta'] ?? '—' }}</div>

                        <div class="mt-1 font-semibold text-blue-600">
                            {{ $row['draft_sailing'] ?? '—' }}
                        </div>

                    </td>

                    <td class="px-3 py-3 text-center text-xs">

                        <div>{{ $row['final_etd'] ?? '—' }}</div>
                        <div>{{ $row['final_eta'] ?? '—' }}</div>

                        <div class="mt-1 font-semibold text-emerald-600">
                            {{ $row['final_sailing'] ?? '—' }}
                        </div>

                    </td>

                    <td class="px-3 py-3 text-center text-xs">

                        <div>{{ $row['actual_etd'] ?? '—' }}</div>
                        <div>{{ $row['actual_eta'] ?? '—' }}</div>

                        <div class="mt-1 font-semibold text-violet-600">
                            {{ $row['actual_sailing'] ?? '—' }}
                        </div>

                    </td>

                    <td class="px-3 py-3 text-center {{ $varianceClass($row['delta_df']) }}">
                        {{ $varianceLabel($row['delta_df']) }}
                    </td>

                    <td class="px-3 py-3 text-center {{ $varianceClass($row['delta_fa']) }}">
                        {{ $varianceLabel($row['delta_fa']) }}
                    </td>

                    <td class="px-3 py-3 text-center {{ $varianceClass($row['delta_da']) }}">
                        {{ $varianceLabel($row['delta_da']) }}
                    </td>
                    ```

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
        x-cloak></div>

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
        x-cloak>
        <template x-if="selected">
            <div class="p-6 space-y-6">

                {{-- Drawer header --}}
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-[10px] text-gray-400 uppercase tracking-wider">Detail Perubahan</div>
                        <div class="text-xl font-bold text-gray-900 mt-0.5" x-text="selected.vessel"></div>
                        <div class="text-xs font-mono text-gray-400 mt-0.5" x-text="selected.voyage_no"></div>
                    </div>
                    <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Draft Schedule --}}
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                        <div class="text-xs font-bold text-blue-600 uppercase tracking-wider">Draft Schedule</div>
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
                        <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Final Schedule</div>
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
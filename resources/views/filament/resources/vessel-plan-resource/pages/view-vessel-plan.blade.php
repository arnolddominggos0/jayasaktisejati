@php
/**
 * View Vessel Plan — 3-tab layout
 *
 * Tab 1 — Overview        : items table (bestaan) + summary
 * Tab 2 — Schedule Analysis : analisis per-vessel vs standar TAM
 * Tab 3 — Schedule History  : Draft vs Final + delta & detail drawer
 *
 * Header widget (VesselPlanAnalysis — Evaluasi Risiko Jadwal) tetap
 * dirender oleh Filament di atas tab, via getHeaderWidgets().
 */

$analysis  = $record->analyze();
$items     = $record->items->sortBy('planned_etd');
$total     = $items->count();
$riskLevel = $analysis['risk_level'] ?? 'valid';

$statusLabel = match (true) {
    $total < 2                => 'Data Belum Cukup',
    $riskLevel === 'warning'  => 'PERINGATAN',
    $riskLevel === 'critical' => 'KRITIS',
    default                   => 'VALID',
};

$statusColor = match ($riskLevel) {
    'warning'  => 'text-amber-600',
    'critical' => 'text-red-600',
    default    => 'text-green-600',
};
@endphp

<x-filament-panels::page>

    {{-- ──────────────────────────────────────────────────────────────────
         Tab Navigation — persisten via URL ?tab=
    ─────────────────────────────────────────────────────────────────── --}}
    <div
        x-data="{
            tab: new URLSearchParams(window.location.search).get('tab') || 'overview'
        }"
        x-init="
            $watch('tab', v => {
                const u = new URL(window.location);
                u.searchParams.set('tab', v);
                window.history.replaceState({}, '', u);
            })
        "
    >

        {{-- Tab bar --}}
        <div class="flex items-center gap-1 border-b border-gray-200 mb-5">

            <button
                @click="tab = 'overview'"
                :class="tab === 'overview'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                Overview
            </button>

            <button
                @click="tab = 'analysis'"
                :class="tab === 'analysis'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                Final Schedule Analysis
            </button>

            <button
                @click="tab = 'history'"
                :class="tab === 'history'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                Schedule History
            </button>

        </div>

        {{-- ──────────────────────────────────────────────────────────────
             TAB 1 — Overview
             Isi yang sudah ada: summary strip + tabel items
        ─────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'overview'" x-cloak>

            <x-vessel-plan.summary
                :total="$total"
                :maxGap="$analysis['max_gap'] ?? 0"
                :idealGap="6"
                :statusLabel="$statusLabel"
                :statusColor="$statusColor"
            />

            <div class="overflow-hidden rounded-xl border bg-white mt-4">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Nama Pelayaran</th>
                            <th class="px-4 py-3 text-left">Voyage</th>
                            <th class="px-4 py-3 text-left">ETD</th>
                            <th class="px-4 py-3 text-left">ETA</th>
                            <th class="px-4 py-3 text-center">Selisih ETD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($items as $item)
                            @php $gap = $analysis['gaps'][$item->id] ?? null; @endphp
                            <tr>
                                <td class="px-4 py-3">{{ $item->shippingLine?->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $item->voyage_no }}</td>
                                <td class="px-4 py-3">{{ $item->planned_etd?->format('d M Y') }}</td>
                                <td class="px-4 py-3">{{ $item->planned_eta?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-center">{{ $gap === null ? '—' : $gap }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
        {{-- /tab:overview --}}

        {{-- ──────────────────────────────────────────────────────────────
             TAB 2 — Final Schedule Analysis
             Per-vessel: Dwelling / Sailing / Dooring / Lead Time / Variance
        ─────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'analysis'" x-cloak>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                @include(
                    'filament.resources.vessel-plan-resource.tabs.schedule-analysis',
                    ['record' => $record, 'items' => $items]
                )
            </div>
        </div>
        {{-- /tab:analysis --}}

        {{-- ──────────────────────────────────────────────────────────────
             TAB 3 — Schedule History
             Draft vs Final, delta perubahan, detail drawer per vessel
        ─────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'history'" x-cloak>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                @include(
                    'filament.resources.vessel-plan-resource.tabs.schedule-history',
                    ['record' => $record, 'items' => $items]
                )
            </div>
        </div>
        {{-- /tab:history --}}

    </div>
    {{-- /x-data --}}

</x-filament-panels::page>

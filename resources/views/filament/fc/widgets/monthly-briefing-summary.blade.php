<x-filament-widgets::widget class="fi-wi-monthly-briefing-summary col-span-full">
@php
    $data           = $this->getViewData();
    $months         = $data['months'];
    $year           = $data['year'];
    $grandTotal     = $data['grand_total'];
    $grandUnit      = $data['grand_unit'];
    $grandReady     = $data['grand_ready'];
    $grandNg        = $data['grand_ng'];
    $grandReadiness = $data['grand_readiness'];
@endphp

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5 dark:border-gray-800">
        <div class="flex items-center gap-2.5">
            <x-heroicon-m-table-cells class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-sm font-bold text-gray-900 dark:text-white">Ringkasan Bulanan {{ $year }}</span>
        </div>
        @if ($grandReadiness !== null)
            <span class="text-xs font-semibold
                         {{ $grandReadiness >= 90 ? 'text-emerald-600 dark:text-emerald-400'
                           : ($grandReadiness >= 70 ? 'text-amber-600 dark:text-amber-400'
                                                    : 'text-rose-600 dark:text-rose-400') }}">
                YTD Readiness: {{ $grandReadiness }}%
            </span>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 w-28">Bulan</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Sesi</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Unit Masuk</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">READY</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">NG</th>
                    <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 min-w-[120px]">Readiness</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                @foreach ($months as $row)
                    <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/30
                               {{ ! $row['has_data'] ? 'opacity-40' : '' }}">

                        {{-- Bulan --}}
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">
                            {{ $row['label'] }}
                        </td>

                        {{-- Total sesi --}}
                        <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">
                            {{ $row['has_data'] ? $row['total_sesi'] : '—' }}
                        </td>

                        {{-- Unit masuk --}}
                        <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">
                            {{ $row['has_data'] ? number_format($row['total_unit']) : '—' }}
                        </td>

                        {{-- READY --}}
                        <td class="px-4 py-3 text-right tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $row['has_data'] ? $row['sesi_ready'] : '—' }}
                        </td>

                        {{-- NG --}}
                        <td class="px-4 py-3 text-right tabular-nums
                                   {{ $row['sesi_ng'] > 0 ? 'font-semibold text-rose-500 dark:text-rose-400' : 'text-gray-400' }}">
                            {{ $row['has_data'] ? ($row['sesi_ng'] > 0 ? $row['sesi_ng'] : '—') : '—' }}
                        </td>

                        {{-- Readiness bar + % --}}
                        <td class="px-4 py-3">
                            @if ($row['readiness'] !== null)
                                @php
                                    $pct = $row['readiness'];
                                    $barW = max(2, (int) $pct);
                                    $barCl = $pct >= 90 ? 'bg-emerald-500'
                                           : ($pct >= 70 ? 'bg-amber-400' : 'bg-rose-500');
                                    $txtCl = $pct >= 90 ? 'text-emerald-600 dark:text-emerald-400'
                                           : ($pct >= 70 ? 'text-amber-600 dark:text-amber-400'
                                                         : 'text-rose-600 dark:text-rose-400');
                                @endphp
                                <div class="flex items-center justify-end gap-2">
                                    <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-1.5 rounded-full {{ $barCl }} transition-all"
                                             style="width: {{ $barW }}%"></div>
                                    </div>
                                    <span class="w-12 text-right text-xs font-semibold tabular-nums {{ $txtCl }}">
                                        {{ $pct }}%
                                    </span>
                                </div>
                            @else
                                <span class="block text-right text-xs text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>

            {{-- Totals --}}
            <tfoot>
                <tr class="border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <td class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</td>
                    <td class="px-4 py-3 text-right tabular-nums font-bold text-gray-800 dark:text-gray-200">{{ $grandTotal }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-bold text-gray-800 dark:text-gray-200">{{ number_format($grandUnit) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-bold text-emerald-600 dark:text-emerald-400">{{ $grandReady }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-bold {{ $grandNg > 0 ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400' }}">
                        {{ $grandNg > 0 ? $grandNg : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($grandReadiness !== null)
                            @php
                                $gPct = $grandReadiness;
                                $gCl  = $gPct >= 90 ? 'text-emerald-600 dark:text-emerald-400'
                                      : ($gPct >= 70 ? 'text-amber-600 dark:text-amber-400'
                                                     : 'text-rose-600 dark:text-rose-400');
                            @endphp
                            <span class="block text-right text-sm font-bold tabular-nums {{ $gCl }}">{{ $gPct }}%</span>
                        @else
                            <span class="block text-right text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-filament-widgets::widget>

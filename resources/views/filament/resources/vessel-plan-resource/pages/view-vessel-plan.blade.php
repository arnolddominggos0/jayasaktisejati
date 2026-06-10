<x-filament-panels::page>
@php
    use Carbon\Carbon;

    $dooringLead = 3; // hari, sesuai standar perusahaan

    $items = $record->items()->with(['vessel', 'shippingLine', 'voyage'])->orderBy('planned_etd')->get();

    $rows = $items->map(function ($item) use ($dooringLead) {
        $v = $item->voyage;

        // Draft (vessel_plan_items)
        $dETB = $item->planned_etb;
        $dETD = $item->planned_etd;
        $dETA = $item->planned_eta;

        // Final (voyages.etb/etd/eta — already cast as Carbon)
        $fETB = $v?->etb;
        $fETD = $v?->etd;
        $fETA = $v?->eta;

        // Dwelling = ETD − ETB (days)
        $dDwell = ($dETB && $dETD) ? round($dETB->diffInSeconds($dETD) / 86400, 1) : null;
        $fDwell = ($fETB && $fETD) ? round($fETB->diffInSeconds($fETD) / 86400, 1) : null;
        $delDwell = ($dDwell !== null && $fDwell !== null) ? round($fDwell - $dDwell, 1) : null;

        // Sailing = ETA − ETD (days)
        $dSail = ($dETD && $dETA) ? round($dETD->diffInSeconds($dETA) / 86400, 1) : null;
        $fSail = ($fETD && $fETA) ? round($fETD->diffInSeconds($fETA) / 86400, 1) : null;
        $delSail = ($dSail !== null && $fSail !== null) ? round($fSail - $dSail, 1) : null;

        // Forecast Dooring = ETA + dooringLead days
        $dDoorDate = $dETA ? $dETA->copy()->addDays($dooringLead) : null;
        $fDoorDate = $fETA ? $fETA->copy()->addDays($dooringLead) : null;
        // Δ Dooring = shift of estimated delivery (signed days)
        $delDoor = ($dDoorDate && $fDoorDate)
            ? round($dDoorDate->diffInSeconds($fDoorDate, false) / 86400, 1)
            : null;

        $isRevised = ($delDwell !== null && $delDwell != 0)
                  || ($delSail  !== null && $delSail  != 0);

        $status = match (true) {
            $fETD === null => 'no_final',
            $isRevised     => 'revised',
            default        => 'no_change',
        };

        return compact(
            'item',
            'dETD', 'fETD',
            'dDwell', 'fDwell', 'delDwell',
            'dSail',  'fSail',  'delSail',
            'dDoorDate', 'fDoorDate', 'delDoor',
            'status'
        );
    })->all();

    // Summary aggregates
    $totalVoyages  = count($rows);
    $revisedCount  = count(array_filter($rows, fn($r) => $r['status'] === 'revised'));

    $pick = fn(string $key) => array_filter(array_column($rows, $key), fn($v) => $v !== null);
    $avg  = function (array $vals): ?float {
        return count($vals) ? round(array_sum($vals) / count($vals), 1) : null;
    };

    $avgDDwell  = $avg($pick('dDwell'));
    $avgFDwell  = $avg($pick('fDwell'));
    $avgDelDwell = ($avgDDwell !== null && $avgFDwell !== null) ? round($avgFDwell - $avgDDwell, 1) : null;

    $avgDSail   = $avg($pick('dSail'));
    $avgFSail   = $avg($pick('fSail'));
    $avgDelSail  = ($avgDSail !== null && $avgFSail !== null) ? round($avgFSail - $avgDSail, 1) : null;

    $avgDelDoor  = $avg($pick('delDoor'));

    // Helpers
    $fmt = fn($d) => $d ? $d->format('d M') : '—';

    $fmtDelta = function (?float $v): string {
        if ($v === null) return '—';
        if ($v == 0)     return '0';
        return ($v > 0 ? '+' : '') . $v;
    };

    $deltaClass = function (?float $v): string {
        if ($v === null || $v == 0) return 'text-gray-500';
        return $v > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold';
    };

    $impactCard = function (?float $v, string $positiveLabel = 'lebih lama', string $negativeLabel = 'lebih cepat'): string {
        if ($v === null) return '—';
        if ($v == 0)     return 'Tidak berubah';
        $abs = abs($v);
        return ($v > 0 ? "+{$abs} hari ({$positiveLabel})" : "{$v} hari ({$negativeLabel})");
    };
@endphp

    {{-- ── Schedule Impact Analysis ─────────────────────────────── --}}
    <div class="rounded-xl border bg-white px-6 py-5 mb-6">

        <h3 class="text-base font-semibold mb-1">Schedule Impact Analysis</h3>
        <p class="text-sm text-gray-500 mb-5">
            Perbandingan Draft vs Final schedule.
            Menampilkan dampak perubahan jadwal terhadap Dwelling, Sailing, dan estimasi Dooring.
        </p>

        {{-- KPI Summary ──────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-3 lg:grid-cols-5">

            <div class="rounded-lg border bg-gray-50 p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Voyage</div>
                <div class="text-2xl font-bold text-gray-800">{{ $totalVoyages }}</div>
            </div>

            <div class="rounded-lg border {{ $revisedCount > 0 ? 'bg-amber-50' : 'bg-gray-50' }} p-4">
                <div class="text-xs {{ $revisedCount > 0 ? 'text-amber-600' : 'text-gray-500' }} uppercase tracking-wide mb-1">Voyage Direvisi</div>
                <div class="text-2xl font-bold {{ $revisedCount > 0 ? 'text-amber-700' : 'text-gray-400' }}">{{ $revisedCount }}</div>
            </div>

            <div class="rounded-lg border bg-blue-50 p-4">
                <div class="text-xs text-blue-600 uppercase tracking-wide mb-2 font-semibold">Avg Dwelling (hari)</div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Draft</span><span>{{ $avgDDwell ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Final</span><span>{{ $avgFDwell ?? '—' }}</span></div>
                    <div class="flex justify-between border-t pt-1 mt-1">
                        <span class="text-gray-500">Δ</span>
                        <span class="{{ $deltaClass($avgDelDwell) }}">{{ $fmtDelta($avgDelDwell) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-indigo-50 p-4">
                <div class="text-xs text-indigo-600 uppercase tracking-wide mb-2 font-semibold">Avg Sailing (hari)</div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Draft</span><span>{{ $avgDSail ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Final</span><span>{{ $avgFSail ?? '—' }}</span></div>
                    <div class="flex justify-between border-t pt-1 mt-1">
                        <span class="text-gray-500">Δ</span>
                        <span class="{{ $deltaClass($avgDelSail) }}">{{ $fmtDelta($avgDelSail) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border {{ $avgDelDoor !== null && $avgDelDoor > 0 ? 'bg-rose-50' : 'bg-gray-50' }} p-4">
                <div class="text-xs {{ $avgDelDoor !== null && $avgDelDoor > 0 ? 'text-rose-600' : 'text-gray-500' }} uppercase tracking-wide mb-1 font-semibold">Avg Dooring Shift</div>
                <div class="text-2xl font-bold {{ $deltaClass($avgDelDoor) }}">
                    {{ $avgDelDoor !== null ? $fmtDelta($avgDelDoor) . ' hr' : '—' }}
                </div>
                <div class="text-xs text-gray-400 mt-1">ETA + {{ $dooringLead }} hari</div>
            </div>

        </div>

        {{-- Analysis Table ───────────────────────────────────────── --}}
        @if (empty($rows))
            <div class="rounded-lg border border-dashed py-8 text-center text-sm text-gray-400">
                Belum ada data voyage plan.
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="px-3 py-3 text-left" rowspan="2">Voyage</th>
                            <th class="px-3 py-3 text-center border-l border-gray-600" colspan="2">ETD</th>
                            <th class="px-3 py-3 text-center border-l border-gray-600" colspan="3">Dwelling (hari)</th>
                            <th class="px-3 py-3 text-center border-l border-gray-600" colspan="3">Sailing (hari)</th>
                            <th class="px-3 py-3 text-center border-l border-gray-600" rowspan="2">Forecast<br>Dooring Δ</th>
                            <th class="px-3 py-3 text-center border-l border-gray-600" rowspan="2">Status</th>
                        </tr>
                        <tr class="bg-gray-700 text-gray-300">
                            <th class="px-3 py-2 text-center border-l border-gray-600">Draft</th>
                            <th class="px-3 py-2 text-center">Final</th>
                            <th class="px-3 py-2 text-center border-l border-gray-600">Draft</th>
                            <th class="px-3 py-2 text-center">Final</th>
                            <th class="px-3 py-2 text-center">Δ</th>
                            <th class="px-3 py-2 text-center border-l border-gray-600">Draft</th>
                            <th class="px-3 py-2 text-center">Final</th>
                            <th class="px-3 py-2 text-center">Δ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 font-medium text-gray-800 whitespace-nowrap">
                                    {{ $row['item']->voyage_no ?? '—' }}
                                    <div class="text-gray-400 font-normal">{{ $row['item']->vessel?->name ?? '' }}</div>
                                </td>

                                {{-- ETD Draft / Final --}}
                                <td class="px-3 py-3 text-center border-l text-gray-600">{{ $fmt($row['dETD']) }}</td>
                                <td class="px-3 py-3 text-center {{ $row['status'] === 'revised' ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">
                                    {{ $fmt($row['fETD']) }}
                                    @if ($row['status'] === 'revised' && $row['fETD'] && $row['dETD'] && $row['fETD'] != $row['dETD'])
                                        <span class="text-amber-400 ml-0.5">↺</span>
                                    @endif
                                </td>

                                {{-- Dwelling Draft / Final / Δ --}}
                                <td class="px-3 py-3 text-center border-l text-gray-600">{{ $row['dDwell'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-gray-600">{{ $row['fDwell'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-center {{ $deltaClass($row['delDwell']) }}">{{ $fmtDelta($row['delDwell']) }}</td>

                                {{-- Sailing Draft / Final / Δ --}}
                                <td class="px-3 py-3 text-center border-l text-gray-600">{{ $row['dSail'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-gray-600">{{ $row['fSail'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-center {{ $deltaClass($row['delSail']) }}">{{ $fmtDelta($row['delSail']) }}</td>

                                {{-- Forecast Dooring Δ --}}
                                <td class="px-3 py-3 text-center border-l {{ $deltaClass($row['delDoor']) }}">
                                    @if ($row['delDoor'] !== null)
                                        {{ $fmtDelta($row['delDoor']) }} hr
                                        @if ($row['fDoorDate'])
                                            <div class="text-gray-400 font-normal">{{ $row['fDoorDate']->format('d M') }}</div>
                                        @endif
                                    @elseif ($row['dDoorDate'])
                                        <span class="text-gray-500">{{ $row['dDoorDate']->format('d M') }}</span>
                                        <div class="text-gray-400">draft only</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-3 py-3 text-center border-l">
                                    @switch($row['status'])
                                        @case('revised')
                                            <span class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Revised</span>
                                            @break
                                        @case('no_change')
                                            <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">No Change</span>
                                            @break
                                        @default
                                            <span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-500">No Final</span>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Interpretation note --}}
            <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
                <span><span class="text-red-500 font-semibold">+</span> positif = lebih lama / terlambat</span>
                <span><span class="text-green-500 font-semibold">−</span> negatif = lebih cepat / lebih awal</span>
                <span>Forecast Dooring = Final ETA + {{ $dooringLead }} hari</span>
            </div>
        @endif

    </div>

</x-filament-panels::page>

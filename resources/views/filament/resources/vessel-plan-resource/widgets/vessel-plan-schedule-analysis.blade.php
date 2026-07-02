@php
    $fmtDate = fn($d) => $d ? $d->format('d M Y') : '—';

    $fmtImpact = function (?float $v): string {
        if ($v === null) return '—';
        if ($v == 0)     return '0 hari';
        return ($v > 0 ? '+' : '') . $v . ' hari';
    };

    $impactClass = function (?float $v): string {
        if ($v === null || $v == 0) return 'text-gray-500';
        return $v > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold';
    };

    $n = $narrative ?? null;
@endphp

<div class="rounded-xl border bg-white px-6 py-5 mb-6">

@if (! $hasData)

    <h3 class="text-base font-semibold mb-1">Analisa Jadwal Kapal</h3>
    <p class="text-sm text-gray-500">Belum ada jadwal kapal pada vessel plan ini.</p>

@else

    <h3 class="text-base font-semibold mb-1">Analisa Jadwal Kapal</h3>
    <p class="text-sm text-gray-500 mb-5">
        Dampak perubahan jadwal Draft → Final → Actual terhadap Dwelling, Sailing, dan Dooring per kapal.
    </p>

    {{-- Auto narrative --}}
    @if ($n['revisedCount'] > 0 && $n['topRow'])
        @php
            $top = $n['topRow'];
            $dwDir  = $top['dwellingImpact'] > 0 ? 'meningkat' : 'berkurang';
            $dwAbs  = abs($top['dwellingImpact']);
        @endphp
        <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 mb-5 text-sm text-blue-800 space-y-1">
            <p>
                Ditemukan <strong>{{ $n['revisedCount'] }}</strong> jadwal mengalami revisi keberangkatan.
                Perubahan terbesar terjadi pada <strong>{{ $top['vessel_name'] }}</strong>
                (<strong>{{ $fmtImpact($top['dwellingImpact']) }}</strong>).
            </p>
            <p>
                Potensi dwelling {{ $dwDir }} hingga <strong>{{ $dwAbs }} hari</strong>.
                @if ($n['forecastDooringTotal'] !== null)
                    Estimasi dooring meningkat menjadi <strong>{{ $n['forecastDooringTotal'] }} hari</strong>
                    (Dwelling {{ $fmtImpact($top['dwellingImpact']) }} + Sailing Plan {{ $top['sailingPlan'] }} hari).
                @endif
            </p>
        </div>
    @elseif ($revisedCount === 0)
        <div class="rounded-lg border border-green-100 bg-green-50 px-4 py-3 mb-5 text-sm text-green-700">
            Tidak ada perubahan jadwal keberangkatan antara Draft dan Final.
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Kapal</th>
                    <th class="px-4 py-3 text-center">Draft ETD</th>
                    <th class="px-4 py-3 text-center">Final ETD</th>
                    <th class="px-4 py-3 text-center">Actual ATD</th>
                    <th class="px-4 py-3 text-center">Dwelling</th>
                    <th class="px-4 py-3 text-center">Sailing</th>
                    <th class="px-4 py-3 text-center">Dooring</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($rows as $row)
                    <tr class="hover:bg-gray-50">

                        {{-- Kapal --}}
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $row['vessel_name'] }}</div>
                            @if ($row['voyage_no'] !== '—')
                                <div class="text-xs text-gray-400">{{ $row['voyage_no'] }}</div>
                            @endif
                        </td>

                        {{-- Draft ETD --}}
                        <td class="px-4 py-3 text-center text-gray-600">
                            {{ $fmtDate($row['draftETD']) }}
                        </td>

                        {{-- Final ETD --}}
                        <td class="px-4 py-3 text-center {{ ($row['dwellingImpact'] ?? 0) != 0 ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">
                            {{ $fmtDate($row['finalETD']) }}
                            @if (($row['dwellingImpact'] ?? 0) != 0)
                                <span class="text-amber-400 ml-0.5">↺</span>
                            @endif
                        </td>

                        {{-- Actual ATD --}}
                        <td class="px-4 py-3 text-center text-gray-600">
                            {{ $fmtDate($row['actualATD']) }}
                        </td>

                        {{-- Dwelling Impact --}}
                        <td class="px-4 py-3 text-center {{ $impactClass($row['dwellingImpact']) }}">
                            {{ $fmtImpact($row['dwellingImpact']) }}
                        </td>

                        {{-- Sailing Impact --}}
                        <td class="px-4 py-3 text-center">
                            @if ($row['sailingImpact'] !== null)
                                <span class="{{ $impactClass($row['sailingImpact']) }}">{{ $fmtImpact($row['sailingImpact']) }}</span>
                            @elseif ($row['sailingPlan'] !== null)
                                <span class="text-gray-400 text-xs">Plan: {{ $row['sailingPlan'] }} hr</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Dooring Impact --}}
                        <td class="px-4 py-3 text-center">
                            @if ($row['dooringImpact'] !== null)
                                <span class="{{ $impactClass($row['dooringImpact']) }}">{{ $fmtImpact($row['dooringImpact']) }}</span>
                            @elseif (($row['dwellingImpact'] ?? 0) != 0)
                                <span class="text-amber-600 text-xs font-medium">Potensi {{ $fmtImpact($row['dwellingImpact']) }}</span>
                            @elseif ($row['dwellingImpact'] === null)
                                <span class="text-gray-400">—</span>
                            @else
                                <span class="text-gray-500">0 hari</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            @switch($row['status'])
                                @case('tinggi')
                                    <span class="inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Tinggi</span>
                                    @break
                                @case('sedang')
                                    <span class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Sedang</span>
                                    @break
                                @case('rendah')
                                    <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Rendah</span>
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

    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-400">
        <span><strong>Dwelling</strong> = Final ETD − Draft ETD</span>
        <span><strong>Sailing</strong> = Actual ATD − Final ETD</span>
        <span><strong>Dooring</strong> = Dwelling + Sailing</span>
        <span>Tinggi ≥ 4 hari &nbsp;·&nbsp; Sedang 2–3 hari &nbsp;·&nbsp; Rendah 0–1 hari</span>
    </div>

@endif

</div>

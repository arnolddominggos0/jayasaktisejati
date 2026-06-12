@php
/**
 * Tab: Schedule Analysis — Vessel Plan
 *
 * Analisa kualitas jadwal Final Voyage berdasarkan standar TAM.
 * Semua perhitungan murni dari ETD/ETA voyage — tanpa data shipment.
 *
 * Standar TAM:
 *   Dwelling  = 6 hari  (konstanta)
 *   Sailing   = 10 hari (standar benchmark)
 *   Dooring   = 3 hari  (konstanta)
 *   Lead Time = 19 hari (total standar)
 *
 * Sailing Schedule = voyage.eta - voyage.etd (satu-satunya variabel)
 * Lead Time Schedule = 6 + sailing + 3
 * Variance = lead_time - 19
 *
 * Status:
 *   ON STANDARD      = variance ≤ 0
 *   MINOR DEVIATION  = variance 1–2
 *   HIGH DEVIATION   = variance > 2
 */

$dwelling    = \App\Models\Voyage::TAM_DWELLING_STANDARD;   // 6
$sailingStd  = \App\Models\Voyage::TAM_SAILING_STANDARD;    // 10
$dooring     = \App\Models\Voyage::TAM_DOORING_STANDARD;    // 3
$ltStandard  = \App\Models\Voyage::TAM_LEAD_TIME_STANDARD;  // 19

$sailingSchedule = $v->sailing_schedule_days;
$ltSchedule      = $v->lead_time_schedule_days;
$variance        = $v->lead_time_variance;
$status          = $v->schedule_analysis_status;

// Variance sailing saja (untuk baris sailing di tabel)
$sailingVariance = $sailingSchedule !== null
    ? round($sailingSchedule - $sailingStd, 1)
    : null;

$hasSchedule = $v->etd && $v->eta;

// ── Helpers ────────────────────────────────────────────────────────────────
$fmtDays = fn(?float $d) => $d !== null ? number_format($d, 0) . ' hari' : '—';

$varLabel = function(?float $v): array {
    if ($v === null) return ['text' => '—', 'class' => 'text-gray-400'];
    if ($v === 0.0)  return ['text' => '±0', 'class' => 'text-gray-500'];
    if ($v > 0)      return ['text' => '+' . number_format($v, 1), 'class' => 'text-red-600 font-semibold'];
    return ['text' => number_format($v, 1), 'class' => 'text-emerald-600 font-semibold'];
};

$statusConfig = match ($status) {
    'on_standard'    => ['label' => 'ON STANDARD',     'bg' => 'bg-emerald-50',  'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
    'minor_deviation'=> ['label' => 'MINOR DEVIATION', 'bg' => 'bg-amber-50',    'border' => 'border-amber-200',   'text' => 'text-amber-700',   'dot' => 'bg-amber-400'],
    'high_deviation' => ['label' => 'HIGH DEVIATION',  'bg' => 'bg-red-50',      'border' => 'border-red-200',     'text' => 'text-red-700',     'dot' => 'bg-red-500'],
    default          => ['label' => 'BELUM ADA DATA',  'bg' => 'bg-gray-50',     'border' => 'border-gray-200',    'text' => 'text-gray-500',    'dot' => 'bg-gray-400'],
};
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
                Schedule Analysis
            </div>
            <p class="text-sm text-gray-500">
                Analisa kualitas jadwal voyage berdasarkan standar TAM.
                Baseline: <span class="font-semibold text-gray-700">Final Schedule</span>
                (voyage.etd / voyage.eta).
            </p>
        </div>

        {{-- Status badge --}}
        @if ($hasSchedule)
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border {{ $statusConfig['border'] }} {{ $statusConfig['bg'] }}">
            <div class="w-2 h-2 rounded-full {{ $statusConfig['dot'] }}"></div>
            <span class="text-sm font-bold {{ $statusConfig['text'] }}">
                {{ $statusConfig['label'] }}
            </span>
            @if ($variance !== null)
                <span class="text-xs {{ $statusConfig['text'] }} opacity-70">
                    ({{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }} hari)
                </span>
            @endif
        </div>
        @endif
    </div>

    @if (! $hasSchedule)
    {{-- No data --}}
    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
        <div class="text-sm text-gray-400 italic">
            ETD atau ETA belum tersedia. Analisa akan tampil setelah vessel plan difinalisasi.
        </div>
    </div>
    @else

    {{-- KPI Comparison Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Komponen
                    </th>
                    <th class="text-center px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Standar TAM
                    </th>
                    <th class="text-center px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Schedule
                    </th>
                    <th class="text-center px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Variance
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

                {{-- Dwelling --}}
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></div>
                            <span class="font-medium text-gray-700">Dwelling</span>
                        </div>
                        <div class="text-[11px] text-gray-400 ml-4 mt-0.5">
                            Konstanta TAM — tidak bervariasi per voyage
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-mono text-gray-600">
                        {{ $dwelling }} hari
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">
                        {{ $dwelling }} hari
                    </td>
                    <td class="px-4 py-3 text-center text-gray-400 text-xs">
                        ±0
                    </td>
                </tr>

                {{-- Sailing --}}
                <tr class="bg-gray-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></div>
                            <span class="font-medium text-gray-700">Sailing</span>
                        </div>
                        <div class="text-[11px] text-gray-400 ml-4 mt-0.5">
                            {{ optional($v->etd)->format('d M Y') }} → {{ optional($v->eta)->format('d M Y') }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-mono text-gray-600">
                        {{ $sailingStd }} hari
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">
                        {{ $fmtDays($sailingSchedule) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php $sv = $varLabel($sailingVariance); @endphp
                        <span class="text-sm {{ $sv['class'] }}">{{ $sv['text'] }}</span>
                    </td>
                </tr>

                {{-- Dooring --}}
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-violet-400 shrink-0"></div>
                            <span class="font-medium text-gray-700">Dooring</span>
                        </div>
                        <div class="text-[11px] text-gray-400 ml-4 mt-0.5">
                            Konstanta TAM — tidak bervariasi per voyage
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-mono text-gray-600">
                        {{ $dooring }} hari
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">
                        {{ $dooring }} hari
                    </td>
                    <td class="px-4 py-3 text-center text-gray-400 text-xs">
                        ±0
                    </td>
                </tr>

                {{-- Lead Time --}}
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-gray-700 shrink-0"></div>
                            <span class="font-bold text-gray-800">Total Lead Time</span>
                        </div>
                        <div class="text-[11px] text-gray-400 ml-4 mt-0.5">
                            Dwelling + Sailing + Dooring
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-mono font-bold text-gray-700">
                        {{ $ltStandard }} hari
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-gray-800">
                        {{ $fmtDays($ltSchedule) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php $lv = $varLabel($variance); @endphp
                        <span class="text-sm {{ $lv['class'] }}">{{ $lv['text'] }}</span>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    {{-- Formula note --}}
    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-xs text-gray-500 space-y-1">
        <div class="font-semibold text-gray-600 mb-1">Formula</div>
        <div>Lead Time Schedule = Dwelling ({{ $dwelling }}) + Sailing ({{ $fmtDays($sailingSchedule) }}) + Dooring ({{ $dooring }}) = <strong class="text-gray-700">{{ $fmtDays($ltSchedule) }}</strong></div>
        <div>Variance = Lead Time Schedule − Standard ({{ $ltStandard }}) = <strong class="{{ $variance !== null && $variance > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $variance !== null ? ($variance >= 0 ? '+' : '') . number_format($variance, 1) . ' hari' : '—' }}</strong></div>
    </div>

    @endif {{-- /hasSchedule --}}

</div>

@php
/**
 * Tab: Final Schedule Analysis — Vessel Plan
 *
 * Analisis kualitas jadwal per-vessel menggunakan FINAL SCHEDULE
 * (VesselPlanItem.planned_etd / planned_eta).
 *
 * Standar TAM:
 *   Dwelling  = 6 hari  (konstanta)
 *   Sailing   = 10 hari (benchmark)
 *   Dooring   = 3 hari  (konstanta)
 *   Lead Time = 19 hari (total standar = 6+10+3)
 *
 * Sailing per vessel = planned_eta - planned_etd
 * Lead Time          = 6 + sailing + 3
 * Variance           = lead_time - 19
 *
 * Status:
 *   ON STANDARD      = variance ≤ 0
 *   MINOR DEVIATION  = variance 1–2
 *   HIGH DEVIATION   = variance > 2
 */

use Carbon\Carbon;

$dwelling   = 6;
$sailingStd = 10;
$dooring    = 3;
$ltStd      = 19;

// ── Hitung analisis per item ────────────────────────────────────────────────
$rows = $items->map(function ($item) use ($dwelling, $dooring, $ltStd) {
    $sailing  = null;
    $lt       = null;
    $variance = null;
    $status   = 'unknown';

    if ($item->planned_etd && $item->planned_eta) {
        $sailing  = (int) $item->planned_etd->diffInDays($item->planned_eta);
        $lt       = $dwelling + $sailing + $dooring;
        $variance = $lt - $ltStd;
        $status   = match (true) {
            $variance <= 0 => 'on_standard',
            $variance <= 2 => 'minor_deviation',
            default        => 'high_deviation',
        };
    }

    return compact('item', 'sailing', 'lt', 'variance', 'status');
});

// ── Status config ───────────────────────────────────────────────────────────
$statusCfg = [
    'on_standard'     => ['label' => 'ON STANDARD',     'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
    'minor_deviation' => ['label' => 'MINOR DEVIATION', 'bg' => 'bg-amber-100',   'text' => 'text-amber-700'],
    'high_deviation'  => ['label' => 'HIGH DEVIATION',  'bg' => 'bg-red-100',     'text' => 'text-red-700'],
    'unknown'         => ['label' => '—',               'bg' => 'bg-gray-100',    'text' => 'text-gray-500'],
];

// ── Summary counts ──────────────────────────────────────────────────────────
$summary = [
    'on_standard'     => $rows->where('status', 'on_standard')->count(),
    'minor_deviation' => $rows->where('status', 'minor_deviation')->count(),
    'high_deviation'  => $rows->where('status', 'high_deviation')->count(),
    'sailing_avg'     => $rows->whereNotNull('sailing')->avg('sailing'),
];

$varLabel = function (?int $v): array {
    if ($v === null) return ['text' => '—',                          'class' => 'text-gray-400'];
    if ($v === 0)    return ['text' => '±0',                         'class' => 'text-gray-500'];
    if ($v > 0)      return ['text' => '+' . $v . ' hr',             'class' => 'text-red-600 font-semibold'];
    return                  ['text' => $v . ' hr',                   'class' => 'text-emerald-600 font-semibold'];
};
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
                Final Schedule Analysis
            </div>
            <p class="text-sm text-gray-500">
                Analisis per-vessel menggunakan
                <span class="font-semibold text-gray-700">Final Schedule</span>
                (planned_etd / planned_eta).
                Baseline standar TAM: Dwelling 6 + Sailing 10 + Dooring 3 = 19 hari.
            </p>
        </div>

        {{-- Summary chips --}}
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            @if ($summary['on_standard'] > 0)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                {{ $summary['on_standard'] }} On Standard
            </span>
            @endif
            @if ($summary['minor_deviation'] > 0)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                {{ $summary['minor_deviation'] }} Minor
            </span>
            @endif
            @if ($summary['high_deviation'] > 0)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                {{ $summary['high_deviation'] }} High
            </span>
            @endif
            @if ($summary['sailing_avg'] !== null)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs">
                Avg Sailing {{ number_format($summary['sailing_avg'], 1) }} hr
            </span>
            @endif
        </div>
    </div>

    {{-- Standards reminder --}}
    <div class="grid grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Dwelling', 'value' => '6 hari', 'note' => 'Konstanta TAM', 'color' => 'blue'],
            ['label' => 'Sailing',  'value' => '10 hari', 'note' => 'Benchmark TAM', 'color' => 'emerald'],
            ['label' => 'Dooring',  'value' => '3 hari',  'note' => 'Konstanta TAM', 'color' => 'violet'],
            ['label' => 'Lead Time','value' => '19 hari', 'note' => 'Total standar',  'color' => 'gray'],
        ] as $s)
        @php
            $cMap = [
                'blue'   => 'border-blue-100 bg-blue-50 text-blue-700',
                'emerald'=> 'border-emerald-100 bg-emerald-50 text-emerald-700',
                'violet' => 'border-violet-100 bg-violet-50 text-violet-700',
                'gray'   => 'border-gray-200 bg-gray-50 text-gray-700',
            ];
        @endphp
        <div class="rounded-xl border px-4 py-3 {{ $cMap[$s['color']] }}">
            <div class="text-[10px] uppercase tracking-wider font-bold opacity-70 mb-1">{{ $s['label'] }}</div>
            <div class="text-xl font-black">{{ $s['value'] }}</div>
            <div class="text-[11px] opacity-60 mt-0.5">{{ $s['note'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Analysis Table --}}
    @if ($rows->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
        <div class="text-sm text-gray-400 italic">Belum ada jadwal di vessel plan ini.</div>
    </div>
    @else
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-[11px] uppercase tracking-wider text-gray-400">
                    <th class="text-left px-4 py-3 font-bold">Vessel</th>
                    <th class="text-left px-4 py-3 font-bold">ETD</th>
                    <th class="text-left px-4 py-3 font-bold">ETA</th>
                    <th class="text-center px-3 py-3 font-bold">Dwelling</th>
                    <th class="text-center px-3 py-3 font-bold">Sailing</th>
                    <th class="text-center px-3 py-3 font-bold">Dooring</th>
                    <th class="text-center px-3 py-3 font-bold">Lead Time</th>
                    <th class="text-center px-3 py-3 font-bold">Variance</th>
                    <th class="text-center px-4 py-3 font-bold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $row)
                @php
                    $cfg = $statusCfg[$row['status']];
                    $vl  = $varLabel($row['variance']);
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-800">{{ $row['item']->vessel?->name ?? '—' }}</div>
                        @if ($row['item']->voyage_no)
                        <div class="text-[11px] text-gray-400 font-mono">{{ $row['item']->voyage_no }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $row['item']->planned_etd?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $row['item']->planned_eta?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-3 py-3 text-center text-gray-500">{{ $dwelling }}</td>
                    <td class="px-3 py-3 text-center font-semibold {{ $row['sailing'] !== null && $row['sailing'] > $sailingStd ? 'text-amber-600' : 'text-gray-800' }}">
                        {{ $row['sailing'] ?? '—' }}
                    </td>
                    <td class="px-3 py-3 text-center text-gray-500">{{ $dooring }}</td>
                    <td class="px-3 py-3 text-center font-bold text-gray-800">
                        {{ $row['lt'] ?? '—' }}
                    </td>
                    <td class="px-3 py-3 text-center text-sm {{ $vl['class'] }}">
                        {{ $vl['text'] }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if ($row['status'] !== 'unknown')
                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $cfg['bg'] }} {{ $cfg['text'] }}">
                            {{ $cfg['label'] }}
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Formula footer --}}
    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-xs text-gray-500">
        <span class="font-semibold text-gray-600">Formula:</span>
        Lead Time = Dwelling ({{ $dwelling }}) + Sailing (planned_eta − planned_etd) + Dooring ({{ $dooring }}) &nbsp;·&nbsp;
        Variance = Lead Time − {{ $ltStd }} hari standar &nbsp;·&nbsp;
        ON STANDARD ≤ 0 &nbsp;·&nbsp; MINOR ≤ 2 &nbsp;·&nbsp; HIGH > 2
    </div>
    @endif

</div>

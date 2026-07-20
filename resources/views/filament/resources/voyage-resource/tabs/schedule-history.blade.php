@php
/**
 * Tab: Schedule History Logbook — Voyage
 *
 * Source: voyage_schedule_histories (via Voyage::scheduleHistories HasMany).
 * Tiga versi jadwal:
 *   draft  — jadwal saat draft dikirim ke customer
 *   final  — jadwal saat vessel plan difinalisasi / disetujui TAM
 *   actual — waktu keberangkatan/tiba aktual (ATD / ATA)
 *
 * Highlight perubahan:
 *   Badge "Berubah" + delta hari muncul di sel ETD/ETA ketika nilai
 *   versi ini berbeda dari versi sebelumnya.
 *   Final dibandingkan ke Draft, Actual dibandingkan ke Final.
 *
 * Variance Matrix bawah (sailing days):
 *   Draft→Final  · Final→Actual  · Draft→Actual
 *
 * Eager loading:
 *   Sudah di-load di ViewVoyage::mount() via $this->record->load(['scheduleHistories']).
 *   Blade tidak memicu query tambahan.
 */

// ── Source data ───────────────────────────────────────────────────────────────
$__isVoyage = isset($v) && $v instanceof \App\Models\Voyage;

// Query langsung agar tidak bergantung pada state Livewire.
$histories = $__isVoyage
    ? \App\Models\VoyageScheduleHistory::where('voyage_id', $v->id)
        ->orderBy('schedule_type')
        ->get()
    : collect();

$draft  = $histories->firstWhere('schedule_type', 'draft');
$final  = $histories->firstWhere('schedule_type', 'final');
$actual = $histories->firstWhere('schedule_type', 'actual');

// ── Variance sailing_days antar versi ─────────────────────────────────────────
$varDraftFinal  = \App\Models\VoyageScheduleHistory::sailingVariance($draft,  $final);
$varFinalActual = \App\Models\VoyageScheduleHistory::sailingVariance($final,  $actual);
$varDraftActual = \App\Models\VoyageScheduleHistory::sailingVariance($draft,  $actual);

// ── Format helpers ─────────────────────────────────────────────────────────────
$fmtDt   = fn($dt)  => $dt  ? $dt->format('d M Y')                        : '—';
$fmtDays = fn($d)   => $d !== null ? number_format((float) $d, 1) . ' hr' : '—';

/**
 * Hitung delta hari antara dua datetime (int, bisa negatif).
 * Positif = versi baru lebih lambat.
 */
$deltaDay = function($baselineDt, $compareDt): ?int {
    if (! $baselineDt || ! $compareDt) return null;
    return (int) round($baselineDt->diffInSeconds($compareDt, false) / 86400);
};

/**
 * Render badge "Berubah" + delta hari.
 * Kembalikan array ['html' => string, 'changed' => bool].
 */
$changeBadge = function(?int $delta): array {
    if ($delta === null)   return ['html' => '', 'changed' => false];
    if ($delta === 0)      return ['html' => '', 'changed' => false];

    $abs  = abs($delta);
    $sign = $delta > 0 ? '+' : '-';

    // > 0 = mundur = merah; < 0 = maju = hijau
    [$bg, $text] = $delta > 0
        ? ['bg-red-50 border-red-200',           'text-red-700']
        : ['bg-emerald-50 border-emerald-200',   'text-emerald-700'];

    $label = $sign . $abs . ' hr';
    $html  = <<<HTML
<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded border text-[10px] font-bold {$bg} {$text} ml-1">
    {$label}
</span>
HTML;
    return ['html' => $html, 'changed' => true];
};

// ── Per-row delta computation ──────────────────────────────────────────────────
// Final dibandingkan ke Draft; Actual dibandingkan ke Final
$deltaFinalEtd  = $changeBadge($deltaDay(optional($draft)->etd,  optional($final)->etd));
$deltaFinalEta  = $changeBadge($deltaDay(optional($draft)->eta,  optional($final)->eta));
$deltaActualEtd = $changeBadge($deltaDay(optional($final)->etd,  optional($actual)->etd));
$deltaActualEta = $changeBadge($deltaDay(optional($final)->eta,  optional($actual)->eta));

// ── Row config ────────────────────────────────────────────────────────────────
$typeConfig = [
    'draft'  => [
        'dot'       => 'bg-blue-400',
        'label'     => 'Draft',
        'textColor' => 'text-blue-700',
        'rowBg'     => '',
        'deltaEtd'  => ['html' => '', 'changed' => false],
        'deltaEta'  => ['html' => '', 'changed' => false],
        'data'      => $draft,
    ],
    'final'  => [
        'dot'       => 'bg-emerald-500',
        'label'     => 'Final',
        'textColor' => 'text-emerald-700',
        'rowBg'     => $deltaFinalEtd['changed'] || $deltaFinalEta['changed'] ? 'bg-amber-50/40' : '',
        'deltaEtd'  => $deltaFinalEtd,
        'deltaEta'  => $deltaFinalEta,
        'data'      => $final,
    ],
    'actual' => [
        'dot'       => 'bg-violet-500',
        'label'     => 'Actual',
        'textColor' => 'text-violet-700',
        'rowBg'     => $deltaActualEtd['changed'] || $deltaActualEta['changed'] ? 'bg-amber-50/40' : '',
        'deltaEtd'  => $deltaActualEtd,
        'deltaEta'  => $deltaActualEta,
        'data'      => $actual,
    ],
];

// ── Variance sailing_days label helper ────────────────────────────────────────
$varLabel = function(?float $val): array {
    if ($val === null)  return ['text' => '—',                        'class' => 'text-gray-400'];
    if ($val === 0.0)   return ['text' => '±0',                       'class' => 'text-gray-500'];
    if ($val > 0)       return ['text' => '+'.number_format($val,1),  'class' => 'text-red-600 font-semibold'];
    return                     ['text' => number_format($val,1),       'class' => 'text-emerald-600 font-semibold'];
};

$hasAnyData = $draft || $final || $actual;

// Berapa banyak versi yang punya data?
$versionCount = collect([$draft, $final, $actual])->filter()->count();
@endphp


<div class="space-y-5">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
                Schedule History Logbook
            </div>
            <p class="text-sm text-gray-500">
                Histori tiga versi jadwal voyage:
                <span class="font-semibold text-blue-600">Draft</span>
                → <span class="font-semibold text-emerald-600">Final</span>
                → <span class="font-semibold text-violet-600">Actual</span>.
                Badge <span class="inline-flex items-center px-1.5 py-0.5 rounded border text-[10px] font-bold bg-red-50 border-red-200 text-red-700">+N hr</span>
                muncul bila jadwal berubah dari versi sebelumnya.
            </p>
        </div>

        {{-- Progress chip --}}
        <div class="shrink-0">
            @php
                $progressColor = match($versionCount) {
                    3 => 'bg-emerald-100 text-emerald-700',
                    2 => 'bg-amber-100 text-amber-700',
                    1 => 'bg-blue-100 text-blue-700',
                    default => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $progressColor }}">
                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                {{ $versionCount }}/3 versi tersedia
            </span>
        </div>
    </div>

    @if (! $hasAnyData)
    {{-- Empty state --}}
    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/>
            </svg>
        </div>
        <div class="text-sm font-medium text-gray-500">Belum ada data schedule history</div>
        <div class="mt-1 text-xs text-gray-400">
            Data akan tersedia setelah vessel plan difinalisasi dan voyage ATD/ATA diisi.
        </div>
    </div>
    @else

    {{-- ── Schedule Table ───────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold w-28">
                        Versi
                    </th>
                    <th class="text-left px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        ETD / ATD
                    </th>
                    <th class="text-left px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        ETA / ATA
                    </th>
                    <th class="text-center px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Sailing
                    </th>
                    <th class="text-left px-4 py-3 text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                        Dicatat
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

                @foreach ($typeConfig as $type => $cfg)
                @php
                    /** @var \App\Models\VoyageScheduleHistory|null $row */
                    $row    = $cfg['data'];
                    $hasRow = $row !== null;
                @endphp
                <tr class="transition-colors {{ $hasRow ? $cfg['rowBg'] : 'opacity-40 bg-white' }}">

                    {{-- Versi label --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }} shrink-0"></span>
                            <span class="font-semibold {{ $cfg['textColor'] }}">{{ $cfg['label'] }}</span>
                        </span>
                    </td>

                    {{-- ETD / ATD --}}
                    <td class="px-4 py-3">
                        <span class="font-mono {{ $hasRow && $row->etd ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $hasRow ? $fmtDt($row->etd) : '—' }}
                        </span>
                        @if ($cfg['deltaEtd']['changed'])
                            {!! $cfg['deltaEtd']['html'] !!}
                        @endif
                    </td>

                    {{-- ETA / ATA --}}
                    <td class="px-4 py-3">
                        <span class="font-mono {{ $hasRow && $row->eta ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $hasRow ? $fmtDt($row->eta) : '—' }}
                        </span>
                        @if ($cfg['deltaEta']['changed'])
                            {!! $cfg['deltaEta']['html'] !!}
                        @endif
                    </td>

                    {{-- Sailing Days --}}
                    <td class="px-4 py-3 text-center {{ $hasRow && $row->sailing_days ? 'font-semibold text-gray-800' : 'text-gray-400' }}">
                        {{ $hasRow ? $fmtDays($row->sailing_days) : '—' }}
                    </td>

                    {{-- Dicatat --}}
                    <td class="px-4 py-3 text-xs text-gray-400">
                        @if ($hasRow && $row->captured_at)
                            <span class="block">{{ $row->captured_at->format('d M Y') }}</span>
                            @if ($row->captured_by)
                                <span class="block text-gray-400 truncate max-w-[140px]"
                                      title="{{ $row->captured_by }}">
                                    {{ $row->captured_by }}
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </td>

                </tr>
                @endforeach

            </tbody>
        </table>
    </div>

    {{-- Legend badge --}}
    @if ($versionCount >= 2)
    <div class="flex items-center gap-3 flex-wrap text-[11px] text-gray-400 px-1">
        <span>Keterangan perubahan ETD/ETA:</span>
        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded border bg-red-50 border-red-200 text-red-700 font-bold">+N hr</span>
        <span>mundur dari versi sebelumnya</span>
        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded border bg-emerald-50 border-emerald-200 text-emerald-700 font-bold">-N hr</span>
        <span>maju dari versi sebelumnya</span>
    </div>
    @endif

    {{-- ── Variance Matrix (Sailing Days) ─────────────────────────────────── --}}
    @if ($versionCount >= 2)
    <div>
        <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-3">
            Variance Sailing Days
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

            {{-- Draft → Final --}}
            <div class="rounded-xl border {{ ($varDraftFinal !== null && $varDraftFinal !== 0.0) ? 'border-amber-100 bg-amber-50/60' : 'border-gray-100 bg-gray-50' }} p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
                    <span class="text-xs font-semibold text-gray-600">Draft → Final</span>
                    <span class="ml-auto">
                        @php $vl1 = $varLabel($varDraftFinal); @endphp
                        <span class="text-base {{ $vl1['class'] }}">{{ $vl1['text'] }}</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Perubahan sailing akibat revisi sebelum persetujuan TAM
                </div>
            </div>

            {{-- Final → Actual --}}
            <div class="rounded-xl border {{ ($varFinalActual !== null && $varFinalActual !== 0.0) ? 'border-amber-100 bg-amber-50/60' : 'border-gray-100 bg-gray-50' }} p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-xs font-semibold text-gray-600">Final → Actual</span>
                    <span class="ml-auto">
                        @php $vl2 = $varLabel($varFinalActual); @endphp
                        <span class="text-base {{ $vl2['class'] }}">{{ $vl2['text'] }}</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Deviasi operasional dari jadwal yang sudah disetujui
                </div>
            </div>

            {{-- Draft → Actual --}}
            <div class="rounded-xl border {{ ($varDraftActual !== null && $varDraftActual !== 0.0) ? 'border-amber-100 bg-amber-50/60' : 'border-gray-100 bg-gray-50' }} p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-violet-500 shrink-0"></span>
                    <span class="text-xs font-semibold text-gray-600">Draft → Actual</span>
                    <span class="ml-auto">
                        @php $vl3 = $varLabel($varDraftActual); @endphp
                        <span class="text-base {{ $vl3['class'] }}">{{ $vl3['text'] }}</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Total deviasi dari rencana awal ke realisasi
                </div>
            </div>

        </div>
    </div>
    @endif

    @endif {{-- /hasAnyData --}}

</div>

@php
/**
 * Tab: Schedule History Logbook — Vessel Plan
 *
 * Tiga versi jadwal per voyage:
 *   Draft  = jadwal saat draft dikirim (dari VesselPlanSnapshot)
 *   Final  = jadwal saat vessel plan disetujui (= voyage.etd/eta)
 *   Actual = waktu keberangkatan/tiba aktual (= voyage.atd_at/ata_at)
 *
 * Actual disimpan auto di voyage_schedule_histories saat atd_at & ata_at diisi.
 *
 * Variance Matrix:
 *   Draft → Final   : perubahan sailing akibat revisi jadwal
 *   Final → Actual  : deviasi operasional dari jadwal yang disetujui
 *   Draft → Actual  : total deviasi dari rencana awal
 */

$histories = $v->relationLoaded('scheduleHistories')
    ? $v->scheduleHistories
    : collect();

$draft  = $histories->firstWhere('schedule_type', 'draft');
$final  = $histories->firstWhere('schedule_type', 'final');
$actual = $histories->firstWhere('schedule_type', 'actual');

// Variance sailing_days antar versi
$varDraftFinal  = \App\Models\VoyageScheduleHistory::sailingVariance($draft,  $final);
$varFinalActual = \App\Models\VoyageScheduleHistory::sailingVariance($final,  $actual);
$varDraftActual = \App\Models\VoyageScheduleHistory::sailingVariance($draft,  $actual);

// ── Helpers ────────────────────────────────────────────────────────────────
$fmtDt   = fn($dt) => $dt ? $dt->format('d M Y') : '—';
$fmtDays = fn($d) => $d !== null ? number_format((float) $d, 1) . ' hr' : '—';

$varLabel = function(?float $v): array {
    if ($v === null)  return ['text' => '—',                     'class' => 'text-gray-400'];
    if ($v === 0.0)   return ['text' => '±0',                    'class' => 'text-gray-500'];
    if ($v > 0)       return ['text' => '+'.number_format($v,1), 'class' => 'text-red-600 font-semibold'];
    return             ['text' => number_format($v,1),            'class' => 'text-emerald-600 font-semibold'];
};

$typeConfig = [
    'draft'  => ['dot' => 'bg-blue-400',   'label' => 'Draft',  'textColor' => 'text-blue-700'],
    'final'  => ['dot' => 'bg-emerald-500','label' => 'Final',  'textColor' => 'text-emerald-700'],
    'actual' => ['dot' => 'bg-violet-500', 'label' => 'Actual', 'textColor' => 'text-violet-700'],
];

$hasAnyData = $draft || $final || $actual;
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div>
        <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
            Schedule History Logbook
        </div>
        <p class="text-sm text-gray-500">
            Histori tiga versi jadwal voyage: Draft → Final → Actual.
            Data akan terisi otomatis saat vessel plan difinalisasi dan ketika
            ATD & ATA voyage diisi.
        </p>
    </div>

    @if (! $hasAnyData)
    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
        <div class="text-sm text-gray-400 italic">
            Belum ada data schedule history.
            Data akan tersedia setelah vessel plan difinalisasi.
        </div>
    </div>
    @else

    {{-- Schedule Table --}}
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

                @foreach (['draft' => $draft, 'final' => $final, 'actual' => $actual] as $type => $row)
                @php
                    $cfg = $typeConfig[$type];
                    $hasRow = $row !== null;
                @endphp
                <tr class="{{ $hasRow ? '' : 'opacity-40' }}">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }} shrink-0"></span>
                            <span class="font-semibold {{ $cfg['textColor'] }}">{{ $cfg['label'] }}</span>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono {{ $hasRow && $row->etd ? 'text-gray-800' : 'text-gray-400' }}">
                        {{ $hasRow ? $fmtDt($row->etd) : '—' }}
                    </td>
                    <td class="px-4 py-3 font-mono {{ $hasRow && $row->eta ? 'text-gray-800' : 'text-gray-400' }}">
                        {{ $hasRow ? $fmtDt($row->eta) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center {{ $hasRow && $row->sailing_days ? 'font-semibold text-gray-800' : 'text-gray-400' }}">
                        {{ $hasRow ? $fmtDays($row->sailing_days) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400">
                        @if ($hasRow && $row->captured_at)
                            {{ $row->captured_at->format('d M Y') }}
                            @if ($row->captured_by)
                                <span class="block">{{ $row->captured_by }}</span>
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

    {{-- Variance Matrix --}}
    <div>
        <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-3">
            Variance Sailing Days
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

            {{-- Draft → Final --}}
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    <span class="text-xs font-semibold text-gray-600">Draft → Final</span>
                    <span class="ml-auto">
                        @php $v1 = $varLabel($varDraftFinal); @endphp
                        <span class="text-base {{ $v1['class'] }}">{{ $v1['text'] }}</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Perubahan jadwal akibat revisi sebelum persetujuan TAM
                </div>
            </div>

            {{-- Final → Actual --}}
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-semibold text-gray-600">Final → Actual</span>
                    <span class="ml-auto">
                        @php $v2 = $varLabel($varFinalActual); @endphp
                        <span class="text-base {{ $v2['class'] }}">{{ $v2['text'] }}</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Deviasi operasional dari jadwal yang sudah disetujui
                </div>
            </div>

            {{-- Draft → Actual --}}
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                    <span class="text-xs font-semibold text-gray-600">Draft → Actual</span>
                    <span class="ml-auto">
                        @php $v3 = $varLabel($varDraftActual); @endphp
                        <span class="text-base {{ $v3['class'] }}">{{ $v3['text'] }}</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Total deviasi dari rencana awal ke realisasi
                </div>
            </div>

        </div>
    </div>

    @endif {{-- /hasAnyData --}}

</div>

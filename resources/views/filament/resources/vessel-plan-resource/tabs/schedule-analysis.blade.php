@php
/**
 * Tab 2 — Planning Analysis (Decision Context: Analyze)
 *
 * Canon v1.1 mapping:
 *   Axiom 3 (Information)  — hanya planning-domain facts: planned ETD/ETA/ETB, sailing, gap
 *   Axiom 4 (DC+Disclosure)— Analysis DC hanya menerima planning Recognition
 *   Axiom 5 (Recognition)  — Planner mengenali gap, conflict, SOP violation, readiness
 *   Axiom 12 (Directedness)— perhatian diarahkan ke planning analysis, tidak didistraksi oleh operational KPI
 *
 * Sprint 12.2 — Planning Analysis Workspace.
 * Legacy operational constants (dwelling/dooring/lead time/variance) telah dihapus dari tab ini.
 * Backend legacy artifacts dipertahankan sampai Sprint 12.5.
 */

$analysis       = $record->analyze();
$gaps           = $analysis['gaps']           ?? [];
$violations     = $analysis['violations']     ?? [];
$conflicts      = $analysis['conflicts']      ?? [];
$gapLimit       = $analysis['gap_limit']      ?? 6;
$riskLevel      = $analysis['risk_level']     ?? 'valid';
$gapOk          = $analysis['gap_ok']         ?? true;
$scheduleCount  = $analysis['schedule_count'] ?? 0;
$sailingAvg     = $analysis['sailing_avg']    ?? 0;
$maxGap         = $analysis['max_gap']        ?? 0;

$riskLabel = match ($riskLevel) {
    'valid'    => 'VALID',
    'warning'  => 'PERINGATAN',
    'critical' => 'KRITIS',
    default    => '—',
};

$riskStyle = match ($riskLevel) {
    'valid'    => ['text' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
    'warning'  => ['text' => 'text-amber-700',   'bg' => 'bg-amber-50',   'border' => 'border-amber-200',   'dot' => 'bg-amber-400'],
    'critical' => ['text' => 'text-red-700',     'bg' => 'bg-red-50',     'border' => 'border-red-200',     'dot' => 'bg-red-500'],
    default    => ['text' => 'text-gray-600',    'bg' => 'bg-gray-50',    'border' => 'border-gray-200',    'dot' => 'bg-gray-400'],
};

$gapStatus = function (?int $gap) use ($gapLimit): ?array {
    if ($gap === null) return null;
    if ($gap > 10)          return ['label' => 'KRITIS',     'class' => 'text-red-700',     'bg' => 'bg-red-100'];
    if ($gap > $gapLimit)   return ['label' => 'PERINGATAN', 'class' => 'text-amber-700',   'bg' => 'bg-amber-100'];
    return                     ['label' => 'OK',         'class' => 'text-emerald-700', 'bg' => 'bg-emerald-100'];
};

$isDraft     = $record->isDraft();
$isRevision  = $record->isRevision();
$isSent      = $record->isSent();
$isFinal     = $record->isFinal();

$hasItems        = $scheduleCount > 0;
$hasCustomer     = filled($record->customer_id ?? null);
$hasWhatsapp     = $record->hasWhatsappRecipient();
$allHaveVoyageNo = $items->every(fn ($i) => filled($i->voyage_no));
$hasRoutePorts   = filled($record->pol_id) && filled($record->pod_id);

$submitChecks = [
    ['label' => 'Jadwal kapal telah diisi',         'pass' => $hasItems],
    ['label' => 'Customer TAM terhubung',           'pass' => $hasCustomer],
    ['label' => 'Nomor WhatsApp tersedia',           'pass' => $hasWhatsapp],
];

$finalizeChecks = [
    ['label' => 'Semua kapal memiliki No Voyage',    'pass' => $allHaveVoyageNo],
    ['label' => 'Route ports (POL/POD) terisi',      'pass' => $hasRoutePorts],
];

$recommendation = null;
if ($isDraft || $isRevision) {
    $allPass = collect($submitChecks)->every(fn ($c) => $c['pass']);
    if ($allPass && $riskLevel === 'valid') {
        $recommendation = ['text' => 'Plan siap dikirim ke TAM', 'type' => 'success'];
    } elseif ($allPass && $riskLevel === 'warning') {
        $recommendation = ['text' => 'Plan dapat dikirim dengan catatan: terdapat peringatan SOP', 'type' => 'warning'];
    } elseif ($allPass && $riskLevel === 'critical') {
        $recommendation = ['text' => 'Plan memiliki risiko SOP tinggi — pertimbangkan revisi sebelum dikirim', 'type' => 'danger'];
    } else {
        $recommendation = ['text' => 'Plan belum siap dikirim — lengkapi persyaratan di bawah', 'type' => 'danger'];
    }
} elseif ($isSent) {
    $allPass = collect($finalizeChecks)->every(fn ($c) => $c['pass']);
    if ($allPass) {
        $recommendation = ['text' => 'Plan siap difinalisasi', 'type' => 'success'];
    } else {
        $recommendation = ['text' => 'Plan belum siap difinalisasi — lengkapi persyaratan di bawah', 'type' => 'danger'];
    }
}

$recStyle = match ($recommendation['type'] ?? null) {
    'success' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800'],
    'warning' => ['bg' => 'bg-amber-50',   'border' => 'border-amber-200',   'text' => 'text-amber-800'],
    'danger'  => ['bg' => 'bg-red-50',     'border' => 'border-red-200',     'text' => 'text-red-800'],
    default   => null,
};

$fmtDate = fn ($d) => $d ? $d->translatedFormat('d M Y') : '—';
@endphp

<div class="space-y-5">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">
                Planning Analysis
            </div>
            <p class="text-sm text-gray-500">
                Analisis jadwal berdasarkan <span class="font-semibold text-gray-700">planning domain</span>:
                gap antar kapal, konflik ETD/ETA, validasi SOP, dan planning readiness.
            </p>
        </div>
    </div>

    {{-- ── Planning Summary (4 metric cards) ─────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Jumlah Jadwal</div>
            <div class="text-xl font-black text-gray-800">{{ $scheduleCount }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">kapal pada periode ini</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Avg Sailing</div>
            <div class="text-xl font-black text-gray-800">{{ $sailingAvg }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">hari (planned ETA − ETD)</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Max ETD Gap</div>
            <div class="text-xl font-black {{ $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $maxGap }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">hari / target ≤ {{ $gapLimit }} hari</div>
        </div>

        <div class="rounded-xl border {{ $riskStyle['border'] }} {{ $riskStyle['bg'] }} px-4 py-3">
            <div class="text-[10px] uppercase tracking-wider font-bold opacity-70 mb-1">Risiko SOP</div>
            <div class="text-xl font-black {{ $riskStyle['text'] }}">{{ $riskLabel }}</div>
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="w-1.5 h-1.5 rounded-full {{ $riskStyle['dot'] }}"></span>
                <span class="text-[11px] {{ $riskStyle['text'] }} opacity-80">
                    @if ($riskLevel === 'valid')
                        Gap dalam batas SOP
                    @elseif ($riskLevel === 'warning')
                        Gap melebihi target SOP
                    @else
                        Gap sangat tinggi
                    @endif
                </span>
            </div>
        </div>

    </div>

    {{-- ── Gap Analysis Table ─────────────────────────────────────────────── --}}
    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
            <div class="text-sm text-gray-400 italic">Belum ada jadwal kapal pada vessel plan ini.</div>
            <div class="text-xs text-gray-400 mt-1">Tambahkan jadwal di Tab Jadwal untuk mulai menganalisis.</div>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="text-left px-4 py-3 font-bold">Kapal</th>
                        <th class="text-left px-3 py-3 font-bold">ETD</th>
                        <th class="text-left px-3 py-3 font-bold">ETA</th>
                        <th class="text-left px-3 py-3 font-bold">ETB</th>
                        <th class="text-center px-3 py-3 font-bold">Sailing</th>
                        <th class="text-center px-3 py-3 font-bold">ETD Gap</th>
                        <th class="text-center px-4 py-3 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($items as $i => $item)
                        @php
                            $gap = $gaps[$item->id] ?? null;
                            $st  = $gapStatus($gap);
                            $sailing = $item->planned_sailing_days;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            {{-- Vessel --}}
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $item->vessel?->name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">
                                    @if ($item->voyage_no)<span class="font-mono">{{ $item->voyage_no }}</span> · @endif
                                    {{ $item->shippingLine?->name ?? '—' }}
                                </div>
                            </td>

                            {{-- ETD --}}
                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">
                                {{ $fmtDate($item->planned_etd) }}
                            </td>

                            {{-- ETA --}}
                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">
                                {{ $fmtDate($item->planned_eta) }}
                            </td>

                            {{-- ETB (optional) --}}
                            <td class="px-3 py-3 text-gray-500 whitespace-nowrap">
                                {{ $fmtDate($item->planned_etb) }}
                            </td>

                            {{-- Sailing days --}}
                            <td class="px-3 py-3 text-center font-semibold text-gray-800">
                                {{ $sailing !== null ? $sailing . ' hr' : '—' }}
                            </td>

                            {{-- ETD Gap --}}
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                @if ($gap === null)
                                    <span class="text-gray-300 text-xs">—</span>
                                @else
                                    <span class="font-semibold {{ $st['class'] }}">{{ $gap }} hari</span>
                                @endif
                            </td>

                            {{-- Gap status badge --}}
                            <td class="px-4 py-3 text-center">
                                @if ($st === null)
                                    <span class="text-gray-300 text-xs">—</span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $st['bg'] }} {{ $st['class'] }}">
                                        {{ $st['label'] }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Gap formula footer --}}
        <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-xs text-gray-500">
            <span class="font-semibold text-gray-600">ETD Gap</span> = selisih hari ETD kapal sebelumnya → kapal ini.
            Target SOP: ≤ {{ $gapLimit }} hari.
            <span class="text-red-600 font-semibold">KRITIS</span> > 10 hari ·
            <span class="text-amber-600 font-semibold">PERINGATAN</span> > {{ $gapLimit }} hari ·
            <span class="text-emerald-600 font-semibold">OK</span> ≤ {{ $gapLimit }} hari
        </div>
    @endif

    {{-- ── ETD/ETA Conflicts ──────────────────────────────────────────────── --}}
    @if (!empty($conflicts))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-bold text-red-700 uppercase tracking-wider">Konflik ETD/ETA</div>
            </div>
            <ul class="space-y-1.5">
                @foreach ($conflicts as $conflict)
                    <li class="text-sm text-red-800 flex items-start gap-2">
                        <span class="text-red-400 mt-0.5">•</span>
                        <span>{{ $conflict }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── SOP Violations ─────────────────────────────────────────────────── --}}
    @if (!empty($violations))
        <div class="rounded-xl border {{ $riskLevel === 'critical' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }} px-4 py-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 {{ $riskLevel === 'critical' ? 'text-red-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-bold {{ $riskLevel === 'critical' ? 'text-red-700' : 'text-amber-700' }} uppercase tracking-wider">SOP Violations</div>
            </div>
            <ul class="space-y-1.5">
                @foreach ($violations as $violation)
                    <li class="text-sm {{ $riskLevel === 'critical' ? 'text-red-800' : 'text-amber-800' }} flex items-start gap-2">
                        <span class="{{ $riskLevel === 'critical' ? 'text-red-400' : 'text-amber-400' }} mt-0.5">•</span>
                        <span>{{ $violation }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Planning Readiness ─────────────────────────────────────────────── --}}
    @if ($isFinal)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold text-emerald-800">
                    Plan telah difinalisasi
                    @if ($record->finalized_at)
                        pada {{ $record->finalized_at->translatedFormat('d M Y H:i') }}
                    @endif
                </span>
            </div>
        </div>
    @else
        {{-- Recommendation banner --}}
        @if ($recommendation && $recStyle)
            <div class="rounded-xl border {{ $recStyle['border'] }} {{ $recStyle['bg'] }} px-4 py-3 mb-1">
                <div class="flex items-center gap-2">
                    @if ($recommendation['type'] === 'success')
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @elseif ($recommendation['type'] === 'warning')
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                    <span class="text-sm font-semibold {{ $recStyle['text'] }}">{{ $recommendation['text'] }}</span>
                </div>
            </div>
        @endif

        {{-- Readiness checklist --}}
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-4">
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 mb-3">
                @if ($isDraft || $isRevision)
                    Persyaratan Submit ke TAM
                @elseif ($isSent)
                    Persyaratan Finalisasi
                @endif
            </div>

            <div class="space-y-2.5">
                @php
                    $checks = ($isDraft || $isRevision) ? $submitChecks : ($isSent ? $finalizeChecks : []);
                @endphp

                @foreach ($checks as $check)
                    <div class="flex items-center gap-2.5">
                        @if ($check['pass'])
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm text-gray-700">{{ $check['label'] }}</span>
                        @else
                            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-sm text-gray-500">{{ $check['label'] }}</span>
                        @endif
                    </div>
                @endforeach

                {{-- SOP status as informational --}}
                <div class="flex items-center gap-2.5 pt-2.5 border-t border-gray-100">
                    @if ($riskLevel === 'valid')
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-gray-700">SOP status: <span class="font-semibold text-emerald-700">VALID</span></span>
                    @elseif ($riskLevel === 'warning')
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700">SOP status: <span class="font-semibold text-amber-700">PERINGATAN</span> — dapat dikirim, tetapi perlu perhatian</span>
                    @else
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700">SOP status: <span class="font-semibold text-red-700">KRITIS</span> — disarankan revisi sebelum dikirim</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>

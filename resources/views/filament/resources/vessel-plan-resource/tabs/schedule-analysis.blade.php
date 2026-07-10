@php
/**
 * Tab 2 — Decision Review Workspace (Decision Context: Review)
 *
 * Canon v1.1 mapping:
 *   Axiom 3 (Information)  — hanya planning-domain facts: planned ETD/ETA/ETB, sailing, gap
 *   Axiom 4 (DC+Disclosure)— Review DC hanya menerima planning-domain Recognition
 *   Axiom 5 (Recognition)  — Planner mengenali gap, SOP violation, invalid chronology,
 *                             missing sailing/voyage, planning readiness
 *   Axiom 6 (Behavior Judgment) — setiap Recognition menghasilkan keputusan yang jelas
 *   Axiom 12 (Directedness)— perhatian diarahkan ke exception, bukan tabel detail
 *
 * Icon hanya dipakai untuk makna semantik (✓ / ⚠ / ✕), tidak dekoratif.
 * Tidak ada KPI / card / widget / summary tambahan di luar yang sudah ada.
 */

$analysis       = $record->analyze();
$gaps           = $analysis['gaps']           ?? [];
$violations     = $analysis['violations']     ?? [];
$gapLimit       = $analysis['gap_limit']      ?? 6;
$riskLevel      = $analysis['risk_level']     ?? 'valid';
$gapOk          = $analysis['gap_ok']         ?? true;
$scheduleCount  = $analysis['schedule_count'] ?? 0;
$sailingAvg     = $analysis['sailing_avg']    ?? 0;
$maxGap         = $analysis['max_gap']        ?? 0;

$gapWarnings      = $analysis['gap_warnings']      ?? [];
$chronologyIssues = $analysis['chronology_issues'] ?? [];
$missingSailing   = $analysis['missing_sailing']   ?? [];
$missingVoyage    = $analysis['missing_voyage']    ?? [];
$readiness        = $analysis['readiness']         ?? ['ready' => false, 'reasons' => []];

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
    ['label' => 'Jadwal kapal telah disusun',  'pass' => $hasItems],
    ['label' => 'Customer TAM terhubung',      'pass' => $hasCustomer],
    ['label' => 'Nomor WhatsApp tersedia',      'pass' => $hasWhatsapp],
];

$finalizeChecks = [
    ['label' => 'Voyage tersedia',              'pass' => $allHaveVoyageNo],
    ['label' => 'POL/POD lengkap',              'pass' => $hasRoutePorts],
    ['label' => 'ETD Gap sesuai SOP',           'pass' => $gapOk],
];

$planReady  = $readiness['ready'] ?? false;
$submitPass = collect($submitChecks)->every(fn ($c) => $c['pass']);
$finalizePass = collect($finalizeChecks)->every(fn ($c) => $c['pass']);

// ── Executive Summary ──────────────────────────────────────────────────
$isEditable = $isDraft || $isRevision;

$execTitle     = null;
$execNarrative = null;
$execBullets   = [];
$execReady     = false;

if ($isFinal) {
    // Final plans get their own banner; exec summary skipped.
} elseif ($isEditable) {
    if ($submitPass && $planReady) {
        $execTitle     = 'Plan siap dikirim ke TAM';
        $execNarrative = 'Seluruh persyaratan submit telah terpenuhi.';
        $execBullets   = [
            $scheduleCount . ' jadwal diverifikasi',
            'ETD Gap sesuai SOP',
            'Data wajib lengkap',
        ];
        $execReady     = true;
    } else {
        $execTitle     = 'Plan belum siap dikirim';
        $execNarrative = 'Masih terdapat item yang perlu diperbaiki.';
        if (! empty($missingVoyage))    $execBullets[] = count($missingVoyage) . ' kapal belum memiliki Voyage';
        if (! empty($gapWarnings))      $execBullets[] = count($gapWarnings) . ' ETD Gap melebihi SOP';
        if (! empty($missingSailing))   $execBullets[] = count($missingSailing) . ' Sailing Days belum terisi';
        if (! empty($chronologyIssues)) $execBullets[] = count($chronologyIssues) . ' ETA tidak valid';
        if (! $submitPass)              $execBullets[] = 'Persyaratan submit belum lengkap';
    }
} elseif ($isSent) {
    if ($finalizePass && $planReady) {
        $execTitle     = 'Plan siap difinalisasi';
        $execNarrative = 'Seluruh persyaratan finalisasi telah terpenuhi.';
        $execBullets   = [
            $scheduleCount . ' jadwal diverifikasi',
            'ETD Gap sesuai SOP',
            'Data wajib lengkap',
        ];
        $execReady     = true;
    } else {
        $execTitle     = 'Plan belum siap difinalisasi';
        $execNarrative = 'Masih terdapat item yang perlu diperbaiki.';
        if (! empty($missingVoyage))    $execBullets[] = count($missingVoyage) . ' kapal belum memiliki Voyage';
        if (! empty($gapWarnings))      $execBullets[] = count($gapWarnings) . ' ETD Gap melebihi SOP';
        if (! empty($missingSailing))   $execBullets[] = count($missingSailing) . ' Sailing Days belum terisi';
        if (! empty($chronologyIssues)) $execBullets[] = count($chronologyIssues) . ' ETA tidak valid';
        if (! $finalizePass)            $execBullets[] = 'Persyaratan finalisasi belum lengkap';
    }
}

$execStyle = $execReady
    ? ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800', 'narrative' => 'text-emerald-700', 'bullet' => 'text-emerald-600']
    : ['bg' => 'bg-red-50',     'border' => 'border-red-200',     'text' => 'text-red-800',     'narrative' => 'text-red-700',     'bullet' => 'text-red-500'];

// ── Decision Summary ──────────────────────────────────────────────────
$statusPlanLabel = match (true) {
    $isFinal                      => 'Final',
    $isEditable && $execReady      => 'Siap Submit',
    $isSent && $execReady          => 'Siap Finalisasi',
    $isEditable || $isSent         => 'Perlu Perbaikan',
    default                        => '—',
};

$statusBadgeStyle = match ($statusPlanLabel) {
    'Final', 'Siap Submit', 'Siap Finalisasi' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'Perlu Perbaikan'                          => 'bg-amber-100 text-amber-700 border-amber-200',
    default                                      => 'bg-gray-100 text-gray-600 border-gray-200',
};

// ETD Gap value color for card
$gapCardColor = $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600');

// ── Exception First: dikelompokkan per kapal, bukan per jenis masalah ────
// Build flat issue list, lalu group by vessel name supaya Planner langsung
// tahu harus buka baris mana di Daftar Jadwal.
$exceptionIssues = [];
$exceptionIssues[] = ['vessel_raw' => $chronologyIssues, 'severity' => 'critical', 'label' => 'ETA tidak valid'];
$exceptionIssues[] = ['vessel_raw' => $gapWarnings,      'severity' => 'warning',  'labelBuilder' => fn ($w) => 'ETD Gap ' . $w['gap'] . ' hari (melebihi SOP)'];
$exceptionIssues[] = ['vessel_raw' => $missingSailing,   'severity' => 'warning',  'labelBuilder' => fn ($m) => $m['field'] . ' belum diisi'];
$exceptionIssues[] = ['vessel_raw' => $missingVoyage,     'severity' => 'warning',  'label' => 'Voyage belum dipilih'];

$groupedExceptions = [];
foreach ($chronologyIssues as $c) {
    $groupedExceptions[$c['vessel']][] = ['severity' => 'critical', 'label' => 'ETA tidak valid'];
}
foreach ($gapWarnings as $w) {
    $groupedExceptions[$w['vessel']][] = ['severity' => $w['severity'], 'label' => 'ETD Gap ' . $w['gap'] . ' hari (melebihi SOP)'];
}
foreach ($missingSailing as $m) {
    $groupedExceptions[$m['vessel']][] = ['severity' => 'warning', 'label' => $m['field'] . ' belum diisi'];
}
foreach ($missingVoyage as $m) {
    $groupedExceptions[$m['vessel']][] = ['severity' => 'warning', 'label' => 'Voyage belum dipilih'];
}

// Order vessels by severity priority (critical first)
$exceptionVessels = [];
foreach ($groupedExceptions as $vessel => $issues) {
    $hasCritical = collect($issues)->contains(fn ($i) => $i['severity'] === 'critical');
    $exceptionVessels[] = [
        'vessel'       => $vessel,
        'issues'       => $issues,
        'hasCritical'  => $hasCritical,
    ];
}
usort($exceptionVessels, fn ($a, $b) => $b['hasCritical'] <=> $a['hasCritical']);

$exceptionVesselCount = count($exceptionVessels);
$hasExceptions  = $exceptionVesselCount > 0;

$fmtDate = fn ($d) => $d ? $d->translatedFormat('d M Y') : '—';
@endphp

<div class="space-y-2.5">

    {{-- ── Header — Review Jadwal ─────────────────────────────────────────── --}}
    @php
        $reviewSubtitle = match (true) {
            $isFinal         => 'Hasil akhir jadwal yang telah disetujui.',
            $isSent          => 'Verifikasi jadwal final sebelum difinalisasi.',
            $isEditable      => 'Ringkasan kesiapan jadwal sebelum dikirim ke TAM.',
            default          => 'Ringkasan kesiapan jadwal.',
        };
    @endphp
    <div>
        <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1">Review Jadwal</div>
        <p class="text-sm text-gray-500">{{ $reviewSubtitle }}</p>
    </div>

    {{-- ── 1. Executive Summary (compact) ─────────────────────────────────── --}}
    @if ($isFinal)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm font-semibold text-emerald-800">
                    Plan telah difinalisasi
                    @if ($record->finalized_at)
                        pada {{ $record->finalized_at->translatedFormat('d M Y H:i') }}
                    @endif
                </span>
            </div>
        </div>
    @elseif ($execTitle)
        <div class="rounded-xl border {{ $execStyle['border'] }} {{ $execStyle['bg'] }} px-4 py-2.5">
            <div class="flex items-start gap-2.5">
                @if ($execReady)
                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-bold {{ $execStyle['text'] }}">
                        @if ($execReady) @else @endif {{ $execTitle }}
                    </div>

                    @if ($execNarrative)
                        <div class="text-xs {{ $execStyle['narrative'] }} mt-0.5">{{ $execNarrative }}</div>
                    @endif

                    @if (! empty($execBullets))
                        <ul class="mt-1 space-y-0.5">
                            @foreach ($execBullets as $bullet)
                                <li class="flex items-start gap-2 text-xs {{ $execStyle['text'] }}">
                                    <span class="{{ $execStyle['bullet'] }} mt-0.5">•</span>
                                    <span>{{ $bullet }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ── 2. Decision Summary (4 cards, Status = small badge) ────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-2.5">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Total Jadwal</div>
            <div class="text-xl font-black text-gray-800">{{ $scheduleCount }} <span class="text-sm font-semibold text-gray-400">kapal</span></div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-2.5">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Rata-rata Sailing</div>
            <div class="text-xl font-black text-gray-800">{{ $sailingAvg }} <span class="text-sm font-semibold text-gray-400">hari</span></div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-2.5">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Gap ETD Maksimum</div>
            <div class="text-xl font-black {{ $gapCardColor }}">
                {{ $maxGap }} <span class="text-sm font-semibold text-gray-400">hari</span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 flex flex-col justify-between">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1">Status Plan</div>
            <div>
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadgeStyle }} whitespace-nowrap">
                    {{ $statusPlanLabel }}
                </span>
            </div>
        </div>

    </div>

    {{-- ── 3. Exception First (grouped per-vessel, actionable) ─────────────── --}}
    @if ($hasItems)
        @if (! $hasExceptions)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-emerald-800">
                        <span class="font-semibold">Tidak ada exception.</span>
                        Seluruh jadwal memenuhi aturan planning.
                    </span>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5">
                <div class="flex items-start gap-2 mb-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-amber-800">
                        {{ $exceptionVesselCount }} jadwal memerlukan perhatian.
                    </span>
                </div>
                <div class="ml-6 space-y-2">
                    @foreach ($exceptionVessels as $v)
                        @php
                            $vesselSeverity = $v['hasCritical'] ? 'critical' : 'warning';
                            $vesselClass    = $vesselSeverity === 'critical' ? 'text-red-700' : 'text-amber-700';
                            $iconChar       = $vesselSeverity === 'critical' ? '✕' : '⚠';
                            $iconColor      = $vesselSeverity === 'critical' ? 'text-red-500' : 'text-amber-500';
                        @endphp
                        <div>
                            <div class="text-xs font-semibold {{ $vesselClass }}">
                                <span class="{{ $iconColor }}">{{ $iconChar }}</span>
                                {{ $v['vessel'] }}
                            </div>
                            <ul class="ml-4 mt-0.5 space-y-0.5">
                                @foreach ($v['issues'] as $issue)
                                    @php
                                        $isCrit    = $issue['severity'] === 'critical';
                                        $issueIcon = $isCrit ? '✕' : '•';
                                        $issueColor = $isCrit ? 'text-red-500' : 'text-amber-500';
                                        $issueText  = $isCrit ? 'text-red-700' : 'text-amber-700';
                                    @endphp
                                    <li class="flex items-start gap-1.5 text-xs {{ $issueText }}">
                                        <span class="{{ $issueColor }} mt-0.5">{{ $issueIcon }}</span>
                                        <span>{{ $issue['label'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- ── 4. Tabel Jadwal (Supporting Information) ────────────────────────── --}}
    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
            <div class="text-sm text-gray-500">Belum ada jadwal untuk direview.</div>
        </div>
    @else
        <div>
            <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 mb-1.5">Daftar Jadwal</div>
            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] uppercase tracking-wider text-gray-400">
                            <th class="text-left px-4 py-2 font-bold">Kapal</th>
                            <th class="text-left px-3 py-2 font-bold">ETD</th>
                            <th class="text-left px-3 py-2 font-bold">ETA</th>
                            <th class="text-left px-3 py-2 font-bold">ETB</th>
                            <th class="text-center px-3 py-2 font-bold">Sailing</th>
                            <th class="text-center px-3 py-2 font-bold">ETD Gap</th>
                            <th class="text-center px-4 py-2 font-bold">Perhatian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($items as $i => $item)
                            @php
                                $gap    = $gaps[$item->id] ?? null;
                                $sailing = $item->planned_sailing_days;
                                $etaInvalid = $item->planned_eta && $item->planned_etd
                                    && $item->planned_eta <= $item->planned_etd;

                                $exceptions = [];
                                if ($etaInvalid) {
                                    $exceptions[] = ['label' => 'ETA tidak valid', 'class' => 'text-red-700', 'bg' => 'bg-red-100', 'icon' => '✕'];
                                }
                                if ($gap !== null && $gap > 10) {
                                    $exceptions[] = ['label' => 'Gap SOP', 'class' => 'text-red-700', 'bg' => 'bg-red-100', 'icon' => '⚠'];
                                } elseif ($gap !== null && $gap > $gapLimit) {
                                    $exceptions[] = ['label' => 'Gap SOP', 'class' => 'text-amber-700', 'bg' => 'bg-amber-100', 'icon' => '⚠'];
                                }
                                if ($sailing === null) {
                                    $exceptions[] = ['label' => 'Sailing kosong', 'class' => 'text-amber-700', 'bg' => 'bg-amber-100', 'icon' => '⚠'];
                                }
                                if (! filled($item->voyage_no)) {
                                    $exceptions[] = ['label' => 'Voyage belum dipilih', 'class' => 'text-amber-700', 'bg' => 'bg-amber-100', 'icon' => '⚠'];
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                {{-- Vessel — typography hierarchy: name (semibold gray-800) > Voyage (gray-500 xs) > Shipping Line (gray-400 xs) --}}
                                <td class="px-4 py-2">
                                    <div class="font-semibold text-gray-800">{{ $item->vessel?->name ?? '—' }}</div>
                                    <div class="text-[11px] mt-0.5">
                                        @if ($item->voyage_no)
                                            <span class="text-gray-500 font-mono">V.{{ $item->voyage_no }}</span>
                                            @if ($item->shippingLine?->name)
                                                <span class="text-gray-300 mx-1">·</span>
                                            @endif
                                        @endif
                                        @if ($item->shippingLine?->name)
                                            <span class="text-gray-400">{{ $item->shippingLine->name }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- ETD --}}
                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">
                                    {{ $fmtDate($item->planned_etd) }}
                                </td>

                                {{-- ETA --}}
                                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">
                                    {{ $fmtDate($item->planned_eta) }}
                                </td>

                                {{-- ETB (optional) --}}
                                <td class="px-3 py-2 text-gray-500 whitespace-nowrap">
                                    {{ $fmtDate($item->planned_etb) }}
                                </td>

                                {{-- Sailing — "Belum diisi" (abu) when empty, no dash --}}
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @if ($sailing !== null)
                                        <span class="font-semibold text-gray-800">{{ $sailing }} hari</span>
                                    @else
                                        <span class="text-xs text-gray-400">Belum diisi</span>
                                    @endif
                                </td>

                                {{-- ETD Gap — semantic color tier: green ≤6 / amber 7-9 / red ≥10 --}}
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    @if ($gap === null)
                                        <span class="text-gray-300 text-xs">—</span>
                                    @elseif ($gap > 10)
                                        <span class="font-semibold text-red-700">{{ $gap }} hari</span>
                                    @elseif ($gap > $gapLimit)
                                        <span class="font-semibold text-amber-700">{{ $gap }} hari</span>
                                    @else
                                        <span class="font-semibold text-emerald-700">{{ $gap }} hari</span>
                                    @endif
                                </td>

                                {{-- Perhatian — exception badges only, "—" abu when normal --}}
                                <td class="px-4 py-2 text-center">
                                    @if (empty($exceptions))
                                        <span class="text-gray-300 text-xs">—</span>
                                    @else
                                        <div class="flex flex-col items-end gap-1">
                                            @foreach ($exceptions as $ex)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $ex['bg'] }} {{ $ex['class'] }} whitespace-nowrap">
                                                    <span>{{ $ex['icon'] }}</span>
                                                    <span>{{ $ex['label'] }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tanpa penjelasan warna — badge di kolom ETD Gap sudah menjelaskan. --}}
            <div class="text-[11px] text-gray-500 mt-1.5">
                Target ETD Gap ≤ {{ $gapLimit }} hari antar keberangkatan kapal.
            </div>
        </div>
    @endif

    {{-- ── 5. Checklist (diagnostic-only — tampilkan hanya saat ada gate gagal) ── --}}
    @if (! $isFinal && (($isEditable && ! $submitPass) || ($isSent && ! $finalizePass)))
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs px-1">
            <span class="text-[11px] uppercase tracking-wider font-bold text-gray-400">
                @if ($isDraft || $isRevision)
                    Checklist Submit
                @elseif ($isSent)
                    Checklist Finalisasi
                @endif
            </span>
            @php
                $checks = ($isDraft || $isRevision) ? $submitChecks : ($isSent ? $finalizeChecks : []);
            @endphp
            @foreach ($checks as $check)
                <span class="inline-flex items-center gap-1.5">
                    @if ($check['pass'])
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">{{ $check['label'] }}</span>
                    @else
                        <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-gray-500">{{ $check['label'] }}</span>
                    @endif
                </span>
            @endforeach
        </div>
    @endif

</div>
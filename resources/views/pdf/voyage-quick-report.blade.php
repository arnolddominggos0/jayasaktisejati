@php
    $bgBrand = $brand['color'] ?? '#0137A1';

    // Status badge HTML for inline use inside table cells
    $stBadge = function(string $st): string {
        return match ($st) {
            'OK'   => '<span style="background:#dcfce7;color:#14532d;padding:1px 6px;border-radius:3px;font-size:7.5px;font-weight:700;">OK</span>',
            'LATE' => '<span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:3px;font-size:7.5px;font-weight:700;">NG</span>',
            default => '<span style="color:#9ca3af;font-size:8px;">&#8211;</span>',
        };
    };

    $achieveColor = function(float $pct): string {
        return $pct >= 85 ? '#14532d' : ($pct >= 60 ? '#92400e' : '#991b1b');
    };

    $fmtDelay = function($val): string {
        if ($val === null) return '&#8211;';
        $sign  = $val > 0 ? '+' : '';
        $color = $val > 0 ? '#dc2626' : '#16a34a';
        return "<span style=\"color:{$color};font-weight:700;\">{$sign}" . number_format($val, 2) . ' hari</span>';
    };

    $ngRows  = array_values(array_filter($kpi_rows, fn($r) => $r['lt_status'] === 'LATE'));
    $allRows = array_values($kpi_rows);

    $safeStr = function ($v, string $d = '&#8211;'): string {
        return (isset($v) && $v !== null && $v !== '') ? e((string) $v) : $d;
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Quick Report {{ $voyage->code ?? ('Voyage ' . ($voyage->voyage_no ?? '')) }} – {{ $voyage->vessel?->name ?? '' }}</title>
<style>
    @page { margin: 14mm 11mm 12mm 11mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 9px;
        color: #1f2937;
        line-height: 1.55;
    }

    /* ── Header ───────────────────────────────── */
    .doc-header {
        border-bottom: 3px solid {{ $bgBrand }};
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .company-name {
        font-size: 16px;
        font-weight: 900;
        color: {{ $bgBrand }};
        line-height: 1.2;
    }
    .doc-title-box {
        background: {{ $bgBrand }};
        color: #fff;
        padding: 8px 14px;
        border-radius: 6px;
        text-align: center;
    }
    .doc-title    { font-size: 13px; font-weight: 900; letter-spacing: 1px; }
    .doc-subtitle { font-size: 8.5px; margin-top: 2px; opacity: 0.9; }
    .doc-date     { font-size: 8px; margin-top: 4px; opacity: 0.75; }

    /* ── Section container ────────────────────── */
    .section { margin-bottom: 14px; }
    .section-header {
        background: {{ $bgBrand }};
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px 4px 0 0;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .section-body {
        border: 1px solid #d1d5db;
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 10px;
    }
    .section-body-flush {
        border: 1px solid #d1d5db;
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 0;
    }

    /* ── Info rows ────────────────────────────── */
    .info-tbl { width: 100%; border-collapse: collapse; }
    .info-tbl td { padding: 3px 6px; vertical-align: top; }
    .lbl { color: #6b7280; font-size: 8px; font-weight: 700; text-transform: uppercase; width: 130px; }
    .val { font-weight: 600; font-size: 9px; }

    /* ── KPI table ────────────────────────────── */
    .kpi-tbl { width: 100%; border-collapse: collapse; font-size: 7.5px; }
    .kpi-tbl thead th {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        padding: 4px 5px;
        text-align: center;
        font-weight: 700;
        color: #374151;
        font-size: 7px;
        text-transform: uppercase;
    }
    .kpi-tbl tbody td {
        border: 1px solid #e5e7eb;
        padding: 3px 5px;
        text-align: center;
    }
    .kpi-tbl tbody td.left-cell { text-align: left; font-family: monospace; font-size: 7px; }
    .kpi-tbl tbody tr:nth-child(even) td { background: #f9fafb; }
    .kpi-tbl tbody tr.ng-row td { background: #fff7f7; }

    /* ── Root-cause boxes ─────────────────────── */
    .rc-box {
        border-left: 4px solid #ef4444;
        background: #fff7ed;
        padding: 8px 12px;
        margin-bottom: 8px;
        border-radius: 0 4px 4px 0;
    }
    .rc-box.ok {
        border-left-color: #22c55e;
        background: #f0fdf4;
    }
    .rc-label { font-size: 8px; font-weight: 800; text-transform: uppercase; color: #991b1b; margin-bottom: 3px; }
    .rc-box.ok .rc-label { color: #166534; }
    .rc-text  { font-size: 8.5px; color: #374151; line-height: 1.6; }

    /* ── Impact table ─────────────────────────── */
    .impact-tbl { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8.5px; }
    .impact-tbl thead th {
        background: #374151;
        color: #fff;
        padding: 5px 8px;
        font-size: 8px;
        text-align: center;
    }
    .impact-tbl thead th.left-cell { text-align: left; }
    .impact-tbl tbody td {
        border: 1px solid #d1d5db;
        padding: 5px 8px;
        text-align: center;
    }
    .impact-tbl tbody td.left-cell { text-align: left; font-weight: 600; }
    .impact-tbl tbody tr.total-row td {
        background: #fef9c3;
        font-weight: 700;
        font-size: 9px;
        border-top: 2px solid #d97706;
    }

    /* ── Countermeasure boxes ─────────────────── */
    .cm-box {
        padding: 7px 10px;
        background: #eff6ff;
        border-left: 3px solid #3b82f6;
        border-radius: 0 4px 4px 0;
        margin-bottom: 7px;
    }
    .cm-phase  { font-size: 8px; font-weight: 800; color: #1d4ed8; text-transform: uppercase; margin-bottom: 2px; }
    .cm-action { font-size: 8.5px; color: #1e3a8a; line-height: 1.6; }

    /* ── Achievement table ────────────────────── */
    .ach-tbl { width: 100%; border-collapse: collapse; font-size: 8.5px; }
    .ach-tbl thead th {
        background: #1e40af;
        color: #fff;
        padding: 5px 8px;
        font-size: 8px;
        text-align: center;
    }
    .ach-tbl thead th.left-cell { text-align: left; }
    .ach-tbl tbody td {
        border: 1px solid #d1d5db;
        padding: 5px 8px;
        text-align: center;
        vertical-align: middle;
    }
    .ach-tbl tbody td.left-cell { text-align: left; font-weight: 700; }
    .ach-tbl tbody tr.total-row td {
        background: #eff6ff;
        border-top: 2px solid #3b82f6;
        font-weight: 700;
    }
    .ach-pct { font-size: 15px; font-weight: 800; }

    /* ── Achievement summary strip ────────────── */
    .ach-summary { margin-top: 10px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; background: #f8fafc; }
    .ach-summary-tbl { width: 100%; border-collapse: collapse; }
    .ach-summary-tbl td { text-align: center; padding: 6px 10px; border-right: 1px solid #e5e7eb; }
    .ach-summary-tbl td:last-child { border-right: none; }
    .ach-big { font-size: 22px; font-weight: 800; }
    .ach-lbl { font-size: 7.5px; color: #6b7280; margin-top: 2px; }

    /* ── Evidence ─────────────────────────────── */
    .ev-list { list-style: none; padding: 0; margin: 0 0 12px 0; }
    .ev-list li {
        padding: 5px 6px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 8.5px;
    }
    .ev-list li:last-child { border-bottom: none; }
    .ev-badge {
        display: inline-block;
        width: 22px;
        border-radius: 3px;
        text-align: center;
        font-size: 6.5px;
        font-weight: 700;
        padding: 1px 0;
        margin-right: 5px;
    }
    .ev-badge.pdf { background: #fee2e2; color: #991b1b; }
    .ev-badge.img { background: #dcfce7; color: #14532d; }
    .ev-badge.oth { background: #f3f4f6; color: #6b7280; }
    .ev-img-wrap { margin-top: 10px; margin-bottom: 4px; text-align: center; }
    .ev-img      { max-width: 100%; max-height: 190mm; border: 1px solid #d1d5db; border-radius: 3px; }
    .ev-caption  { font-size: 7.5px; color: #6b7280; text-align: center; margin-bottom: 10px; }
    .ev-pdf-note { color: #3b82f6; font-size: 8px; margin-left: 6px; font-style: italic; }

    /* ── Sign-off ─────────────────────────────── */
    .sign-tbl { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .sign-tbl td {
        border: 1px solid #d1d5db;
        padding: 8px 10px;
        text-align: center;
        vertical-align: top;
        width: 33.33%;
    }
    .sign-role  { font-size: 7.5px; text-transform: uppercase; font-weight: 700; color: #6b7280; }
    .sign-space { height: 48px; }
    .sign-line  { border-top: 1px solid #9ca3af; padding-top: 4px; font-size: 8.5px; font-weight: 600; }
    .sign-dept  { font-size: 7px; color: #9ca3af; margin-top: 2px; }

    /* ── Footer (fixed) ───────────────────────── */
    .page-footer {
        position: fixed;
        bottom: -8mm;
        left: 0; right: 0;
        border-top: 1px solid #e5e7eb;
        padding: 2px 11mm;
        font-size: 7px;
        color: #9ca3af;
        text-align: right;
    }

    /* ── Utility ──────────────────────────────── */
    .page-break { page-break-before: always; }
    .sub-title  { font-size: 8px; font-weight: 700; color: #374151; text-transform: uppercase; margin-bottom: 5px; margin-top: 10px; }
    .badge-ok   { background:#dcfce7;color:#14532d;padding:1px 5px;border-radius:3px;font-size:7.5px;font-weight:700; }
    .badge-ng   { background:#fee2e2;color:#991b1b;padding:1px 5px;border-radius:3px;font-size:7.5px;font-weight:700; }
    .badge-pend { color:#9ca3af;font-size:8px; }
</style>
</head>
<body>

{{-- PAGE FOOTER (repeats on every page) --}}
<div class="page-footer">
    {{ $brand['name'] }} &bull; {{ $qr_ref }} &bull; Quick Report Evaluasi Voyage &bull; Dicetak: {{ $generated_at }}
</div>

{{-- DOCUMENT HEADER --}}
<div class="doc-header">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:55%; vertical-align:middle;">
                @if($brand['logo'])
                    <img src="{{ $brand['logo'] }}" style="height:34px; margin-bottom:4px;" />
                @endif
                <div class="company-name">{{ $brand['name'] }}</div>
                <div style="font-size:8px;color:#6b7280;">Jasa Pengiriman Otomotif &bull; Moda Laut</div>
            </td>
            <td style="width:22%; vertical-align:top; text-align:right; padding-right:10px;">
                <div style="font-size:7px;color:#9ca3af;">No. Referensi:</div>
                <div style="font-size:9.5px;font-weight:700;color:#374151;">{{ $qr_ref }}</div>
                <div style="margin-top:5px;">{!! $qr_svg !!}</div>
            </td>
            <td style="width:23%; vertical-align:top;">
                <div class="doc-title-box">
                    <div class="doc-title">QUICK REPORT</div>
                    <div class="doc-subtitle">EVALUASI VOYAGE</div>
                    <div class="doc-date">{{ $generated_at }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- A. INFORMASI DASAR --}}
<div class="section">
    <div class="section-header">A. Informasi Dasar</div>
    <div class="section-body">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="width:50%;vertical-align:top;">
                    <table class="info-tbl">
                        <tr>
                            <td class="lbl">Nama Kapal</td>
                            <td class="val">{{ $voyage->vessel?->name ?? '&#8211;' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Voyage Code</td>
                            <td class="val" style="font-weight:800;color:{{ $bgBrand }};font-size:10px;font-family:monospace;">
                                {{ $voyage->code ?? '&#8211;' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Voyage No</td>
                            <td class="val" style="font-size:9px;color:#6b7280;">
                                {{ $voyage->voyage_no ?? '&#8211;' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Periode</td>
                            <td class="val">{{ $period }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Service / Rute</td>
                            <td class="val">{{ $voyage->service ?? '&#8211;' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%;vertical-align:top;">
                    <table class="info-tbl">
                        <tr>
                            <td class="lbl">ETD</td>
                            <td class="val">{{ $voyage->etd?->format('d M Y') ?? '&#8211;' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">ATA (Aktual Tiba)</td>
                            <td class="val">{{ $voyage->ata_at?->format('d M Y') ?? '&#8211;' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Total Unit Dievaluasi</td>
                            <td class="val">
                                <strong>{{ $total_units }}</strong> unit
                                @if($total_shipments !== $total_units)
                                    ({{ $total_shipments }} shipment)
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Nomor QR</td>
                            <td class="val" style="color:{{ $bgBrand }};font-weight:700;">{{ $qr_ref }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- B. DETAIL MASALAH --}}
<div class="section">
    <div class="section-header">
        B. Detail Masalah &mdash; Unit Berstatus NG
        ({{ count($ngRows) }} / {{ $total_units }} unit)
    </div>
    <div class="section-body-flush">
        @if(count($ngRows) > 0)
        <table class="kpi-tbl">
            <thead>
                <tr>
                    <th style="width:18px;">#</th>
                    <th style="text-align:left;">No Rangka</th>
                    <th style="text-align:left;">No Mesin</th>
                    <th>
                        Dwelling<br>
                        <span style="font-weight:400;color:#6b7280;">≤&nbsp;{{ $thresholds['dwelling_days'] ?? 6 }}&nbsp;hr</span>
                    </th>
                    <th>St</th>
                    <th>
                        Sailing<br>
                        <span style="font-weight:400;color:#6b7280;">≤&nbsp;{{ $thresholds['sailing_days'] ?? 10 }}&nbsp;hr</span>
                    </th>
                    <th>St</th>
                    <th>
                        Dooring<br>
                        <span style="font-weight:400;color:#6b7280;">≤&nbsp;{{ $thresholds['dooring_days'] ?? 3 }}&nbsp;hr</span>
                    </th>
                    <th>St</th>
                    <th>LT Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ngRows as $idx => $unit)
                <tr class="ng-row">
                    <td>{{ $idx + 1 }}</td>
                    <td class="left-cell">{{ $unit['identifier'] }}</td>
                    <td class="left-cell">{{ $unit['engine_no'] }}</td>
                    <td>{{ $unit['dwelling'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['dwelling_st']) !!}</td>
                    <td>{{ $unit['sailing'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['sailing_st']) !!}</td>
                    <td>{{ $unit['dooring'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['dooring_st']) !!}</td>
                    <td style="font-weight:700;color:#dc2626;">{{ $unit['lt_total'] ?? '&#8211;' }}</td>
                    <td><span class="badge-ng">NG</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="padding:14px;text-align:center;color:#16a34a;font-weight:600;font-size:9px;">
            &#10003;&nbsp; Seluruh unit berstatus OK — Tidak ada unit NG pada voyage ini.
        </div>
        @endif
    </div>
</div>

{{-- ALL UNITS SUMMARY (compact) --}}
@if(count($allRows) > 0 && count($ngRows) < count($allRows))
<div class="section">
    <div class="section-header" style="background:#374151;">
        Lampiran B — Ringkasan Seluruh Unit ({{ count($allRows) }} unit)
    </div>
    <div class="section-body-flush">
        <table class="kpi-tbl">
            <thead>
                <tr>
                    <th style="width:18px;">#</th>
                    <th style="text-align:left;">No Rangka</th>
                    <th>Dwelling</th>
                    <th>St</th>
                    <th>Sailing</th>
                    <th>St</th>
                    <th>Dooring</th>
                    <th>St</th>
                    <th>LT Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allRows as $idx => $unit)
                <tr @if($unit['lt_status'] === 'LATE') class="ng-row" @endif>
                    <td>{{ $idx + 1 }}</td>
                    <td class="left-cell">{{ $unit['identifier'] }}</td>
                    <td>{{ $unit['dwelling'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['dwelling_st']) !!}</td>
                    <td>{{ $unit['sailing'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['sailing_st']) !!}</td>
                    <td>{{ $unit['dooring'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['dooring_st']) !!}</td>
                    <td style="font-weight:700;">{{ $unit['lt_total'] ?? '&#8211;' }}</td>
                    <td>{!! $stBadge($unit['lt_status']) !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- C. ANALISIS MASALAH --}}
<div class="section page-break">
    <div class="section-header">C. Analisis Masalah &amp; Root Cause</div>
    <div class="section-body">

        @if(count($root_causes) > 0)
            @foreach($root_causes as $phase => $text)
            <div class="rc-box">
                <div class="rc-label">
                    @if($phase === 'dwelling') &#x1F7E5;&nbsp; Dwelling — Gate In &#8594; Kapal Berangkat
                    @elseif($phase === 'sailing') &#x1F7E0;&nbsp; Sailing — Kapal Berangkat &#8594; Kapal Tiba
                    @elseif($phase === 'dooring') &#x1F7E6;&nbsp; Dooring — Kapal Tiba &#8594; Pengiriman ke Tujuan Akhir
                    @else {{ ucfirst($phase) }}
                    @endif
                </div>
                <div class="rc-text">{{ $text }}</div>
            </div>
            @endforeach
        @else
        <div class="rc-box ok">
            <div class="rc-label">&#x2705;&nbsp; Tidak Ada Keterlambatan Terdeteksi</div>
            <div class="rc-text">
                Seluruh fase lead time (Dwelling, Sailing, Dooring) berjalan sesuai target KPI.
                Tidak ditemukan root cause keterlambatan pada voyage ini.
            </div>
        </div>
        @endif

        {{-- KPI Impact ──────────────────────────────────────────────────── --}}
        <div class="sub-title" style="margin-top:14px;">KPI Impact — Rata-rata Aktual vs Target</div>
        <table class="impact-tbl">
            <thead>
                <tr>
                    <th class="left-cell" style="width:22%;">Fase</th>
                    <th>Target (hari)</th>
                    <th>Avg Aktual (hari)</th>
                    <th>Selisih / Impact</th>
                    <th>Unit OK</th>
                    <th>Unit NG</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="left-cell">Dwelling</td>
                    <td>{{ $thresholds['dwelling_days'] ?? 6 }}</td>
                    <td>{{ $avg_dwelling !== null ? number_format($avg_dwelling, 2) : '&#8211;' }}</td>
                    <td>{!! $fmtDelay($dw_delay) !!}</td>
                    <td><span class="badge-ok">{{ $dw_ok }}</span></td>
                    <td><span class="badge-ng">{{ $dw_ng }}</span></td>
                </tr>
                <tr>
                    <td class="left-cell">Sailing</td>
                    <td>{{ $thresholds['sailing_days'] ?? 10 }}</td>
                    <td>{{ $avg_sailing !== null ? number_format($avg_sailing, 2) : '&#8211;' }}</td>
                    <td>{!! $fmtDelay($sa_delay) !!}</td>
                    <td><span class="badge-ok">{{ $sa_ok }}</span></td>
                    <td><span class="badge-ng">{{ $sa_ng }}</span></td>
                </tr>
                <tr>
                    <td class="left-cell">Dooring</td>
                    <td>{{ $thresholds['dooring_days'] ?? 3 }}</td>
                    <td>{{ $avg_dooring !== null ? number_format($avg_dooring, 2) : '&#8211;' }}</td>
                    <td>{!! $fmtDelay($do_delay) !!}</td>
                    <td><span class="badge-ok">{{ $do_ok }}</span></td>
                    <td><span class="badge-ng">{{ $do_ng }}</span></td>
                </tr>
                <tr class="total-row">
                    <td class="left-cell">TOTAL IMPACT</td>
                    <td>
                        {{ ($thresholds['dwelling_days'] ?? 6)
                           + ($thresholds['sailing_days'] ?? 10)
                           + ($thresholds['dooring_days'] ?? 3) }}
                    </td>
                    <td>{{ $avg_lt !== null ? number_format($avg_lt, 2) : '&#8211;' }}</td>
                    <td>
                        @if($total_delay !== null)
                            @php $sign = $total_delay > 0 ? '+' : ''; $col = $total_delay > 0 ? '#dc2626' : '#16a34a'; @endphp
                            <span style="color:{{ $col }};font-weight:800;font-size:9.5px;">
                                {{ $sign }}{{ number_format($total_delay, 2) }} hari terhadap overall leadtime
                            </span>
                        @else
                            &#8211;
                        @endif
                    </td>
                    <td><span class="badge-ok">{{ $ov_ok }}</span></td>
                    <td><span class="badge-ng">{{ $ov_ng }}</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- D. TINDAKAN PERBAIKAN --}}
<div class="section">
    <div class="section-header">D. Tindakan Perbaikan (Countermeasure)</div>
    <div class="section-body">
        @foreach($countermeasures as $i => $cm)
        <div class="cm-box">
            <div class="cm-phase">{{ $i + 1 }}. {{ $cm['phase'] }}</div>
            <div class="cm-action">{{ $cm['action'] }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- E. ACHIEVEMENT --}}
<div class="section page-break">
    <div class="section-header">E. Achievement</div>
    <div class="section-body">

        <table class="ach-tbl">
            <thead>
                <tr>
                    <th class="left-cell" style="width:18%;">Fase</th>
                    <th>Target</th>
                    <th>Avg Aktual</th>
                    <th>Unit OK</th>
                    <th>Unit NG</th>
                    <th>Achievement</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- Dwelling --}}
                <tr>
                    <td class="left-cell">Dwelling</td>
                    <td>&#8804; {{ $thresholds['dwelling_days'] ?? 6 }} hari</td>
                    <td>{{ $avg_dwelling !== null ? number_format($avg_dwelling, 2) . ' hr' : '&#8211;' }}</td>
                    <td><span class="badge-ok">{{ $dw_ok }}</span></td>
                    <td><span class="badge-ng">{{ $dw_ng }}</span></td>
                    <td>
                        <span class="ach-pct" style="color:{{ $achieveColor($achieve_dw) }};">
                            {{ $achieve_dw }}%
                        </span>
                    </td>
                    <td>
                        @if($achieve_dw >= 85)
                            <span class="badge-ok">OK</span>
                        @else
                            <span class="badge-ng">NG</span>
                        @endif
                    </td>
                </tr>
                {{-- Sailing --}}
                <tr>
                    <td class="left-cell">Sailing</td>
                    <td>&#8804; {{ $thresholds['sailing_days'] ?? 10 }} hari</td>
                    <td>{{ $avg_sailing !== null ? number_format($avg_sailing, 2) . ' hr' : '&#8211;' }}</td>
                    <td><span class="badge-ok">{{ $sa_ok }}</span></td>
                    <td><span class="badge-ng">{{ $sa_ng }}</span></td>
                    <td>
                        <span class="ach-pct" style="color:{{ $achieveColor($achieve_sa) }};">
                            {{ $achieve_sa }}%
                        </span>
                    </td>
                    <td>
                        @if($achieve_sa >= 85)
                            <span class="badge-ok">OK</span>
                        @else
                            <span class="badge-ng">NG</span>
                        @endif
                    </td>
                </tr>
                {{-- Dooring --}}
                <tr>
                    <td class="left-cell">Dooring</td>
                    <td>&#8804; {{ $thresholds['dooring_days'] ?? 3 }} hari</td>
                    <td>{{ $avg_dooring !== null ? number_format($avg_dooring, 2) . ' hr' : '&#8211;' }}</td>
                    <td><span class="badge-ok">{{ $do_ok }}</span></td>
                    <td><span class="badge-ng">{{ $do_ng }}</span></td>
                    <td>
                        <span class="ach-pct" style="color:{{ $achieveColor($achieve_do) }};">
                            {{ $achieve_do }}%
                        </span>
                    </td>
                    <td>
                        @if($achieve_do >= 85)
                            <span class="badge-ok">OK</span>
                        @else
                            <span class="badge-ng">NG</span>
                        @endif
                    </td>
                </tr>
                {{-- Overall --}}
                <tr class="total-row">
                    <td class="left-cell" style="color:{{ $bgBrand }};">Overall Voyage</td>
                    <td style="font-size:8.5px;">—</td>
                    <td>{{ $avg_lt !== null ? number_format($avg_lt, 2) . ' hr' : '&#8211;' }}</td>
                    <td><span class="badge-ok" style="font-size:9px;">{{ $ov_ok }}</span></td>
                    <td><span class="badge-ng" style="font-size:9px;">{{ $ov_ng }}</span></td>
                    <td>
                        <span class="ach-pct" style="font-size:20px;color:{{ $achieveColor($achieve_ov) }};">
                            {{ $achieve_ov }}%
                        </span>
                    </td>
                    <td>
                        @if($achieve_ov >= 85)
                            <span class="badge-ok" style="font-size:9.5px;padding:2px 7px;">OK</span>
                        @else
                            <span class="badge-ng" style="font-size:9.5px;padding:2px 7px;">NG</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Achievement Summary strip ──────────────────────────────────── --}}
        <div class="ach-summary">
            <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#374151;margin-bottom:6px;">
                Achievement Summary
            </div>
            <table class="ach-summary-tbl">
                <tr>
                    <td>
                        <div class="ach-big" style="color:{{ $achieveColor($achieve_ov) }};">
                            {{ $achieve_ov }}%
                        </div>
                        <div class="ach-lbl">Overall Achievement</div>
                    </td>
                    <td>
                        <div class="ach-big" style="color:#16a34a;">{{ $ov_ok }}</div>
                        <div class="ach-lbl">
                            OK :&nbsp;
                            {{ $ov_ok + $ov_ng > 0 ? round($ov_ok / ($ov_ok + $ov_ng) * 100, 1) : 0 }}%
                        </div>
                    </td>
                    <td>
                        <div class="ach-big" style="color:#dc2626;">{{ $ov_ng }}</div>
                        <div class="ach-lbl">
                            NG :&nbsp;
                            {{ $ov_ok + $ov_ng > 0 ? round($ov_ng / ($ov_ok + $ov_ng) * 100, 1) : 0 }}%
                        </div>
                    </td>
                    <td>
                        <table style="width:100%;border-collapse:collapse;font-size:8px;">
                            <tr>
                                <td style="padding:2px 4px;text-align:left;">Dwelling</td>
                                <td style="font-weight:700;color:{{ $achieveColor($achieve_dw) }};">{{ $achieve_dw }}%</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 4px;text-align:left;">Sailing</td>
                                <td style="font-weight:700;color:{{ $achieveColor($achieve_sa) }};">{{ $achieve_sa }}%</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 4px;text-align:left;">Dooring</td>
                                <td style="font-weight:700;color:{{ $achieveColor($achieve_do) }};">{{ $achieve_do }}%</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

{{-- F. EVIDENCE --}}
<div class="section page-break">
    <div class="section-header">F. Evidence ({{ count($evidence_items) }} file)</div>
    <div class="section-body">
        @if(count($evidence_items) > 0)

        {{-- File index --}}
        <ul class="ev-list">
            @foreach($evidence_items as $i => $ev)
            <li>
                @if($ev['type'] === 'pdf')
                    <span class="ev-badge pdf">PDF</span>
                @elseif($ev['type'] === 'image')
                    <span class="ev-badge img">IMG</span>
                @else
                    <span class="ev-badge oth">DOC</span>
                @endif
                <strong>{{ $i + 1 }}.</strong>&nbsp;
                {{ $ev['name'] }}
                @if(isset($ev['size']))
                    <span style="color:#9ca3af;">({{ number_format($ev['size'] / 1024, 1) }} KB)</span>
                @endif
                @if($ev['type'] === 'pdf')
                    <span class="ev-pdf-note">— Dokumen PDF terlampir (lihat lampiran terpisah)</span>
                @endif
            </li>
            @endforeach
        </ul>

        {{-- Image previews ──────────────────────────────────────────────── --}}
        @foreach($evidence_items as $ev)
            @if($ev['type'] === 'image')
            <div class="ev-img-wrap">
                <img src="{{ $ev['src'] }}" class="ev-img" />
            </div>
            <div class="ev-caption">{{ $ev['name'] }}</div>
            @endif
        @endforeach

        @else
        <div style="text-align:center;color:#9ca3af;padding:20px;font-size:9px;">
            Tidak ada file evidence yang ditemukan untuk voyage ini.
            <br>
            <span style="font-size:8px;">
                Simpan file evidence di: <code>storage/app/public/evidence_qr/{{ $voyage->code ?? $voyage->voyage_no ?? '' }}/</code>
            </span>
        </div>
        @endif
    </div>
</div>

{{-- G. PENGESAHAN --}}
<div class="section">
    <div class="section-header">G. Pengesahan</div>
    <div class="section-body">
        <div style="font-size:8.5px;color:#6b7280;margin-bottom:12px;line-height:1.7;">
            Laporan ini diterbitkan sebagai Quick Report hasil evaluasi voyage
            <strong>{{ $voyage->vessel?->name ?? '' }}</strong>
            <strong>{{ $voyage->code ?? ('Voyage ' . ($voyage->voyage_no ?? '')) }}</strong>
            periode <strong>{{ $period }}</strong>.
            Dokumen ini bersifat informatif dan digunakan sebagai bahan evaluasi kinerja lead time
            pengiriman kendaraan moda laut.
            Nomor referensi: <strong>{{ $qr_ref }}</strong>.
        </div>
        <table class="sign-tbl">
            <tr>
                <td>
                    <div class="sign-role">Dibuat Oleh</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                    <div class="sign-dept">Divisi Operasional</div>
                </td>
                <td>
                    <div class="sign-role">Diketahui Oleh</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                    <div class="sign-dept">Supervisor Operasional</div>
                </td>
                <td>
                    <div class="sign-role">Disetujui Oleh</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                    <div class="sign-dept">Manajer Operasional</div>
                </td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>

@php
    use App\Models\UnitInspection;
    use App\Models\UnitInspectionItem;

    $s   = $shipment;
    $u   = $unit;
    $ins = $inspection;

    $gateColor = match ($ins->gate_decision) {
        UnitInspection::GATE_ACCEPT            => '#14532d',
        UnitInspection::GATE_ALLOW_WITH_REMARK => '#92400e',
        UnitInspection::GATE_RETURN_TO_PDC     => '#991b1b',
        default                                => '#374151',
    };

    $statusColor = $ins->status === UnitInspection::STATUS_PASSED ? '#14532d' : '#991b1b';

    $safeStr = fn($v, $d = '—') => filled($v) ? e((string) $v) : $d;

    $findingLabel = fn($type) => match ($type) {
        UnitInspectionItem::FINDING_MAJOR_DAMAGE  => 'Major Damage',
        UnitInspectionItem::FINDING_MINOR_MISSING => 'Minor / Missing',
        'information_only'                        => 'Information Only',
        default                                   => $type ?? '—',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Unit Inspection Report #{{ $ins->id }}</title>
<style>
    @page { margin: 14mm 12mm 14mm 12mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 8.5px;
        color: #111827;
        line-height: 1.4;
    }

    /* ── Header ─────────────────────────────────────────────────── */
    .page-header {
        border-bottom: 2.5px solid #1e40af;
        padding-bottom: 8px;
        margin-bottom: 10px;
    }
    .header-inner {
        display: table;
        width: 100%;
    }
    .header-logo-col {
        display: table-cell;
        width: 60%;
        vertical-align: middle;
    }
    .header-doc-col {
        display: table-cell;
        width: 40%;
        vertical-align: middle;
        text-align: right;
    }
    .company-name {
        font-size: 14px;
        font-weight: 700;
        color: #1e40af;
        letter-spacing: 0.5px;
    }
    .company-sub {
        font-size: 8px;
        color: #6b7280;
        margin-top: 1px;
    }
    .doc-title {
        font-size: 11px;
        font-weight: 700;
        color: #111827;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .doc-id {
        font-size: 7.5px;
        color: #6b7280;
        margin-top: 2px;
    }

    /* ── Section heading ─────────────────────────────────────────── */
    .section-heading {
        background: #1e40af;
        color: #fff;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 7px;
        margin-top: 9px;
        margin-bottom: 0;
    }

    /* ── Info grid (key-value pairs) ─────────────────────────────── */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
    }
    .info-table td {
        padding: 3px 7px;
        border-bottom: 0.5px solid #e5e7eb;
        vertical-align: top;
    }
    .info-table .label {
        width: 32%;
        color: #6b7280;
        font-weight: 600;
    }
    .info-table .value {
        color: #111827;
    }
    .info-two-col .label { width: 18%; }

    /* ── Checklist table ─────────────────────────────────────────── */
    .checklist-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
        font-size: 8px;
    }
    .checklist-table th {
        background: #dbeafe;
        color: #1e3a8a;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 7px;
        letter-spacing: 0.3px;
        padding: 3px 6px;
        border: 0.5px solid #bfdbfe;
        text-align: left;
    }
    .checklist-table td {
        padding: 3px 6px;
        border: 0.5px solid #e5e7eb;
        vertical-align: top;
    }
    .checklist-table .cat-row td {
        background: #f8fafc;
        font-weight: 700;
        color: #374151;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-ok {
        display: inline-block;
        background: #dcfce7;
        color: #14532d;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 7px;
        font-weight: 700;
    }
    .badge-ng {
        display: inline-block;
        background: #fee2e2;
        color: #991b1b;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 7px;
        font-weight: 700;
    }

    /* ── Result summary ──────────────────────────────────────────── */
    .result-row {
        display: table;
        width: 100%;
        margin-top: 9px;
    }
    .result-cell {
        display: table-cell;
        width: 50%;
        padding: 8px 10px;
        border: 0.5px solid #e5e7eb;
        vertical-align: middle;
    }
    .result-cell:first-child {
        border-right: none;
    }
    .result-label {
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        font-weight: 700;
    }
    .result-value {
        font-size: 13px;
        font-weight: 700;
        margin-top: 2px;
    }

    /* ── Signature ───────────────────────────────────────────────── */
    .sig-row {
        display: table;
        width: 100%;
        margin-top: 9px;
    }
    .sig-cell {
        display: table-cell;
        width: 50%;
        border: 0.5px solid #e5e7eb;
        padding: 8px 10px;
        vertical-align: top;
    }
    .sig-cell:first-child { border-right: none; }
    .sig-field-label {
        font-size: 7px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 600;
    }
    .sig-field-value {
        font-size: 9px;
        color: #111827;
        margin-top: 1px;
        font-weight: 600;
    }
    .sig-image-wrap {
        margin-top: 6px;
        min-height: 50px;
        border-top: 0.5px solid #e5e7eb;
        padding-top: 4px;
    }
    .sig-image {
        max-height: 60px;
        max-width: 180px;
    }
    .sig-no-image {
        color: #9ca3af;
        font-size: 7.5px;
        font-style: italic;
        margin-top: 8px;
    }

    /* ── Footer ──────────────────────────────────────────────────── */
    .page-footer {
        margin-top: 12px;
        border-top: 0.5px solid #e5e7eb;
        padding-top: 5px;
        font-size: 7px;
        color: #9ca3af;
        display: table;
        width: 100%;
    }
    .footer-left  { display: table-cell; text-align: left; }
    .footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>

{{-- ── Page Header ─────────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="header-inner">
        <div class="header-logo-col">
            <div class="company-name">PT JAYA SAKTI SEJATI</div>
            <div class="company-sub">Freight Forwarding</div>
        </div>
        <div class="header-doc-col">
            <div class="doc-title">Unit Inspection Report</div>
            <div class="doc-id">
                #{{ $ins->id }}
                &nbsp;·&nbsp;
                {{ $safeStr(UnitInspection::STAGE_LABELS[$ins->stage] ?? $ins->stage) }}
            </div>
        </div>
    </div>
</div>

{{-- ── Shipment Information ──────────────────────────────────────────── --}}
<div class="section-heading">Shipment Information</div>
<table class="info-table info-two-col">
    <tr>
        <td class="label">Shipment Code</td>
        <td class="value">{{ $safeStr($s->code) }}</td>
        <td class="label">Voyage</td>
        <td class="value">{{ $safeStr($s->voyage) }}</td>
    </tr>
    <tr>
        <td class="label">Customer</td>
        <td class="value">{{ $safeStr($s->customer?->name) }}</td>
        <td class="label">Origin</td>
        <td class="value">{{ $safeStr($s->pol ?? $s->route_from) }}</td>
    </tr>
    <tr>
        <td class="label">Destination</td>
        <td class="value" colspan="3">{{ $safeStr($s->pod ?? $s->route_to) }}</td>
    </tr>
</table>

{{-- ── Unit Information ──────────────────────────────────────────────── --}}
<div class="section-heading">Unit Information</div>
<table class="info-table info-two-col">
    <tr>
        <td class="label">Model</td>
        <td class="value">{{ $safeStr($u->model_no) }}</td>
        <td class="label">No Rangka</td>
        <td class="value" style="font-family: monospace;">{{ $safeStr($u->chassis_no) }}</td>
    </tr>
    <tr>
        <td class="label">No Mesin</td>
        <td class="value" style="font-family: monospace;">{{ $safeStr($u->engine_no) }}</td>
        <td class="label">Warna</td>
        <td class="value">{{ $safeStr($u->color) }}</td>
    </tr>
    <tr>
        <td class="label">SJKB</td>
        <td class="value" colspan="3" style="font-family: monospace;">{{ $safeStr($u->sjkb_no) }}</td>
    </tr>
</table>

{{-- ── Inspection Information ────────────────────────────────────────── --}}
<div class="section-heading">Inspection Information</div>
<table class="info-table info-two-col">
    <tr>
        <td class="label">Stage</td>
        <td class="value">{{ $safeStr(UnitInspection::STAGE_LABELS[$ins->stage] ?? $ins->stage) }}</td>
        <td class="label">Tanggal Inspeksi</td>
        <td class="value">{{ $ins->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Diperiksa Oleh</td>
        <td class="value">{{ $safeStr($ins->checkedBy?->name) }}</td>
        <td class="label">PIC</td>
        <td class="value">{{ $safeStr($ins->signed_by) }}</td>
    </tr>
    <tr>
        <td class="label">Tanda Tangan</td>
        <td class="value" colspan="3">{{ $ins->signed_at?->format('d M Y H:i') ?? '—' }}</td>
    </tr>
</table>

{{-- ── Checklist ──────────────────────────────────────────────────────── --}}
<div class="section-heading">Checklist Pemeriksaan</div>
<table class="checklist-table">
    <thead>
        <tr>
            <th style="width:20%">Kategori</th>
            <th style="width:38%">Item Pemeriksaan</th>
            <th style="width:10%;text-align:center">Hasil</th>
            <th style="width:18%">Jenis Temuan</th>
            <th style="width:14%">Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $category => $categoryItems)
            <tr class="cat-row">
                <td colspan="5">{{ $category }}</td>
            </tr>
            @foreach ($categoryItems as $item)
                <tr>
                    <td></td>
                    <td>{{ $item->item_name }}</td>
                    <td style="text-align:center">
                        @if ($item->result === 'ok')
                            <span class="badge-ok">OK</span>
                        @else
                            <span class="badge-ng">NG</span>
                        @endif
                    </td>
                    <td>
                        @if ($item->result === 'ng' && $item->finding_type)
                            {{ $findingLabel($item->finding_type) }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:7.5px;color:#374151">
                        {{ filled($item->notes) ? $item->notes : '—' }}
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

{{-- ── Result Summary ────────────────────────────────────────────────── --}}
<div class="result-row">
    <div class="result-cell">
        <div class="result-label">Status Inspeksi</div>
        <div class="result-value" style="color: {{ $statusColor }}">
            {{ strtoupper($ins->status ?? '—') }}
        </div>
    </div>
    <div class="result-cell">
        <div class="result-label">Gate Decision</div>
        <div class="result-value" style="color: {{ $gateColor }}">
            {{ strtoupper(str_replace('_', ' ', $ins->gate_decision ?? '—')) }}
        </div>
    </div>
</div>

{{-- ── Digital Signature ─────────────────────────────────────────────── --}}
<div class="section-heading">Tanda Tangan Digital</div>
<div class="sig-row">
    <div class="sig-cell">
        <div class="sig-field-label">Nama PIC</div>
        <div class="sig-field-value">{{ $safeStr($ins->signed_by) }}</div>
        <div class="sig-field-label" style="margin-top:5px">Tanggal Tanda Tangan</div>
        <div class="sig-field-value">{{ $ins->signed_at?->format('d M Y H:i') ?? '—' }}</div>
    </div>
    <div class="sig-cell">
        <div class="sig-field-label">Tanda Tangan</div>
        <div class="sig-image-wrap">
            @if ($signatureDataUri)
                <img class="sig-image" src="{{ $signatureDataUri }}" alt="Tanda Tangan">
            @else
                <div class="sig-no-image">Tidak ada tanda tangan digital</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Page Footer ────────────────────────────────────────────────────── --}}
<div class="page-footer">
    <div class="footer-left">
        PT Jaya Sakti Sejati · Unit Inspection Report · #{{ $ins->id }}
    </div>
    <div class="footer-right">
        Dibuat: {{ now()->format('d M Y H:i') }} · Dokumen ini merupakan bukti audit resmi
    </div>
</div>

</body>
</html>

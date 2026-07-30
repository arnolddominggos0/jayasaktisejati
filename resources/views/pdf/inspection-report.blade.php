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

    $allItems  = $items->flatten();
    $okCount   = $allItems->where('result', 'ok')->count();
    $ngCount   = $allItems->where('result', 'ng')->count();
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
        width: 52%;
        vertical-align: middle;
    }
    .header-doc-col {
        display: table-cell;
        width: 48%;
        vertical-align: middle;
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
        margin-bottom: 3px;
    }
    .doc-meta-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5px;
    }
    .doc-meta-table td {
        padding: 1px 0;
        vertical-align: top;
    }
    .doc-meta-table .doc-meta-label {
        width: 42%;
        color: #6b7280;
        font-weight: 600;
    }
    .doc-meta-table .doc-meta-value {
        color: #111827;
        font-weight: 700;
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

    /* ── Checksheet — hasil pemeriksaan per kategori ─────────────── */
    .category-block {
        margin-top: 5px;
    }
    .category-heading {
        background: #f1f5f9;
        color: #1e293b;
        font-size: 7.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 3px 7px;
        border-left: 3px solid #1e40af;
        margin-bottom: 2px;
    }
    .check-item {
        padding: 1.5px 7px 1.5px 12px;
        font-size: 8.5px;
    }
    .check-mark-ok {
        color: #14532d;
        font-weight: 700;
        margin-right: 5px;
    }
    .check-mark-ng {
        color: #991b1b;
        font-weight: 700;
        margin-right: 5px;
    }
    .check-item-ng-detail {
        margin: 2px 7px 5px 24px;
        padding: 4px 8px;
        background: #fef2f2;
        border-left: 2px solid #991b1b;
        font-size: 7.5px;
        color: #374151;
    }
    .check-item-ng-detail .finding-row {
        margin-bottom: 1px;
    }
    .check-item-ng-detail .label-inline {
        color: #6b7280;
        font-weight: 600;
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
    .result-count-row {
        display: table;
        width: 100%;
        margin-top: 0;
        border: 0.5px solid #e5e7eb;
        border-top: none;
    }
    .result-count-cell {
        display: table-cell;
        width: 33.33%;
        padding: 6px 10px;
        text-align: center;
        border-right: 0.5px solid #e5e7eb;
    }
    .result-count-cell:last-child {
        border-right: none;
    }
    .result-count-label {
        font-size: 6.5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #6b7280;
        font-weight: 600;
    }
    .result-count-value {
        font-size: 11px;
        font-weight: 700;
        margin-top: 1px;
        color: #111827;
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
            <table class="doc-meta-table">
                <tr>
                    <td class="doc-meta-label">Inspection No</td>
                    <td class="doc-meta-value">#{{ $ins->id }}</td>
                </tr>
                <tr>
                    <td class="doc-meta-label">Tanggal</td>
                    <td class="doc-meta-value">{{ $ins->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="doc-meta-label">Stage</td>
                    <td class="doc-meta-value">{{ $safeStr(UnitInspection::STAGE_LABELS[$ins->stage] ?? $ins->stage) }}</td>
                </tr>
                <tr>
                    <td class="doc-meta-label">Status</td>
                    <td class="doc-meta-value" style="color: {{ $statusColor }}">{{ strtoupper($ins->status ?? '—') }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>

{{-- ── Informasi Pengiriman ──────────────────────────────────────────── --}}
<div class="section-heading">Informasi Pengiriman</div>
<table class="info-table info-two-col">
    <tr>
        <td class="label">Shipment</td>
        <td class="value">{{ $safeStr($s->code) }}</td>
        <td class="label">Customer</td>
        <td class="value">{{ $safeStr($s->customer?->name) }}</td>
    </tr>
    <tr>
        <td class="label">Voyage</td>
        <td class="value">{{ display_voyage($s->voyage) }}</td>
        <td class="label">Route</td>
        <td class="value">{{ $safeStr($s->route_summary) }}</td>
    </tr>
    <tr>
        <td class="label">Destination</td>
        <td class="value" colspan="3">{{ $safeStr($s->destinationCity?->name ?? $s->pod ?? $s->route_to) }}</td>
    </tr>
</table>

{{-- ── Informasi Unit ──────────────────────────────────────────────── --}}
<div class="section-heading">Informasi Unit</div>
<table class="info-table info-two-col">
    <tr>
        <td class="label">Model</td>
        <td class="value">{{ $safeStr($u->model_no) }}</td>
        <td class="label">Chassis</td>
        <td class="value" style="font-family: monospace;">{{ $safeStr($u->chassis_no) }}</td>
    </tr>
    <tr>
        <td class="label">Engine</td>
        <td class="value" style="font-family: monospace;">{{ $safeStr($u->engine_no) }}</td>
        <td class="label">Color</td>
        <td class="value">{{ $safeStr($u->color) }}</td>
    </tr>
    <tr>
        <td class="label">SJKB</td>
        <td class="value" colspan="3" style="font-family: monospace;">{{ $safeStr($u->sjkb_no) }}</td>
    </tr>
</table>

{{-- ── Hasil Pemeriksaan ──────────────────────────────────────────────── --}}
<div class="section-heading">Hasil Pemeriksaan</div>
@foreach ($items as $category => $categoryItems)
    <div class="category-block">
        <div class="category-heading">{{ $category }}</div>
        @foreach ($categoryItems as $item)
            <div class="check-item">
                @if ($item->result === 'ok')
                    <span class="check-mark-ok">✓</span>
                @else
                    <span class="check-mark-ng">✗</span>
                @endif
                {{ $item->item_name }}
            </div>
            @if ($item->result === 'ng')
                <div class="check-item-ng-detail">
                    <div class="finding-row">
                        <span class="label-inline">Jenis Temuan:</span> {{ $findingLabel($item->finding_type) }}
                    </div>
                    @if (filled($item->notes))
                        <div class="finding-row">
                            <span class="label-inline">Catatan:</span> {{ $item->notes }}
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endforeach

{{-- ── Result Summary ────────────────────────────────────────────────── --}}
<div class="result-row">
    <div class="result-cell">
        <div class="result-label">Inspection Result</div>
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
<div class="result-count-row">
    <div class="result-count-cell">
        <div class="result-count-label">Jumlah Item OK</div>
        <div class="result-count-value" style="color:#14532d">{{ $okCount }}</div>
    </div>
    <div class="result-count-cell">
        <div class="result-count-label">Jumlah Item NG</div>
        <div class="result-count-value" style="color:#991b1b">{{ $ngCount }}</div>
    </div>
    <div class="result-count-cell">
        <div class="result-count-label">Jumlah Temuan</div>
        <div class="result-count-value">{{ $ngCount }}</div>
    </div>
</div>

{{-- ── Digital Signature ─────────────────────────────────────────────── --}}
<div class="section-heading">Tanda Tangan Digital</div>
<div class="sig-row">
    <div class="sig-cell">
        <div class="sig-field-label">Nama PIC</div>
        <div class="sig-field-value">{{ $safeStr($ins->signed_by) }}</div>
        <div class="sig-field-label" style="margin-top:5px">Jabatan</div>
        <div class="sig-field-value">{{ $safeStr($ins->signed_position) }}</div>
        <div class="sig-field-label" style="margin-top:5px">Tanggal</div>
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

@php
    $brandDefaults = [
        'name' => 'PT Jaya Sakti Sejati',
        'abbr' => 'JSS',
        'color' => '#0137A1',
    ];
    $brand = array_merge($brandDefaults, $brand ?? []);
    $bgBrand = $brand['color'] ?? '#0137A1';
    $safe = fn($v, $dash = '-') => filled($v) && $v !== '-' ? e($v) : $dash;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $safe($shipment->code) }} - {{ $safe($brand['abbr']) }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid {{ $bgBrand }}; padding-bottom: 10px; margin-bottom: 12px; }
        .waybill-box { background: {{ $bgBrand }}; color: #fff; padding: 10px 20px; border-radius: 6px; text-align: center; }
        .waybill-number { font-size: 18px; font-weight: 900; }
        .sea-banner { background: linear-gradient(135deg, #eef2ff, #dbe4ff); border: 2px solid {{ $bgBrand }}; border-radius: 8px; padding: 12px; margin-bottom: 12px; text-align: center; }
        .sea-banner-title { font-size: 16px; font-weight: 800; color: {{ $bgBrand }}; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .card-header { font-weight: 800; font-size: 11px; color: {{ $bgBrand }}; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 2px solid {{ $bgBrand }}; }
        .info-row { display: grid; grid-template-columns: 40% 60%; gap: 6px; margin-bottom: 5px; }
        .info-label { color: #6b7280; font-size: 9px; font-weight: 600; }
        .info-value { font-size: 10px; font-weight: 600; }
        .sea-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
        .sea-info-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; }
        .sea-info-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
        .sea-info-value { font-size: 12px; font-weight: 700; color: {{ $bgBrand }}; }
        .route-box { display: flex; align-items: center; justify-content: space-between; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .route-label { font-size: 9px; color: #3b82f6; font-weight: 700; text-transform: uppercase; }
        .route-value { font-size: 14px; font-weight: 800; color: #1e40af; }
        .route-arrow { font-size: 24px; color: {{ $bgBrand }}; padding: 0 16px; }
        .footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #6b7280; }
        .barcode-img { height: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div style="font-size: 20px; font-weight: 800; color: {{ $bgBrand }};">{{ $safe($brand['name']) }}</div>
            <div style="font-size: 9px; color: #6b7280;">{{ $safe($branchInfo['address'] ?? '') }} | Telp: {{ $safe($branchInfo['phone'] ?? '') }}</div>
        </div>
        <div class="waybill-box">
            <div style="font-size: 10px;">WAYBILL LAUT</div>
            <div class="waybill-number">{{ $safe($shipment->code) }}</div>
        </div>
    </div>

    <div class="sea-banner">
        <div class="sea-banner-title">SEA WAYBILL / BILL OF LADING</div>
        <div style="font-size: 10px; color: #4b5563;">{{ $safe($seaInfo['service_option'] ?? 'FCL') }} - {{ $safe($seaInfo['container_size'] ?? '-') }}</div>
    </div>

    <div class="sea-info-grid">
        <div class="sea-info-box">
            <div class="sea-info-label">VESSEL</div>
            <div class="sea-info-value">{{ $safe($seaInfo['vessel_name'] ?? '-') }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">VOYAGE</div>
            <div class="sea-info-value">{{ $safe($seaInfo['voyage'] ?? '-') }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">CONTAINER</div>
            <div class="sea-info-value">{{ $safe($seaInfo['container_no'] ?? '-') }}</div>
        </div>
    </div>

    <div class="route-box">
        <div style="text-align: center; flex: 1;">
            <div class="route-label">POL (Port of Loading)</div>
            <div class="route-value">{{ $safe($seaInfo['pol'] ?? '-') }}</div>
        </div>
        <div class="route-arrow">&rarr;</div>
        <div style="text-align: center; flex: 1;">
            <div class="route-label">POD (Port of Discharge)</div>
            <div class="route-value">{{ $safe($seaInfo['pod'] ?? '-') }}</div>
        </div>
    </div>

    <div class="sea-info-grid">
        <div class="sea-info-box">
            <div class="sea-info-label">ETD</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['etd'] ?? '-') }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">ETA</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['eta'] ?? '-') }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">SEAL NO</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['seal_no'] ?? '-') }}</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">Shipper (Pengirim)</div>
            <div class="info-row">
                <div class="info-label">Nama</div>
                <div class="info-value">{{ $safe($shipment->customer?->name) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon</div>
                <div class="info-value">{{ $safe($shipment->customer?->phone) }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Consignee (Penerima)</div>
            <div class="info-row">
                <div class="info-label">Nama</div>
                <div class="info-value">{{ $safe($shipment->receiver?->name) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon</div>
                <div class="info-value">{{ $safe($shipment->receiver?->phone) }}</div>
            </div>
        </div>
    </div>

    <div style="background: #fafafa; border: 1px dashed #d1d5db; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 8px; color: #6b7280;">TRACKING URL</div>
                <div style="font-size: 9px;">{{ $safe($trackUrl) }}</div>
            </div>
            <div>
                <img src="{{ $barcodeDataUri }}" class="barcode-img" alt="barcode">
            </div>
        </div>
    </div>

    <div class="footer">
        <strong>{{ $safe($brand['name']) }}</strong> | Waybill ini adalah dokumen resmi pengiriman laut.<br>
        Dicetak: {{ $safe($printedAt) }} | Layanan: {{ $safe($brand['phone']) }}
    </div>
</body>
</html>

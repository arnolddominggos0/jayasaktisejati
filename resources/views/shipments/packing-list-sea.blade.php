@php
    $brandDefaults = [
        'name' => 'PT Jaya Sakti Sejati',
        'abbr' => 'JSS',
        'color' => '#0137A1',
    ];
    $brand = array_merge($brandDefaults, $brand ?? []);
    $bgBrand = $brand['color'] ?? '#0137A1';
    $safe = fn($v, $dash = '—') => filled($v) && $v !== '—' ? e($v) : $dash;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Packing List {{ $safe($shipment->code) }} - {{ $safe($brand['abbr']) }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid {{ $bgBrand }}; padding-bottom: 10px; margin-bottom: 12px; }
        .pl-box { background: {{ $bgBrand }}; color: #fff; padding: 10px 20px; border-radius: 6px; text-align: center; }
        .pl-number { font-size: 18px; font-weight: 900; }
        .sea-banner { background: linear-gradient(135deg, #eef2ff, #dbe4ff); border: 2px solid {{ $bgBrand }}; border-radius: 8px; padding: 12px; margin-bottom: 12px; text-align: center; }
        .sea-banner-title { font-size: 16px; font-weight: 800; color: {{ $bgBrand }}; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .card-header { font-weight: 800; font-size: 11px; color: {{ $bgBrand }}; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 2px solid {{ $bgBrand }}; }
        .info-row { display: grid; grid-template-columns: 40% 60%; gap: 6px; margin-bottom: 5px; }
        .info-label { color: #6b7280; font-size: 9px; font-weight: 600; }
        .info-value { font-size: 10px; font-weight: 600; }
        .sea-info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 10px; }
        .sea-info-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; text-align: center; }
        .sea-info-label { font-size: 7px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
        .sea-info-value { font-size: 11px; font-weight: 700; color: {{ $bgBrand }}; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items-table th, .items-table td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        .items-table th { background: {{ $bgBrand }}; color: #fff; font-weight: 800; font-size: 9px; }
        .items-table td { font-size: 9px; }
        .items-table tbody tr:nth-child(even) { background: #fafafa; }
        .footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #6b7280; }
        .barcode-img { height: 36px; }
        .summary-box { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div style="font-size: 20px; font-weight: 800; color: {{ $bgBrand }};">{{ $safe($brand['name']) }}</div>
            <div style="font-size: 9px; color: #6b7280;">{{ $safe($branchInfo['address'] ?? '') }} | Telp: {{ $safe($branchInfo['phone'] ?? '') }}</div>
        </div>
        <div class="pl-box">
            <div style="font-size: 10px;">PACKING LIST</div>
            <div class="pl-number">{{ $safe($shipment->code) }}</div>
        </div>
    </div>

    <div class="sea-banner">
        <div class="sea-banner-title">SEA SHIPMENT PACKING LIST</div>
        <div style="font-size: 10px; color: #4b5563;">{{ $safe($seaInfo['service_option'] ?? 'FCL') }} - Container: {{ $safe($seaInfo['container_size'] ?? '—') }}</div>
    </div>

    <div class="sea-info-grid">
        <div class="sea-info-box">
            <div class="sea-info-label">Vessel</div>
            <div class="sea-info-value">{{ $safe($seaInfo['vessel_name']) }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">Voyage</div>
            <div class="sea-info-value">{{ $safe($seaInfo['voyage']) }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">Container No</div>
            <div class="sea-info-value">{{ $safe($seaInfo['container_no']) }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">Seal No</div>
            <div class="sea-info-value">{{ $safe($seaInfo['seal_no']) }}</div>
        </div>
    </div>

    <div class="sea-info-grid">
        <div class="sea-info-box">
            <div class="sea-info-label">POL</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['pol']) }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">POD</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['pod']) }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">ETD</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['etd']) }}</div>
        </div>
        <div class="sea-info-box">
            <div class="sea-info-label">ETA</div>
            <div style="font-weight: 700;">{{ $safe($seaInfo['eta']) }}</div>
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
                <div class="info-label">Alamat</div>
                <div class="info-value">{{ $safe($shipment->customer?->address) }}</div>
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
                <div class="info-label">Alamat</div>
                <div class="info-value">{{ $safe($shipment->receiver?->address) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon</div>
                <div class="info-value">{{ $safe($shipment->receiver?->phone) }}</div>
            </div>
        </div>
    </div>

    @if(filled($shipment->units) && $shipment->units->count() > 0)
    <div class="card" style="margin-top: 10px;">
        <div class="card-header">Detail Cargo / Units</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 20%;">Model/Description</th>
                    <th style="width: 15%;">No. Polisi</th>
                    <th style="width: 20%;">No. Rangka (Chassis)</th>
                    <th style="width: 20%;">No. Mesin</th>
                    <th style="width: 10%;">Warna</th>
                    <th style="width: 10%;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipment->units as $idx => $unit)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $safe($unit->model_no ?? $unit['model_no'] ?? '-') }}</td>
                    <td>{{ $safe($unit->reg_no ?? $unit['reg_no'] ?? '-') }}</td>
                    <td style="font-size: 8px;">{{ $safe($unit->chassis_no ?? $unit['chassis_no'] ?? '-') }}</td>
                    <td style="font-size: 8px;">{{ $safe($unit->engine_no ?? $unit['engine_no'] ?? '-') }}</td>
                    <td>{{ $safe($unit->color ?? $unit['color'] ?? '-') }}</td>
                    <td style="text-align: center;">{{ $unit->qty ?? $unit['qty'] ?? 1 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(filled($shipment->lcl_items) && is_array($shipment->lcl_items) && count($shipment->lcl_items) > 0)
    <div class="card" style="margin-top: 10px;">
        <div class="card-header">Detail Barang (LCL)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 45%;">Deskripsi Barang</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 15%;">Berat (kg)</th>
                    <th style="width: 20%;">Volume (CBM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipment->lcl_items as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $safe($item['description'] ?? '-') }}</td>
                    <td style="text-align: center;">{{ $item['qty'] ?? 0 }}</td>
                    <td style="text-align: right;">{{ number_format($item['weight_kg'] ?? 0, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item['cbm'] ?? 0, 3) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="summary-box">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 9px; color: #6b7280;">TOTAL</div>
                <div style="font-size: 11px; font-weight: 700;">
                    {{ $shipment->packages_total ?? count($shipment->units ?? []) }} Packages | 
                    {{ number_format($shipment->weight_total ?? 0, 2) }} kg | 
                    {{ number_format($shipment->cbm_total ?? 0, 3) }} CBM
                </div>
            </div>
            <div style="text-align: right;">
                <img src="{{ $barcodeDataUri }}" class="barcode-img" alt="barcode">
            </div>
        </div>
    </div>

    <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
        <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; height: 80px;">
            <div style="font-size: 9px; color: #6b7280; margin-bottom: 40px;">Dibuat Oleh</div>
            <div style="font-size: 9px;">_____________</div>
        </div>
        <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; height: 80px;">
            <div style="font-size: 9px; color: #6b7280; margin-bottom: 40px;">Dicek Oleh</div>
            <div style="font-size: 9px;">_____________</div>
        </div>
        <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; height: 80px;">
            <div style="font-size: 9px; color: #6b7280; margin-bottom: 40px;">Diterima Oleh</div>
            <div style="font-size: 9px;">_____________</div>
        </div>
    </div>

    <div class="footer">
        <strong>{{ $safe($brand['name']) }}</strong> | Packing List ini adalah dokumen pendukung pengiriman laut.<br>
        Dicetak: {{ $safe($printedAt) }} | Layanan: {{ $safe($brand['phone']) }}
    </div>
</body>
</html>

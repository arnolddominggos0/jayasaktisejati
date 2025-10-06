@php
$brandDefaults = [
'name' => 'PT Jaya Sakti Sejati',
'abbr' => 'JSS',
'color' => '#0137A1',
'logo' => null,
'site' => env('APP_BRAND_SITE', env('COMPANY_URL', config('company.url', config('app.url', 'https://jayasaktisejati.com')))),
'phone' => env('COMPANY_PHONE', config('company.phone', '')),
'addr' => (!empty($branchInfo) && is_array($branchInfo))
? trim(
($branchInfo['name'] ? $branchInfo['name'].' — ' : '').
($branchInfo['address'] ?? '').
(($branchInfo['city'] ?? '') ? ', '.$branchInfo['city'] : '')
)
: 'Komplek Lodan Center Jakarta',
];
$brand = array_merge($brandDefaults, $brand ?? []);
if (isset($brand['site']) && $brand['site'] && !preg_match('~^https?://~i', $brand['site'])) {
$brand['site'] = 'https://'.ltrim($brand['site'], '/');
}
if (strtolower($brand['name']) === 'laravel') $brand['name'] = $brandDefaults['name'];

$fmtDate = fn($dt) => optional($dt)->timezone(config('app.timezone'))->format('d M Y');
$fmtDatetime = fn($dt) => optional($dt)->timezone(config('app.timezone'))->format('d M Y H:i');
$safe = fn($v, $dash='-') => (filled($v) ? e($v) : $dash);
$join = function (array $parts, string $sep = ' · ') {
$clean = array_values(array_filter(array_map(fn($v) => is_string($v) ? trim($v) : ($v ?? ''), $parts), fn($v) => $v !== ''));
return implode($sep, $clean);
};
$toTitle = fn($x) => $x === '-' ? '-' : mb_convert_case($x, MB_CASE_TITLE, 'UTF-8');

$bgBrand = $brand['color'] ?? '#0137A1';

$originRaw = $shipment->originCity->name
?? $shipment->origin_city_name
?? $shipment->route_from
?? optional($shipment->originBranch?->city)->name
?? $shipment->originBranch?->name
?? '-';
$destRaw = $shipment->destinationCity->name
?? $shipment->destination_city_name
?? $shipment->route_to
?? optional($shipment->destinationBranch?->city)->name
?? $shipment->destinationBranch?->name
?? '-';

$origin = $toTitle($originRaw);
$dest = $toTitle($destRaw);

$packages = (int) ($shipment->packages_total ?? 0);
$weightKg = (float) ($shipment->weight_total ?? 0);
$volume = (float) ($shipment->cbm_total ?? 0);

$containerSize = $shipment->container_size ?? null;
$containerQty = (int) ($shipment->container_qty ?? 0);
$isFcl = !empty($containerSize) && $containerQty > 0;

$senderName = $shipment->customer?->name ?? '';
$senderPhone = $shipment->customer?->phone ?? $shipment->customer?->pic_phone ?? '';

$recvName = $shipment->receiver?->name ?? '';
$recvPhone = $shipment->receiver?->phone ?? $shipment->receiver?->pic_phone ?? '';
$recvAddr = $shipment->receiver?->address ?? '';

$serviceTypeLabel = $shipment->service_type?->label() ?? (is_string($shipment->service_type) ? $shipment->service_type : '-');
$modeLabel = $shipment->mode?->label() ?? (is_string($shipment->mode) ? strtoupper($shipment->mode) : '-');
$cargoTypeLabel = $shipment->cargo_type?->label() ?? (is_string($shipment->cargo_type) ? $shipment->cargo_type : '-');

$serviceOption = match($shipment->service_option) {
'fcl' => 'FCL',
'lcl' => 'LCL',
'truck' => 'Truck',
'car_carrier' => 'Car Carrier',
'towing' => 'Towing',
default => $shipment->service_option ?? '-'
};

$createdOn = $fmtDate($shipment->created_at);
$etaOn = filled($shipment->eta) ? $fmtDate($shipment->eta) : '-';
$etdOn = filled($shipment->etd) ? $fmtDate($shipment->etd) : '-';
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resi {{ e($shipment->code) }} - {{ e($brand['abbr']) }}</title>
    <style>
        @page {
            margin: 10mm 8mm
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #111827;
            line-height: 1.45
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            border-bottom: 3px solid #0137A1;
            padding-bottom: 8px;
            margin-bottom: 10px
        }

        .header-left {
            flex: 1
        }

        .logo-title {
            font-size: 18px;
            font-weight: 800;

            color: #0137A1;
            letter-spacing: .2px
        }

        .company-info {
            font-size: 8px;
            color: #6b7280;
            line-height: 1.5;
            margin-top: 3px
        }

        .header-right {
            text-align: right
        }

        .resi-box {
            background: #0137A1;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-align: center
        }

        .resi-label {
            font-size: 9px;
            opacity: .95;
            margin-bottom: 2px
        }

        .resi-number {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 1px
        }

        .date-chip {
            margin-top: 6px;
            display: inline-block;
            padding: 3px 6px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 8.5px;
            color: #374151;
            background: #fafafa
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px;
            margin: 10px 0
        }

        .meta .k {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 2px;
            font-weight: 600
        }

        .meta .v {
            font-size: 9.5px;
            color: #111827;
            font-weight: 600
        }

        .tracking-section {
            display: flex;
            gap: 12px;
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px
        }

        .qr-area {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            border-right: 1px dashed #d1d5db;
            padding-right: 12px
        }

        .qr-area svg {
            width: 72px !important;
            height: 72px !important
        }

        .qr-info {
            flex: 1
        }

        .qr-info .title {
            font-weight: 800;
            font-size: 10px;
            margin-bottom: 2px;
            color: #374151
        }

        .qr-info .url {
            font-size: 8.5px;
            color: #6b7280;
            word-break: break-all
        }

        .barcode-area {
            flex: 1;
            text-align: center
        }

        .barcode-area .title {
            font-weight: 800;
            font-size: 10px;
            margin-bottom: 4px;
            color: #374151
        }

        .barcode-area img {
            height: 52px;
            margin-bottom: 3px
        }

        .barcode-area .code {
            font-size: 8.5px;
            color: #6b7280;
            font-family: monospace
        }

        .route-highlight {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 10px
        }

        .route-point {
            text-align: center;
            flex: 1
        }

        .route-label {
            font-size: 8.5px;

            color: #0137A1;
            margin-bottom: 3px;
            font-weight: 700
        }

        .route-city {
            font-size: 13px;
            font-weight: 900;

            color: #0137A1;
            line-height: 1.2
        }

        .route-arrow {
            font-size: 20px;
            font-weight: 900;

            color: #0137A1;
            margin: 0 10px;
            padding: 0 12px;
            font-family: "DejaVu Sans",
                Arial,
                Helvetica,
                sans-serif
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            background: #fff
        }

        .card-header {
            font-weight: 900;
            font-size: 11px;
            color: #0f172a;
            margin-bottom: 8px;
            padding-bottom: 6px;

            border-bottom: 2px solid #0137A1;
        }

        .info-row {
            display: grid;
            grid-template-columns: 40% 60%;
            gap: 8px;
            margin-bottom: 5px
        }

        .info-label {
            color: #6b7280;
            font-size: 9px;
            font-weight: 700
        }

        .info-value {
            font-size: 9.5px;
            color: #111827;
            font-weight: 600;
            word-break: break-word
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px
        }

        .items-table th,
        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left
        }

        .items-table th {
            background: #f3f4f6;
            font-weight: 800;
            font-size: 9px;
            color: #374151
        }

        .items-table td {
            font-size: 9px
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa
        }

        .notes-content {
            min-height: 50px;
            font-size: 9px;
            color: #374151;
            font-style: italic
        }

        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            line-height: 1.4
        }

        .footer strong {
            color: #374151
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 8.5px;
            font-weight: 800
        }

        .badge-priority {
            background: #fef3c7;
            color: #92400e
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <div style="display:flex;align-items:center;gap:12px">
                @if(!empty($brand['logo']))
                <img src="{{ $brand['logo'] }}" alt="Logo" style="height:38px;width:auto">
                @endif
                <div>
                    <div class="logo-title">{{ e($brand['name']) }}</div>
                    <div class="company-info">
                        {{ $join([
                            filled($brand['addr']) ? $brand['addr'] : null,
                            filled($brand['phone']) ? 'Telp: '.$brand['phone'] : null,
                            filled($brand['site']) ? $brand['site'] : null,
                        ]) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="resi-box">
                <div class="resi-label">NOMOR RESI</div>
                <div class="resi-number">{{ e($shipment->code) }}</div>
            </div>
            <div class="date-chip">Dicetak: {{ e($printedAt ?? $fmtDatetime(now())) }}</div>
        </div>
    </div>

    <div class="meta">
        <div>
            <div class="k">ETD</div>
            <div class="v">{{ $safe($etdOn) }}</div>
        </div>
        <div>
            <div class="k">ETA</div>
            <div class="v">{{ $safe($etaOn) }}</div>
        </div>
    </div>

    <div class="tracking-section">
        <div class="qr-area">
            <div>{!! $qrSvg !!}</div>
            <div class="qr-info">
                <div class="title">Lacak Pengiriman</div>
                <div class="url">{{ e($trackUrl) }}</div>
            </div>
        </div>
        <div class="barcode-area">
            <div class="title">BARCODE</div>
            <img alt="BARCODE" src="{{ $barcodeDataUri }}">
            <div class="code">{{ e($shipment->code) }}</div>
        </div>
    </div>

    <div class="route-highlight">
        <div class="route-point">
            <div class="route-label">Asal</div>
            <div class="route-city">{{ $safe($origin) }}</div>
        </div>
        <div class="route-arrow">&rarr;</div>
        <div class="route-point">
            <div class="route-label">Tujuan</div>
            <div class="route-city">{{ $safe($dest) }}</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">Data Pengirim</div>
            <div class="info-row">
                <div class="info-label">Nama</div>
                <div class="info-value">{{ $safe($senderName) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon</div>
                <div class="info-value">{{ $safe($senderPhone) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Cabang Asal</div>
                <div class="info-value">{{ $safe($shipment->branch?->name ?? $shipment->originBranch?->name) }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Data Penerima</div>
            <div class="info-row">
                <div class="info-label">Nama</div>
                <div class="info-value">{{ $safe($recvName) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon</div>
                <div class="info-value">{{ $safe($recvPhone) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Alamat</div>
                <div class="info-value">{{ $safe($recvAddr) }}</div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">Layanan & Moda</div>
            <div class="info-row">
                <div class="info-label">Moda</div>
                <div class="info-value">{{ $modeLabel }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Jenis Layanan</div>
                <div class="info-value">{{ $serviceTypeLabel }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Opsi</div>
                <div class="info-value">{{ $serviceOption }}</div>
            </div>
            @if($isFcl && $containerSize)
            <div class="info-row">
                <div class="info-label">Container</div>
                <div class="info-value">{{ $safe(strtoupper($containerSize)) }} × {{ $containerQty }}</div>
            </div>
            @endif
            @if(!$isFcl && $packages > 0)
            <div class="info-row">
                <div class="info-label">Jumlah Koli</div>
                <div class="info-value">{{ number_format($packages) }} koli</div>
            </div>
            @endif
            @if(!$isFcl && $weightKg > 0)
            <div class="info-row">
                <div class="info-label">Berat Total</div>
                <div class="info-value">{{ number_format($weightKg, 2) }} kg</div>
            </div>
            @endif
            @if(!$isFcl && $volume > 0)
            <div class="info-row">
                <div class="info-label">Volume</div>
                <div class="info-value">{{ number_format($volume, 3) }} CBM</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Jenis Muatan</div>
                <div class="info-value">{{ $cargoTypeLabel }}</div>
            </div>
            @if(filled($shipment->priority) && $shipment->priority !== 'normal')
            <div class="info-row">
                <div class="info-label">Prioritas</div>
                <div class="info-value"><span class="badge badge-priority">{{ $safe(strtoupper($shipment->priority)) }}</span></div>
            </div>
            @endif
        </div>
        <div class="card">
            <div class="card-header">Informasi Tambahan</div>
            @if(filled($shipment->doc_number))
            <div class="info-row">
                <div class="info-label">No. Dokumen</div>
                <div class="info-value">{{ $safe($shipment->doc_number) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(filled($shipment->lcl_items) && is_iterable($shipment->lcl_items))
    <div class="card">
        <div class="card-header">Detail Barang (LCL)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:6%">No</th>
                    <th style="width:42%">Uraian Barang</th>
                    <th style="width:12%">Qty</th>
                    <th style="width:20%">Berat (kg)</th>
                    <th style="width:20%">Volume (CBM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipment->lcl_items as $idx => $it)
                @php
                $desc = $it['description'] ?? '-';
                $qty = (int) ($it['qty'] ?? 0);
                $w = (float)($it['weight_kg'] ?? 0);
                $p = (float)($it['length_cm'] ?? 0);
                $l = (float)($it['width_cm'] ?? 0);
                $t = (float)($it['height_cm'] ?? 0);
                $cbm = ($p * $l * $t * $qty) / 1000000;
                @endphp
                <tr>
                    <td style="text-align:center">{{ $idx + 1 }}</td>
                    <td>{{ e($desc) }}</td>
                    <td style="text-align:center">{{ $qty }}</td>
                    <td style="text-align:right">{{ number_format($w, 2) }}</td>
                    <td style="text-align:right">{{ number_format($cbm, 3) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(filled($shipment->units) && is_iterable($shipment->units))
    <div class="card" style="margin-top:10px">
        <div class="card-header">Detail Unit Kendaraan</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:6%">No</th>
                    <th style="width:20%">Model</th>
                    <th style="width:14%">No. Polisi</th>
                    <th style="width:22%">No. Rangka</th>
                    <th style="width:22%">No. Mesin</th>
                    <th style="width:8%">Qty</th>
                    <th style="width:8%">Rack</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipment->units as $idx => $unit)
                <tr>
                    <td style="text-align:center">{{ $idx + 1 }}</td>
                    <td>{{ e($unit['model_no'] ?? '-') }}</td>
                    <td>{{ e($unit['reg_no'] ?? '-') }}</td>
                    <td style="font-size:8px">{{ e($unit['chassis_no'] ?? '-') }}</td>
                    <td style="font-size:8px">{{ e($unit['engine_no'] ?? '-') }}</td>
                    <td style="text-align:center">{{ $unit['qty'] ?? 1 }}</td>
                    <td style="text-align:center">{{ !empty($unit['is_rack']) ? '✓' : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="grid-2" style="margin-top:10px">
        <div class="card">
            <div class="card-header">Catatan</div>
            <div class="notes-content">{{ $safe($shipment->notes ?? 'Tidak ada catatan khusus', '') }}</div>
        </div>
    </div>

    <div class="footer">
        <strong>{{ e($brand['name']) }}</strong> · Resi ini adalah bukti sah pengiriman.<br>
        Simpan nomor resi untuk pelacakan. Layanan pelanggan: <strong>{{ e($brand['phone']) }}</strong> · {{ e($brand['site']) }}
        @if(isset($depotInfo) && $depotInfo)
        <br><span style="font-size:7.5px;display:block;margin-top:4px">Depo: {{ e($depotInfo['name']) }} · {{ e($depotInfo['address']) }}@if(!empty($depotInfo['phone'])) · Telp: {{ e($depotInfo['phone']) }}@endif</span>
        @endif
    </div>
</body>

</html>
@php
    $brandDefaults = [
        'name' => 'PT Jaya Sakti Sejati',
        'abbr' => 'JSS',
        'color' => '#0137A1',
        'logo' => null,
        'site' => env(
            'APP_BRAND_SITE',
            env('COMPANY_URL', config('company.url', config('app.url', 'https://jayasaktiapp.com'))),
        ),
        'phone' => env('COMPANY_PHONE', config('company.phone', '')),
        'addr' =>
            !empty($branchInfo) && is_array($branchInfo)
                ? trim(
                    ($branchInfo['name'] ? $branchInfo['name'] . ' — ' : '') .
                        ($branchInfo['address'] ?? '') .
                        ($branchInfo['city'] ?? '' ? ', ' . $branchInfo['city'] : ''),
                )
                : 'Komplek Lodan Center Jakarta, Ancol',
    ];
    $brand = array_merge($brandDefaults, $brand ?? []);
    if (isset($brand['site']) && $brand['site'] && !preg_match('~^https?://~i', $brand['site'])) {
        $brand['site'] = 'https://' . ltrim($brand['site'], '/');
    }
    if (strtolower($brand['name']) === 'laravel') {
        $brand['name'] = $brandDefaults['name'];
    }

    $fmtDate = fn($dt) => optional($dt)->timezone(config('app.timezone'))->format('d M Y');
    $fmtDatetime = fn($dt) => optional($dt)->timezone(config('app.timezone'))->format('d M Y H:i');
    $safe = fn($v, $dash = '-') => filled($v) ? e($v) : $dash;
    $join = function (array $parts, string $sep = ' · ') {
        $clean = array_values(
            array_filter(array_map(fn($v) => is_string($v) ? trim($v) : $v ?? '', $parts), fn($v) => $v !== ''),
        );
        return implode($sep, $clean);
    };
    $toTitle = fn($x) => $x === '-' ? '-' : mb_convert_case($x, MB_CASE_TITLE, 'UTF-8');

    $bgBrand = $brand['color'] ?? '#0137A1';

    $originRaw =
        $shipment->originCity->name ??
        ($shipment->origin_city_name ??
            ($shipment->route_from ??
                (optional($shipment->originBranch?->city)->name ?? ($shipment->originBranch?->name ?? '-'))));
    $destRaw =
        $shipment->destinationCity->name ??
        ($shipment->destination_city_name ??
            ($shipment->route_to ??
                (optional($shipment->destinationBranch?->city)->name ?? ($shipment->destinationBranch?->name ?? '-'))));

    $origin = $toTitle($originRaw);
    $dest = $toTitle($destRaw);

    $packages = (int) ($shipment->packages_total ?? 0);
    $weightKg = (float) ($shipment->weight_total ?? 0);
    $volume = (float) ($shipment->cbm_total ?? 0);

    $containerSize = $shipment->container_size ?? null;
    $containerQty = (int) ($shipment->container_qty ?? 0);
    $isFcl = !empty($containerSize) && $containerQty > 0;

    $senderName = $shipment->customer?->name ?? '';
    $recvName = $shipment->receiver?->name ?? '';
    $dealerName = $shipment->dealer?->name ?? '';

    $serviceTypeLabel =
        $shipment->service_type?->label() ?? (is_string($shipment->service_type) ? $shipment->service_type : '-');
    $modeLabel = $shipment->mode?->label() ?? (is_string($shipment->mode) ? strtoupper($shipment->mode) : '-');
    $cargoTypeLabel =
        $shipment->cargo_type?->label() ?? (is_string($shipment->cargo_type) ? $shipment->cargo_type : '-');

    $serviceOption = match ($shipment->service_option) {
        'fcl' => 'FCL',
        'lcl' => 'LCL',
        'truck' => 'Truck',
        'car_carrier' => 'Car Carrier',
        'towing' => 'Towing',
        default => $shipment->service_option ?? '-',
    };

    $createdOn = $fmtDate($shipment->created_at);
    $etaOn = filled($shipment->eta) ? $fmtDate($shipment->eta) : '-';

    $unitCount = filled($shipment->units) && is_iterable($shipment->units) ? count($shipment->units) : 0;
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
            line-height: 1.4
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            border-bottom: 3px solid #0137A1;
            padding-bottom: 6px;
            margin-bottom: 8px
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

        .section-label {
            font-size: 8px;
            font-weight: 800;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 8px 0 4px
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 7px;
            margin: 6px 0;
            page-break-inside: avoid
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

        .tracking-strip {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 8px;
            margin: 10px 0 0;
            page-break-inside: avoid
        }

        .qr-area {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            border-right: 1px dashed #d1d5db;
            padding-right: 10px
        }

        .qr-area svg {
            width: 44px !important;
            height: 44px !important
        }

        .qr-info {
            flex: 1
        }

        .qr-info .title {
            font-weight: 800;
            font-size: 8.5px;
            margin-bottom: 1px;
            color: #374151
        }

        .qr-info .url {
            font-size: 7.5px;
            color: #6b7280;
            word-break: break-all
        }

        .barcode-area {
            flex: 1;
            text-align: center
        }

        .barcode-area img {
            height: 30px;
            margin-bottom: 1px
        }

        .barcode-area .code {
            font-size: 7.5px;
            color: #6b7280;
            font-family: monospace
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 8px;
            page-break-inside: avoid
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            background: #fff;
            page-break-inside: avoid
        }

        .card-header {
            font-weight: 900;
            font-size: 10.5px;
            color: #0f172a;
            margin-bottom: 6px;
            padding-bottom: 5px;

            border-bottom: 2px solid #0137A1;
        }

        .info-row {
            display: grid;
            grid-template-columns: 40% 60%;
            gap: 8px;
            margin-bottom: 4px
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
            padding: 5px 8px;
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

        .items-table tr {
            page-break-inside: avoid
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa
        }

        .notes-content {
            min-height: 20px;
            font-size: 9px;
            color: #374151;
            font-style: italic
        }

        .footer {
            margin-top: 8px;
            padding-top: 6px;
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
                @if (!empty($brand['logo']))
                    <img src="{{ $brand['logo'] }}" alt="Logo" style="height:38px;width:auto">
                @endif
                <div>
                    <div class="logo-title">{{ e($brand['name']) }}</div>
                    <div class="company-info">
                        {{ $join([
                            filled($brand['addr']) ? $brand['addr'] : null,
                            filled($brand['phone']) ? 'Telp: ' . $brand['phone'] : null,
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
            <div class="k">Customer</div>
            <div class="v">{{ $safe($senderName) }}</div>
        </div>
        @if (filled($dealerName))
            <div>
                <div class="k">Dealer</div>
                <div class="v">{{ $safe($dealerName) }}</div>
            </div>
        @endif
        <div>
            <div class="k">Rute</div>
            <div class="v">{{ $safe($origin) }} &rarr; {{ $safe($dest) }}</div>
        </div>
        <div>
            <div class="k">{{ $unitCount > 0 ? 'Jumlah Unit' : 'Jumlah Koli' }}</div>
            <div class="v">{{ $unitCount > 0 ? $unitCount . ' unit' : ($packages > 0 ? number_format($packages) . ' koli' : '-') }}</div>
        </div>
        <div>
            <div class="k">ETA</div>
            <div class="v">{{ $safe($etaOn) }}</div>
        </div>
    </div>

    <div class="section-label">{{ $cargoTypeLabel }}</div>

    @if ($isFcl || (!$isFcl && ($packages > 0 || $weightKg > 0 || $volume > 0)))
        <div class="meta" style="grid-template-columns:repeat(4,1fr)">
            @if ($isFcl && $containerSize)
                <div>
                    <div class="k">Container</div>
                    <div class="v">{{ $safe(strtoupper($containerSize)) }} × {{ $containerQty }}</div>
                </div>
            @endif
            @if (!$isFcl && $packages > 0)
                <div>
                    <div class="k">Jumlah Koli</div>
                    <div class="v">{{ number_format($packages) }} koli</div>
                </div>
            @endif
            @if (!$isFcl && $weightKg > 0)
                <div>
                    <div class="k">Berat Total</div>
                    <div class="v">{{ number_format($weightKg, 2) }} kg</div>
                </div>
            @endif
            @if (!$isFcl && $volume > 0)
                <div>
                    <div class="k">Volume</div>
                    <div class="v">{{ number_format($volume, 3) }} CBM</div>
                </div>
            @endif
        </div>
    @endif

    @if (filled($shipment->lcl_items) && is_iterable($shipment->lcl_items))
        <div class="card" style="margin-top:8px">
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
                    @foreach ($shipment->lcl_items as $idx => $it)
                        @php
                            $desc = $it['description'] ?? '-';
                            $qty = (int) ($it['qty'] ?? 0);
                            $w = (float) ($it['weight_kg'] ?? 0);
                            $p = (float) ($it['length_cm'] ?? 0);
                            $l = (float) ($it['width_cm'] ?? 0);
                            $t = (float) ($it['height_cm'] ?? 0);
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

    @if (filled($shipment->units) && is_iterable($shipment->units))
        <div class="card" style="margin-top:8px">
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
                    @foreach ($shipment->units as $idx => $unit)
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

    <div class="section-label">Informasi Operasional</div>

    <div class="card">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div>
                @if (filled($shipment->branch?->name ?? $shipment->originBranch?->name))
                    <div class="info-row">
                        <div class="info-label">Cabang Asal</div>
                        <div class="info-value">{{ $safe($shipment->branch?->name ?? $shipment->originBranch?->name) }}</div>
                    </div>
                @endif
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
            </div>
            <div>
                @if (filled($recvName))
                    <div class="info-row">
                        <div class="info-label">Penerima</div>
                        <div class="info-value">{{ $safe($recvName) }}</div>
                    </div>
                @endif
                @if (filled($shipment->doc_number))
                    <div class="info-row">
                        <div class="info-label">No. Dokumen</div>
                        <div class="info-value">{{ $safe($shipment->doc_number) }}</div>
                    </div>
                @endif
                @if (filled($shipment->priority) && $shipment->priority !== 'normal')
                    <div class="info-row">
                        <div class="info-label">Prioritas</div>
                        <div class="info-value"><span
                                class="badge badge-priority">{{ $safe(strtoupper($shipment->priority)) }}</span></div>
                    </div>
                @endif
                @if (filled($shipment->notes))
                    <div class="info-row">
                        <div class="info-label">Catatan</div>
                        <div class="info-value notes-content">{{ e($shipment->notes) }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="tracking-strip">
        <div class="qr-area">
            <div>{!! $qrSvg !!}</div>
            <div class="qr-info">
                <div class="title">Lacak Pengiriman</div>
                <div class="url">{{ e($trackUrl) }}</div>
            </div>
        </div>
        <div class="barcode-area">
            <img alt="BARCODE" src="{{ $barcodeDataUri }}">
            <div class="code">{{ e($shipment->code) }}</div>
        </div>
    </div>

    <div class="footer">
        <strong>{{ e($brand['name']) }}</strong> · Resi ini adalah bukti sah pengiriman.<br>
        Simpan nomor resi untuk pelacakan. Layanan pelanggan: <strong>{{ e($brand['phone']) }}</strong> ·
        {{ e($brand['site']) }}
        @if (isset($depotInfo) && $depotInfo)
            <br><span style="font-size:7.5px;display:block;margin-top:4px">Depo: {{ e($depotInfo['name']) }} ·
                {{ e($depotInfo['address']) }}@if (!empty($depotInfo['phone']))
                    · Telp: {{ e($depotInfo['phone']) }}
                @endif
            </span>
        @endif
    </div>
</body>

</html>

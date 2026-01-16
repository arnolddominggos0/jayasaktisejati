<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Pengiriman</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .email-wrapper {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .highlight-box {
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }
        .highlight-h3 {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 5px solid #17a2b8;
        }
        .highlight-h2 {
            background-color: #fff3cd;
            color: #856404;
            border-left: 5px solid #ffc107;
        }
        .highlight-h1 {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        .highlight-h0 {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .greeting {
            margin-bottom: 15px;
        }
        .info-section {
            margin: 25px 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .info-box h3 {
            color: #495057;
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 40%;
        }
        .value {
            color: #212529;
            flex: 0 0 60%;
            text-align: right;
        }
        .value strong {
            color: #495057;
        }
        .notes-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .notes-box strong {
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
        }
        .footer p {
            margin: 5px 0;
        }
        .divider {
            height: 1px;
            background-color: #dee2e6;
            margin: 25px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                border-radius: 0;
            }
            .content {
                padding: 20px 15px;
            }
            .info-row {
                flex-direction: column;
            }
            .label, .value {
                flex: 1;
                text-align: left;
            }
            .value {
                margin-top: 5px;
                font-weight: 500;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>Notifikasi Pengiriman</h1>
            <p>PT. Jaya Sakti Sejati</p>
        </div>

        <div class="content">
            @if($daysBeforeEta === 3)
                <div class="highlight-box highlight-h3">
                    ⏰ Paket Anda akan tiba dalam <strong>3 HARI</strong>
                </div>
            @elseif($daysBeforeEta === 2)
                <div class="highlight-box highlight-h2">
                    ⏰ Paket Anda akan tiba dalam <strong>2 HARI</strong>
                </div>
            @elseif($daysBeforeEta === 1)
                <div class="highlight-box highlight-h1">
                    ⏰ Paket Anda akan tiba <strong>BESOK</strong>
                </div>
            @else
                <div class="highlight-box highlight-h0">
                    ✅ Paket Anda tiba <strong>HARI INI</strong>
                </div>
            @endif

            <div class="greeting">
                <p>Yth. Bapak/Ibu,</p>
            </div>

            <p>Paket Anda dengan kode <strong>{{ $shipment->code }}</strong> sedang dalam perjalanan dan akan segera tiba. Berikut adalah detail pengiriman:</p>

            <div class="info-section">
                <div class="info-box">
                    <h3>📦 Informasi Pengiriman</h3>
                    
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Kode Pengiriman</span>
                        <span class="value"><strong> {{ $shipment->code }}</strong></span>
                    </div>

                    @php
                        $latestTrack = $shipment->tracks->whereNotNull('tracked_at')->sortByDesc('tracked_at')->first();
                        $trackStatus = $latestTrack ? $latestTrack->status : null;
                    @endphp

                    @if($trackStatus)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Status</span>
                        <span class="value">{{ $trackStatus->label() }}</span>
                    </div>
                    @endif

                    @if($shipment->mode)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Moda Transportasi</span>
                        <span class="value">{{ $shipment->mode->label() }}</span>
                    </div>
                    @endif

                    @if($shipment->priority)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Prioritas</span>
                        <span class="value">{{ ucfirst($shipment->priority) }}</span>
                    </div>
                    @endif
                </div>

                @if($shipment->vessel_name || $shipment->voyage || $shipment->pol || $shipment->pod)
                <div class="info-box">
                    <h3>⛴️ Informasi Kapal & Pelabuhan</h3>
                    
                    @if($shipment->vessel_name)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Nama Kapal</span>
                        <span class="value">{{ $shipment->vessel_name }}</span>
                    </div>
                    @endif

                    @if($shipment->voyage)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Voyage</span>
                        <span class="value">{{ $shipment->voyage }}</span>
                    </div>
                    @endif

                    @if($shipment->pol)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Pelabuhan Muat (POL)</span>
                        <span class="value">{{ $shipment->pol }}</span>
                    </div>
                    @endif

                    @if($shipment->pod)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Pelabuhan Bongkar (POD)</span>
                        <span class="value">{{ $shipment->pod }}</span>
                    </div>
                    @endif
                </div>
                @endif

                <div class="info-box">
                    <h3>📅 Jadwal</h3>
                    
                    @if($shipment->etd)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">ETD (Keberangkatan)</span>
                        <span class="value">{{ $shipment->etd->format('d M Y H:i') }} WIB</span>
                    </div>
                    @endif

                    @if($shipment->eta)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">ETA (Estimasi Tiba)</span>
                        <span class="value"><strong>{{ $shipment->eta->format('d M Y H:i') }} WIB</strong></span>
                    </div>
                    @endif
                </div>

                @if($shipment->container_qty || $shipment->packages_total || $shipment->weight_total || $shipment->cbm_total)
                <div class="info-box">
                    <h3>📊 Detail Muatan</h3>
                    
                    @if($shipment->container_qty)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Jumlah Container</span>
                        <span class="value">{{ $shipment->container_qty }} unit</span>
                    </div>
                    @endif

                    @if($shipment->container_size)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Ukuran Container</span>
                        <span class="value">{{ $shipment->container_size }}</span>
                    </div>
                    @endif

                    @if($shipment->packages_total)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Total Paket</span>
                        <span class="value">{{ $shipment->packages_total }} paket</span>
                    </div>
                    @endif

                    @if($shipment->weight_total)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Total Berat</span>
                        <span class="value">{{ number_format($shipment->weight_total, 2) }} kg</span>
                    </div>
                    @endif

                    @if($shipment->cbm_total)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Total Volume</span>
                        <span class="value">{{ number_format($shipment->cbm_total, 3) }} CBM</span>
                    </div>
                    @endif
                </div>
                @endif

                @if($shipment->delivery_contact_name || $shipment->delivery_contact_phone)
                <div class="info-box">
                    <h3>👤 Kontak Penerima</h3>
                    
                    @if($shipment->delivery_contact_name)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Nama</span>
                        <span class="value">{{ $shipment->delivery_contact_name }}</span>
                    </div>
                    @endif

                    @if($shipment->delivery_contact_phone)
                    <div class="info-row">
                        <span class="label" style="margin-right: 8px;">Telepon</span>
                        <span class="value">{{ $shipment->delivery_contact_phone }}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            @if($shipment->notes)
            <div class="notes-box">
                <strong>📝 Catatan:</strong><br>
                {{ $shipment->notes }}
            </div>
            @endif

            <div class="divider"></div>

            <p style="margin-top: 20px; color: #6c757d;">
                Harap pastikan pihak penerima siap untuk menerima paket pada waktu yang dijadwalkan. Jika ada pertanyaan, silakan hubungi customer service kami.
            </p>
        </div>

        <div class="footer">
            <p><strong>PT. Jasa Sarana Samudra</strong></p>
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} JSS - Sistem Pengiriman. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Lacak {{ $shipment->code }}</title>
    <style>
        body {
            font-family: system-ui, Segoe UI, Arial, sans-serif;
            margin: 24px;
            color: #111
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            max-width: 720px
        }

        .muted {
            color: #6b7280
        }

        .row {
            display: flex;
            gap: 16px
        }

        .col {
            flex: 1
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            background: #e5e7eb
        }

        .timeline {
            margin-top: 12px
        }

        .timeline li {
            margin: 6px 0
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Pelacakan Pengiriman</h2>
        <div class="muted">Nomor Resi</div>
        <h3>{{ $shipment->code }}</h3>

        <div class="row">
            <div class="col">
                <div class="muted">Asal</div>
                <div>{{ $shipment->originCity->name ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="muted">Tujuan</div>
                <div>{{ $shipment->destinationCity->name ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="muted">Status</div>
                <div class="badge">{{ $shipment->status?->label() ?? 'Menunggu' }}</div>
            </div>
        </div>

        <div class="row" style="margin-top:12px">
            <div class="col">
                <div class="muted">ETA</div>
                <div>{{ optional($shipment->eta)->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="muted">Depo Penangan</div>
                <div>{{ $shipment->assignedDepot->name ?? '—' }}</div>
            </div>
        </div>

        <ul class="timeline">
            <li>Permintaan dibuat: {{ optional($shipment->created_at)->format('d M Y H:i') }}</li>
            @if($shipment->etd)<li>ETD: {{ \Illuminate\Support\Carbon::parse($shipment->etd)->format('d M Y') }}</li>@endif
            @if($shipment->eta)<li>ETA: {{ \Illuminate\Support\Carbon::parse($shipment->eta)->format('d M Y') }}</li>@endif
            @if($shipment->status?->value === 'delivered')<li><strong>Terkirim</strong></li>@endif
        </ul>
    </div>
</body>

</html>
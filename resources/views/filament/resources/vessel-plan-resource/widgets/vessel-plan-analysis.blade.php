<div class="rounded-xl border bg-white px-6 py-5 mb-6">

    <h3 class="text-base font-semibold mb-1">Evaluasi Risiko Jadwal</h3>
    <p class="text-sm text-gray-500 mb-4">
        Indikator risiko operasional berdasarkan ETD gap antar kapal.
        Status ini tidak memblokir save, pengiriman ke TAM, maupun finalisasi.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Jumlah Jadwal</div>
            <div class="text-2xl font-semibold">{{ $total }}</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Avg Sailing</div>
            <div class="text-2xl font-semibold">{{ $sailingAvg }} hari</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Max ETD Gap</div>
            <div class="text-2xl font-semibold {{ $gapOk ? 'text-green-600' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $maxGap }} hari
            </div>
            <div class="text-sm mt-1 text-gray-500">Target SOP: {{ $idealGap }} hari</div>
        </div>

        <div class="rounded-lg border {{ $statusBorder }} p-4 {{ $statusBg }}">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Risiko Operasional</div>
            <div class="text-xl font-bold {{ $statusColor }} mt-1">{{ $statusLabel }}</div>
            <div class="text-xs text-gray-500 mt-2">
                Max Gap: {{ $maxGap }} hari &nbsp;·&nbsp; Pelanggaran: {{ $violationCount }}
            </div>
        </div>

    </div>

    @if (!empty($violations))
        @php
            $isCritical  = $riskLevel === 'critical';
            $alertBorder = $isCritical ? 'border-red-200'  : 'border-amber-200';
            $alertBg     = $isCritical ? 'bg-red-50'       : 'bg-amber-50';
            $alertTitle  = $isCritical ? 'text-red-700'    : 'text-amber-700';
            $alertLabel  = $isCritical ? 'Risiko Tinggi'   : 'Peringatan Risiko';
        @endphp
        <div class="rounded-lg border {{ $alertBorder }} {{ $alertBg }} p-4 mt-4">
            <div class="text-xs {{ $alertTitle }} uppercase font-semibold mb-2 tracking-wide">{{ $alertLabel }}</div>
            @foreach ($violations as $v)
                <div class="text-sm {{ $alertTitle }}">{{ $v }}</div>
            @endforeach
            <div class="mt-2 text-xs text-gray-500 italic">
                Jadwal tetap dapat diproses. Pertimbangkan risiko ini dalam koordinasi dengan TAM.
            </div>
        </div>
    @endif

</div>

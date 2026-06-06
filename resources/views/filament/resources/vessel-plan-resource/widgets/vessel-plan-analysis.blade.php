<div class="rounded-xl border bg-white px-6 py-5 mb-6">

    <h3 class="text-base font-semibold mb-1">
        Evaluasi Risiko Jadwal
    </h3>

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
            <div class="text-xl font-bold {{ $statusColor }} mt-1">
                {{ $statusLabel }}
            </div>
            <div class="text-xs text-gray-500 mt-2">
                Max Gap: {{ $maxGap }} hari
                &nbsp;·&nbsp;
                Pelanggaran: {{ $violationCount }}
            </div>
        </div>

    </div>

    @if (!empty($violations))
        @php
            $isWarning = $riskLevel === 'warning';
            $isCritical = $riskLevel === 'critical';
            $alertBorder = $isCritical ? 'border-red-200' : 'border-amber-200';
            $alertBg     = $isCritical ? 'bg-red-50'     : 'bg-amber-50';
            $alertTitle  = $isCritical ? 'text-red-700'  : 'text-amber-700';
            $alertText   = $isCritical ? 'text-red-700'  : 'text-amber-700';
            $alertLabel  = $isCritical ? 'Risiko Tinggi' : 'Peringatan Risiko';
        @endphp
        <div class="rounded-lg border {{ $alertBorder }} {{ $alertBg }} p-4 mt-4">
            <div class="text-xs {{ $alertTitle }} uppercase font-semibold mb-2 tracking-wide">
                {{ $alertLabel }}
            </div>
            @foreach ($violations as $violation)
                <div class="text-sm {{ $alertText }}">{{ $violation }}</div>
            @endforeach
            <div class="mt-2 text-xs text-gray-500 italic">
                Jadwal tetap dapat diproses. Pertimbangkan risiko ini dalam koordinasi dengan TAM.
            </div>
        </div>
    @endif

    @if ($draftPayload || $finalPayload)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            @if ($draftPayload)
                <div class="rounded-lg border p-4">
                    <div class="text-xs text-gray-500 uppercase mb-2">{{ $draftPanelTitle }}</div>
                    <div class="text-sm text-gray-500 mb-2">{{ $draftPanelCaption }}</div>
                    <div class="text-sm text-gray-700">Sailing Avg: {{ $draftPayload['sailing_avg'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Max Gap: {{ $draftPayload['max_gap'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Jadwal: {{ $draftPayload['schedule_count'] ?? 0 }}</div>
                </div>
            @endif

            @if ($finalPayload)
                <div class="rounded-lg border p-4">
                    <div class="text-xs text-gray-500 uppercase mb-2">{{ $finalPanelTitle }}</div>
                    <div class="text-sm text-gray-500 mb-2">{{ $finalPanelCaption }}</div>
                    <div class="text-sm text-gray-700">Sailing Avg: {{ $finalPayload['sailing_avg'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Max Gap: {{ $finalPayload['max_gap'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Jadwal: {{ $finalPayload['schedule_count'] ?? 0 }}</div>
                </div>
            @endif
        </div>
    @endif
</div>

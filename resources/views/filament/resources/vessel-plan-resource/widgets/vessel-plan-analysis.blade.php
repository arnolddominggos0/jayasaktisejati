<div class="rounded-xl border bg-white px-6 py-5 mb-6">

    <h3 class="text-base font-semibold mb-1">
        Validasi Jadwal Kapal (SOP)
    </h3>

    <p class="text-sm text-gray-500 mb-4">
        Evaluasi otomatis continuity jadwal kapal berdasarkan SOP.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Jumlah Jadwal</div>
            <div class="text-2xl font-semibold">{{ $total }}</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Avg Sailing</div>
            <div class="text-2xl font-semibold">{{ $sailingAvg }} hari</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Max Gap ETD</div>
            <div class="text-2xl font-semibold {{ $gapOk ? 'text-green-600' : 'text-red-600' }}">{{ $maxGap }} hari</div>
            <div class="text-sm mt-1 text-gray-600">Batas SOP: {{ $idealGap }} hari</div>
        </div>

        <div class="rounded-lg border p-4 {{ $statusBg }}">
            <div class="text-xs text-gray-500 uppercase">Status SOP</div>
            <div class="text-2xl font-bold {{ $statusColor }}">
                {{ $statusLabel }}
            </div>
            @if (!empty($statusReason))
                <div class="text-sm text-gray-600 mt-2">{{ $statusReason }}</div>
            @endif
        </div>

    </div>

    @if (!empty($violations))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 mt-4">
            <div class="text-xs text-red-700 uppercase mb-2">Pelanggaran SOP</div>
            @foreach ($violations as $violation)
                <div class="text-sm text-red-700">{{ $violation }}</div>
            @endforeach
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
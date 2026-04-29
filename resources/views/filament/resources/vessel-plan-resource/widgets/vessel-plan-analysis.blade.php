<div class="rounded-xl border bg-white px-6 py-5 mb-6">

    <h3 class="text-base font-semibold mb-1">
        Analisa Jadwal Kapal (SOP)
    </h3>

    <p class="text-sm text-gray-500 mb-4">
        Evaluasi otomatis kualitas draft jadwal kapal berdasarkan SOP TAM.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Jumlah Jadwal</div>
            <div class="text-2xl font-semibold">{{ $total }}</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Jarak ETD Maksimal</div>
            <div class="text-2xl font-semibold">{{ $maxGap }} hari</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Batas SOP</div>
            <div class="text-2xl font-semibold">{{ $idealGap }} hari</div>
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

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4">
        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Dwelling</div>
            <div class="text-2xl font-semibold">{{ $dwelling }} hari</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Sailing Avg</div>
            <div class="text-2xl font-semibold">{{ $sailingAvg }} hari</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Dooring</div>
            <div class="text-2xl font-semibold">{{ $dooring }} hari</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Total KPI</div>
            <div class="text-2xl font-semibold">{{ $totalKpi }} hari</div>
            <div class="text-sm mt-2 {{ $kpiOk ? 'text-green-600' : 'text-red-600' }}">
                {{ $kpiOk ? 'Status KPI: Sesuai SOP' : 'Status KPI: Melebihi batas ' . $kpiLimit . ' hari' }}
            </div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 uppercase">Status Gap ETD</div>
            <div class="text-2xl font-semibold {{ $gapOk ? 'text-green-600' : 'text-red-600' }}">
                {{ $gapOk ? 'SESUAI SOP' : 'MELEBIHI SOP' }}
            </div>
            <div class="text-sm mt-2 text-gray-600">
                Batas maksimal {{ $idealGap }} hari
            </div>
        </div>
    </div>

    @if (!empty($violations))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 mt-4">
            <div class="text-xs text-red-700 uppercase mb-2">Catatan Evaluasi</div>
            @foreach ($violations as $violation)
                <div class="text-sm text-red-700">{{ $violation }}</div>
            @endforeach
        </div>
    @endif

    @if ($draftKpi || $finalKpi)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div class="rounded-lg border p-4">
                <div class="text-xs text-gray-500 uppercase mb-2">{{ $draftPanelTitle }}</div>
                <div class="text-sm text-gray-500 mb-2">{{ $draftPanelCaption }}</div>
                @if ($draftKpi)
                    <div class="text-sm text-gray-700">Dwelling: {{ $draftKpi['dwelling'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Sailing: {{ $draftKpi['sailing_avg'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Dooring: {{ $draftKpi['dooring'] ?? 0 }} hari</div>
                    <div class="text-sm font-semibold text-gray-900">Total: {{ $draftKpi['total'] ?? 0 }} hari</div>
                @endif
            </div>

            <div class="rounded-lg border p-4">
                <div class="text-xs text-gray-500 uppercase mb-2">{{ $finalPanelTitle }}</div>
                <div class="text-sm text-gray-500 mb-2">{{ $finalPanelCaption }}</div>
                @if ($finalKpi)
                    <div class="text-sm text-gray-700">Dwelling: {{ $finalKpi['dwelling'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Sailing: {{ $finalKpi['sailing_avg'] ?? 0 }} hari</div>
                    <div class="text-sm text-gray-700">Dooring: {{ $finalKpi['dooring'] ?? 0 }} hari</div>
                    <div class="text-sm font-semibold text-gray-900">Total: {{ $finalKpi['total'] ?? 0 }} hari</div>
                @endif
            </div>
        </div>
    @endif
</div>

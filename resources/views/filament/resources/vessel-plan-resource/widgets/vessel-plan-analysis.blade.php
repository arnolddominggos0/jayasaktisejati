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
        </div>

    </div>
</div>

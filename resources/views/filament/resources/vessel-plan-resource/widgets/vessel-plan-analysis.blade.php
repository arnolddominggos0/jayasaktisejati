<div class="rounded-xl border bg-white px-6 py-5 mb-6">

    <div class="mb-4">
        <h3 class="text-base font-semibold text-gray-900">
            Analisa Jadwal Kapal (SOP)
        </h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

        <div class="rounded-lg border p-4">
            <div class="text-xs uppercase text-gray-500">Jumlah Kapal</div>
            <div class="mt-1 text-2xl font-semibold">{{ $total }}</div>
        </div>

        <div class="rounded-lg border p-4">
            <div class="text-xs uppercase text-gray-500">Jarak ETD Maksimal</div>
            <div class="mt-1 text-2xl font-semibold">{{ $maxGap }} hari</div>
        </div>

        <div class="rounded-lg border p-4"> 
            <div class="text-xs uppercase text-gray-500">Batas SOP</div>
            <div class="mt-1 text-2xl font-semibold">{{ $idealGap }} hari</div>
        </div>

        <div class="rounded-lg border p-4 {{ $statusBg }}">
            <div class="text-xs uppercase text-gray-500">Status Analisa</div>
            <div class="mt-1 text-2xl font-bold {{ $statusColor }}">
                {{ $statusLabel }}
            </div>
        </div>

    </div>
</div>
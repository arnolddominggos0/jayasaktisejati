<x-filament::section>
    <x-slot name="heading">
        Dashboard Jadwal Final (Operasional)
    </x-slot>

    <x-slot name="description">
        Ringkasan performa jadwal kapal setelah finalisasi
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-gray-500 uppercase">Total Voyage</div>
            <div class="text-2xl font-bold">{{ $totalVoyages }}</div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-gray-500 uppercase">Total Cargo Plan</div>
            <div class="text-2xl font-bold">
                {{ number_format($totalCargoPlan ?? 0) }}
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-gray-500 uppercase">Avg Dwelling</div>
            <div class="text-2xl font-bold">
                {{ $avgDwelling }} hari
            </div>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-gray-500 uppercase">Voyage Delay</div>
            <div class="text-2xl font-bold text-red-600">
                {{ $delayCount }}
            </div>
        </div>
    </div>
</x-filament::section>

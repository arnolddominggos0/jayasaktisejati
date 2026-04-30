<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-semibold">
                    📅 Jadwal Kapal — <span class="font-bold">Oktober 2025</span> <span
                        class="text-gray-400">(Mock)</span>
                </h2>
                <span class="px-2 py-0.5 text-xs font-medium text-green-800 bg-green-100 rounded">Final</span>
            </div>
            <div class="flex items-center gap-2">
                <x-filament::button size="sm" tag="a"
                    href="{{ route('filament.admin.resources.shipping-schedules.index') }}">
                    Lihat Tabel Jadwal
                </x-filament::button>
                <x-filament::button size="sm" color="primary" tag="a"
                    href="{{ route('filament.admin.resources.shipping-schedules.create') }}">
                    + Buat Draft
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="p-3 rounded border bg-blue-50 border-blue-200 min-w-0 h-24">
                <div class="text-xs text-blue-700">Total Kapal</div>
                <div class="text-2xl font-bold text-blue-900 mt-1 leading-none truncate">3</div>
                <div class="text-xs text-blue-700">voyage</div>
            </div>
            <div class="p-3 rounded border bg-orange-50 border-orange-200 min-w-0 h-24">
                <div class="text-xs text-orange-700">Total Kapasitas</div>
                <div class="text-2xl font-bold text-orange-900 mt-1 leading-none truncate">25</div>
                <div class="text-xs text-orange-700">unit</div>
            </div>
            <div class="p-3 rounded border bg-green-50 border-green-200 min-w-0 h-24">
                <div class="text-xs text-green-700">Rata-rata/Minggu</div>
                <div class="text-2xl font-bold text-green-900 mt-1 leading-none truncate">0.8</div>
                <div class="text-xs text-green-700">kapal</div>
            </div>
            <div class="p-3 rounded border bg-purple-50 border-purple-200 min-w-0 h-24">
                <div class="text-xs text-purple-700">Status Jadwal</div>
                <div class="text-base font-bold text-purple-900 mt-1 leading-none">Final</div>
                <div class="text-xs text-purple-700">Siap digunakan</div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>

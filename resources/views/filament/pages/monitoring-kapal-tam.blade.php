<x-filament-panels::page>
    <div class="space-y-8">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Kapal TAM</h1>
                <p class="text-sm text-gray-500">Sistem Operasional Armada</p>
            </div>

            <div class="flex gap-3">
                <input
                    wire:model.live="search"
                    placeholder="Cari kapal / pelayaran"
                    class="rounded-xl border-gray-300 text-sm w-64">

                <select
                    wire:model.live="period"
                    class="rounded-xl border-gray-300 text-sm">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 border-b pb-3">
            @foreach ([
                'control' => 'Pusat Kendali',
                'performance' => 'Kinerja',
                'calendar' => 'Kalender',
            ] as $key => $label)

                <button
                    wire:click="$set('mode','{{ $key }}')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold
                        {{ $mode === $key ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                    {{ $label }}
                </button>

            @endforeach
        </div>

        @if ($mode === 'control')

            <div class="grid grid-cols-6 gap-4">

                <div class="bg-white rounded-xl border p-4">
                    <div class="text-xs text-gray-500 uppercase">Total Pelayaran</div>
                    <div class="text-2xl font-bold">
                        {{ $summary['total'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                    <div class="text-xs text-red-600 uppercase">Kritis</div>
                    <div class="text-2xl font-bold text-red-700">
                        {{ $summary['critical'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-orange-50 rounded-xl border border-orange-200 p-4">
                    <div class="text-xs text-orange-600 uppercase">Menengah</div>
                    <div class="text-2xl font-bold text-orange-700">
                        {{ $summary['medium'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-4">
                    <div class="text-xs text-yellow-600 uppercase">Ringan</div>
                    <div class="text-2xl font-bold text-yellow-700">
                        {{ $summary['minor'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                    <div class="text-xs text-red-600 uppercase">SLA Tidak Tercapai</div>
                    <div class="text-2xl font-bold text-red-700">
                        {{ $summary['sla_fail'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-orange-50 rounded-xl border border-orange-200 p-4">
                    <div class="text-xs text-orange-600 uppercase">Belum Tiba</div>
                    <div class="text-2xl font-bold text-orange-700">
                        {{ $summary['no_ata'] ?? 0 }}
                    </div>
                </div>

            </div>

            @include('filament.pages.partials.tam-monitoring-table')

        @endif


        @if ($mode === 'performance')
            @include('filament.pages.partials.tam-achievments')
        @endif


        @if ($mode === 'calendar')
            @include('filament.pages.partials.tam-calendar')
        @endif

    </div>
</x-filament-panels::page>
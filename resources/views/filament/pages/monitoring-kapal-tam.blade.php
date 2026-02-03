<x-filament-panels::page>
    <div class="space-y-8">

        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Jadwal Kapal TAM</h1>
                <p class="text-sm text-gray-500">
                    Monitoring jadwal pelayaran dan evaluasi kinerja SLA sailing
                </p>
            </div>

            <div class="flex gap-2">
                <select wire:model.live="period" class="w-56 rounded-xl border-gray-300 text-sm">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filter" class="w-48 rounded-xl border-gray-300 text-sm">
                    <option value="all">Semua</option>
                    <option value="ongoing">Sedang Berjalan</option>
                    <option value="risk">Berisiko</option>
                    <option value="late">SLA Tidak Tercapai</option>
                </select>
            </div>
        </div>

        @include('filament.pages.partials.tam-kpi')
        @include('filament.pages.partials.tam-calendar')
        @include('filament.pages.partials.tam-monitoring-table')

    </div>
</x-filament-panels::page>
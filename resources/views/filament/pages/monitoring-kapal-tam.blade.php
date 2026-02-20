<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Header --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                        Monitoring Kapal TAM
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Monitoring OTD, OTA, dan Transit SLA
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select wire:model.live="period"
                        class="w-full sm:w-56 rounded-xl border-gray-300 text-sm
                               focus:border-primary-500 focus:ring-primary-500">
                        @foreach ($monthOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filter"
                        class="w-full sm:w-48 rounded-xl border-gray-300 text-sm
                               focus:border-primary-500 focus:ring-primary-500">
                        <option value="all">Semua</option>
                        <option value="ongoing">Sedang Berjalan</option>
                        <option value="risk">Berisiko</option>
                        <option value="late">SLA Tidak Tercapai</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Shipping Performance KPI --}}
        @include('filament.pages.partials.tam-achievements')

        {{-- Calendar --}}
        @include('filament.pages.partials.tam-calendar')

        {{-- Detail Monitoring --}}
        @include('filament.pages.partials.tam-monitoring-table')
    </div>
</x-filament-panels::page>
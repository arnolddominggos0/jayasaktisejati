<x-filament-panels::page>
    <div class="space-y-8">

        {{-- HEADER --}}
        <div class="flex items-start justify-between pt-2">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Jadwal Kapal TAM</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Monitoring SLA dan evaluasi pelayaran kapal
                </p>
            </div>

            <div class="flex gap-2">
                <select wire:model.live="period" class="w-56 rounded-xl border-gray-300 shadow-sm text-sm">
                    @foreach ($this->monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filter" class="w-48 rounded-xl border-gray-300 shadow-sm text-sm">
                    <option value="all">Semua</option>
                    <option value="ongoing">Sedang Berjalan</option>
                    <option value="risk">Berisiko</option>
                    <option value="late">Terlambat</option>
                </select>
            </div>
        </div>

        {{-- KPI --}}
        @include('filament.pages.partials.tam-kpi')

        {{-- CALENDAR --}}
        @include('filament.pages.partials.tam-calendar')

        {{-- MONITORING TABLE --}}
        @include('filament.pages.partials.tam-monitoring-table')

    </div>
</x-filament-panels::page>

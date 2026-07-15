{{--
    List Vessel Plan — Sprint "UX Polish": Tahun sebagai context selector.

    Bukan bawaan Filament (filament-panels::resources.pages.list-records) —
    versi native selalu membawa <x-filament-panels::resources.tabs /> (tidak
    dipakai di sini) dan, kalau ->filters() dipakai di resource, panel
    "Filter" penuh + "Filter Aktif" + "Reset" (furniture yang tidak perlu
    untuk satu context selector). Struktur di sini sengaja dibuat ringan:
    breadcrumb + judul tetap native (dari <x-filament-panels::page>), lalu
    dropdown Tahun sebagai elemen halaman biasa tepat di bawah judul,
    baru tabel.
--}}
<x-filament-panels::page>

    <div class="flex items-center gap-2">
        <label for="vp-year-select" class="text-sm font-medium text-gray-600">
            Tahun
        </label>
        <select
            id="vp-year-select"
            wire:model.live="year"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
        >
            @foreach ($this->getYearOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{ $this->table }}

</x-filament-panels::page>

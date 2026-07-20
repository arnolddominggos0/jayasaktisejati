{{--
    WS-01A — Administrative Workspace Shell (Permintaan Pengiriman)

    Struktur mengikuti JSS Workspace Design Language v1.0 (baseline Vessel Plan):
    Tab Navigation → ONE workspace box (Toolbar → Table), dipisah divider,
    bukan card terpisah. Kelas .vp-tab* / .vp-workspace* di-reuse dari
    theme.css; tambahan kelas .ws-* hanya untuk toolbar shell (lihat blok
    "WS-01A" di theme.css).

    Tab belum memfilter (tab query = WS-01B). Search tersambung ke
    tableSearch bawaan Filament supaya fungsi pencarian existing tidak
    regresi.

    WS-01A.1 — Filter panel (FiltersLayout::AboveContent bawaan resource)
    default collapsed; dibuka dari tombol Filter toolbar via class
    .ws-filters-open (transisi + klik-luar menutup, lihat theme.css).
    Filter logic tidak disentuh — hanya presentasinya.

    UX-POLISH-01 — heading "Filters" + link "Reset filters" merah bawaan
    Filament disembunyikan via CSS (scoped ke .ws-shipment-workspace, lihat
    theme.css) karena hanya tersisa 2 quick filter (Customer, Kota Tujuan).
    Digantikan tombol "Atur ulang filter" kustom di toolbar (gray, kecil,
    hanya tampil saat isFiltered() true) yang memanggil method Livewire
    bawaan resetTableFiltersForm — sama persis dengan link native, tidak ada
    logic filter baru.
--}}
<x-filament-panels::page>
    <div
        x-data="{ filtersOpen: false }"
        x-on:click.window="
            if (
                filtersOpen
                && ! $event.target.closest('.fi-ta-filters-above-content-ctn')
                && ! $event.target.closest('.ws-filter-toggle')
            ) filtersOpen = false
        "
    >
        {{-- UX-LIST-02 — Tab dikendalikan Livewire (activeTab native
             ListRecords) sehingga BENAR-BENAR menyaring query; URL state
             (?tab=) via #[Url] di page class, bukan Alpine lagi. --}}
        <div class="vp-workspace-toolbar-row ws-nav-row">
            <nav class="vp-tab-bar" role="tablist" aria-label="Segmen permintaan pengiriman">
                @foreach ($this->getTabs() as $key => $tab)
                    @php $isActive = $this->activeTab === (string) $key; @endphp
                    <button
                        type="button"
                        role="tab"
                        class="vp-tab {{ $isActive ? 'is-active' : '' }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        wire:click="$set('activeTab', @js((string) $key))"
                        wire:loading.attr="disabled"
                    >
                        {{ $tab->getLabel() }}
                        @if (filled($badge = $tab->getBadge()))
                            <span class="ws-tab-badge">{{ $badge }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Workspace Box — satu surface: Toolbar → (Filter panel) → Table --}}
        <div
            class="vp-workspace ws-shipment-workspace"
            :class="{ 'ws-filters-open': filtersOpen }"
        >
            <div class="ws-toolbar">
                {{-- Prioritas toolbar: Search (dominan) → Filter (sekunder) → Export (tersier, overflow) --}}
                <div class="ws-toolbar-search">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.500ms="tableSearch"
                            placeholder="Cari no. permintaan, SPPB/DO, customer…"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="ws-toolbar-utils">
                    @if ($this->getTable()->isFiltered())
                        <button
                            type="button"
                            wire:click="resetTableFiltersForm"
                            wire:loading.attr="disabled"
                            wire:target="resetTableFiltersForm"
                            class="ws-filter-reset"
                        >
                            Atur ulang filter
                        </button>
                    @endif

                    <x-filament::button
                        color="gray"
                        icon="heroicon-m-funnel"
                        class="ws-filter-toggle"
                        x-on:click="filtersOpen = ! filtersOpen"
                        x-bind:aria-expanded="filtersOpen ? 'true' : 'false'"
                    >
                        Filter
                    </x-filament::button>

                    <x-filament::dropdown placement="bottom-end">
                        <x-slot name="trigger">
                            <x-filament::icon-button
                                icon="heroicon-m-ellipsis-vertical"
                                color="gray"
                                label="Menu lainnya"
                            />
                        </x-slot>

                        <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item
                                icon="heroicon-m-arrow-down-tray"
                                wire:click="mountAction('export')"
                            >
                                Export CSV
                            </x-filament::dropdown.list.item>
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                </div>
            </div>

            <div class="vp-workspace-table">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>

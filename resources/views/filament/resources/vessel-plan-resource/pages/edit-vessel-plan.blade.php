@php
/**
 * Tab 1 (schedule) tetap ter-mount lewat x-show agar Livewire form dan
 * relation manager tidak kehilangan state saat berpindah tab. Tab 2 dan 3
 * blade-only sehingga aman dibungkus x-show juga.
 */

$record = $this->record;
$items  = $record->items->sortBy('planned_etd')->values();
$shippingLines = $items->pluck('shippingLine')->filter()->unique('id')->sortBy('name')->values();

// Riwayat Jadwal membandingkan draft snapshot dengan final snapshot —
// tanpa final snapshot tidak ada apa pun untuk dibandingkan.
$hasFinalSnapshot = $record->finalSnapshot() !== null;

// Tab yang paling relevan berbeda tergantung fase: Draft/Revision berarti
// masih menyusun jadwal, Sent berarti menunggu/mencatat hasil dari TAM
// sehingga bukti kelayakan (Review Jadwal) yang perlu dibaca dulu, Final
// berarti tidak ada lagi yang perlu disusun sehingga perbandingan jadwal
// akhir (Riwayat Jadwal) yang paling relevan — kecuali belum ada snapshot
// final untuk dibandingkan, maka Review Jadwal tetap jadi fallback yang
// paling relevan. Query string ?tab= tetap menang kalau user membuka
// tautan langsung ke tab tertentu.
$defaultTab = match (true) {
    $record->isFinal() => $hasFinalSnapshot ? 'history' : 'analysis',
    $record->isSent()  => 'analysis',
    default             => 'schedule',
};
@endphp

<x-filament-panels::page
    @class([
        'fi-resource-edit-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>

    <div
        x-data="{
            tab: new URLSearchParams(window.location.search).get('tab') || '{{ $defaultTab }}'
        }"
        x-init="
            $watch('tab', v => {
                const u = new URL(window.location);
                u.searchParams.set('tab', v);
                window.history.replaceState({}, '', u);
            })
        "
    >
        {{-- Tab bar --}}
        <div class="vp-tab-bar">

            <button type="button" @click="tab = 'schedule'" :class="tab === 'schedule' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-calendar-days class="w-4 h-4" />
                Jadwal
            </button>

            <button type="button" @click="tab = 'analysis'" :class="tab === 'analysis' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-chart-bar-square class="w-4 h-4" />
                Review Jadwal
            </button>

            @if ($hasFinalSnapshot)
            <button type="button" @click="tab = 'history'" :class="tab === 'history' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-clock class="w-4 h-4" />
                Riwayat Jadwal
            </button>
            @endif

        </div>

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 1 — Jadwal (Form + RelationManager)
             Menggunakan x-show agar Livewire tetap di-mounted
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'schedule'">

            {{-- Workspace Filter: kontrol terhadap isi workspace (filter,
                 nantinya search/sort/export/bulk action/view options).
                 Card Filament sendiri, terpisah dari Planning Summary —
                 filter adalah control, summary adalah information. Livewire
                 property shippingLineFilter dispatch 'vpFilterShippingLine'
                 ke RelationManager untuk live update tanpa reload halaman. --}}
            @if ($shippingLines->count() > 1)
                <x-filament::section compact class="vp-filter-section">
                    <div class="vp-toolbar">
                        <span class="vp-toolbar-label">Shipping Line</span>
                        <select
                            wire:model.live="shippingLineFilter"
                            class="text-sm rounded-md border-gray-300 shadow-sm py-1.5 pl-2.5 pr-8 leading-none bg-white text-gray-700 focus:border-primary-500 focus:ring-primary-500 cursor-pointer"
                            aria-label="Shipping Line"
                        >
                            <option value="">Semua</option>
                            @foreach ($shippingLines as $line)
                                <option value="{{ $line->id }}">{{ $line->name }}</option>
                            @endforeach
                        </select>
                        @if (filled($this->shippingLineFilter))
                            <button type="button" wire:click="$set('shippingLineFilter', '')" class="text-xs text-gray-500 hover:text-gray-700 underline cursor-pointer">
                                Reset Filter
                            </button>
                        @endif
                    </div>
                </x-filament::section>
            @endif

            {{-- Header, toolbar Simpan/Batal, dan tabel adalah satu workspace
                 surface (.vp-workspace), dipisah divider — bukan card terpisah.
                 Status & POL/POD sengaja tidak diulang di sini — sudah ada di Header. --}}
            <div class="vp-workspace">

                {{-- Workspace Header: heading aktivitas, bukan statistik —
                     jumlah jadwal sudah ada di Planning Summary. --}}
                <div class="vp-workspace-header">
                    <div class="min-w-0">
                        <div class="vp-workspace-title">Jadwal Kapal</div>
                        <p class="vp-workspace-subtitle truncate">
                            @if ($record->isFinal())
                                Jadwal telah difinalisasi.
                            @elseif ($record->isSent() || $record->isRevision())
                                Menunggu penyesuaian Final Schedule dari TAM.
                            @else
                                Susun dan kelola jadwal kapal sebelum dikirim ke TAM.
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Toolbar Simpan/Batal — divider, bukan kotak terpisah.
                     Livewire form wiring (wire:submit="save") preserved as-is. --}}
                @capture($form)
                    @unless($record->isFinal())
                        <div class="vp-workspace-toolbar">
                            <x-filament-panels::form
                                id="form"
                                :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
                                wire:submit="save"
                            >
                                {{ $this->form }}

                                <x-filament-panels::form.actions
                                    :actions="$this->getCachedFormActions()"
                                    :full-width="$this->hasFullWidthFormActions()"
                                />
                            </x-filament-panels::form>
                        </div>
                    @endunless
                @endcapture

                @php
                    $relationManagers                          = $this->getRelationManagers();
                    $hasCombinedRelationManagerTabsWithContent = $this->hasCombinedRelationManagerTabsWithContent();
                @endphp

                @if ((! $hasCombinedRelationManagerTabsWithContent) || (! count($relationManagers)))
                    {{ $form() }}
                @endif

                @if (count($relationManagers))
                    {{-- Nuansa fase: Draft netral, Sent/Revision aksen biru, Final redup terkunci.
                         vp-workspace-table: menyatukan tabel ke dalam surface yang sama
                         (lihat theme.css — box native Filament .fi-ta-ctn dilepas). --}}
                    <div @class([
                        'vp-phase',
                        'vp-workspace-table',
                        'vp-phase-final' => $record->isFinal(),
                        'vp-phase-draft' => $record->isDraft(),
                        'vp-phase-sent'  => ! $record->isFinal() && ! $record->isDraft(),
                    ])>
                        <x-filament-panels::resources.relation-managers
                            :active-locale="isset($activeLocale) ? $activeLocale : null"
                            :active-manager="$this->activeRelationManager ?? ($hasCombinedRelationManagerTabsWithContent ? null : array_key_first($relationManagers))"
                            :content-tab-label="$this->getContentTabLabel()"
                            :content-tab-icon="$this->getContentTabIcon()"
                            :content-tab-position="$this->getContentTabPosition()"
                            :managers="$relationManagers"
                            :owner-record="$record"
                            :page-class="static::class"
                        >
                            @if ($hasCombinedRelationManagerTabsWithContent)
                                <x-slot name="content">
                                    {{ $form() }}
                                </x-slot>
                            @endif
                        </x-filament-panels::resources.relation-managers>
                    </div>
                @endif

            </div>
            {{-- /vp-workspace --}}

            <x-filament-panels::page.unsaved-data-changes-alert />

        </div>
        {{-- /tab:schedule --}}

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 2 — Review Jadwal (Decision Review Workspace)
             Blade-only, read-only — menggunakan x-show + x-cloak
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'analysis'" x-cloak>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                @include(
                    'filament.resources.vessel-plan-resource.tabs.schedule-analysis',
                    ['record' => $record, 'items' => $items]
                )
            </div>
        </div>
        {{-- /tab:analysis --}}

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 3 — Riwayat Jadwal (Schedule History)
             Draft vs Final + delta + detail drawer Alpine.js
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'history'" x-cloak>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                @include(
                    'filament.resources.vessel-plan-resource.tabs.schedule-history',
                    ['record' => $record, 'items' => $items]
                )
            </div>
        </div>
        {{-- /tab:history --}}

    </div>
    {{-- /x-data tabs --}}

</x-filament-panels::page>

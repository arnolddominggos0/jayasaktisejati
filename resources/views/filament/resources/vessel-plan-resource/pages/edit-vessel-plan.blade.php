@php
/**
 * Tab 1 (schedule) tetap ter-mount lewat x-show agar Livewire form dan
 * relation manager tidak kehilangan state saat berpindah tab. Tab 2 dan 3
 * blade-only sehingga aman dibungkus x-show juga.
 */

$record = $this->record;
$items  = $record->items->sortBy('planned_etd');

// Riwayat Jadwal membandingkan draft snapshot dengan final snapshot —
// tanpa final snapshot tidak ada apa pun untuk dibandingkan.
$hasFinalSnapshot = $record->finalSnapshot() !== null;

// Tab yang paling relevan berbeda tergantung fase: Draft/Revision berarti
// masih menyusun jadwal, Sent berarti menunggu/mencatat hasil dari TAM
// sehingga bukti kelayakan (Review Jadwal) yang perlu dibaca dulu, Final
// berarti tidak ada lagi yang perlu disusun sehingga perbandingan jadwal
// akhir (Riwayat Jadwal) yang paling relevan. Query string ?tab= tetap
// menang kalau user membuka tautan langsung ke tab tertentu.
$defaultTab = match (true) {
    $record->isSent()                       => 'analysis',
    $record->isFinal() && $hasFinalSnapshot => 'history',
    default                                  => 'schedule',
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

            {{-- Sprint 15.1 — One Tab = One Workspace: header, toolbar Simpan/
                 Batal, dan tabel dulu 3 kotak terpisah (gap kosong di antara
                 masing-masing + double heading "Jadwal Kapal" vs "Daftar
                 Jadwal" dari getTableHeading()). Sekarang satu surface
                 (.vp-workspace), dipisah divider (border-t), bukan gap+card.
                 Toolbar dispatch Livewire event 'vpFilterShippingLine' ke
                 RelationManager untuk live update tanpa reload halaman.
                 Status pill & POL/POD sengaja tidak di sini — sudah ada di Hero. --}}
            @php
                $shippingLines = $items
                    ->pluck('shippingLine')
                    ->filter()
                    ->unique('id')
                    ->sortBy('name')
                    ->values();
            @endphp

            <div class="vp-workspace">

                {{-- Workspace Header: judul + subtitle + filter (satu-satunya
                     heading Tab Jadwal — getTableHeading() dikosongkan supaya
                     tidak ada "Daftar Jadwal" bersaing di bawahnya). --}}
                <div class="vp-workspace-header">
                    <div class="min-w-0">
                        <div class="vp-workspace-title">Jadwal Kapal</div>
                        <p class="vp-workspace-subtitle truncate">
                            @if ($record->isFinal())
                                {{ $items->count() }} jadwal kapal telah difinalisasi.
                            @elseif ($record->isSent() || $record->isRevision())
                                {{ $items->count() }} jadwal kapal menunggu penyesuaian Final Schedule dari TAM.
                            @else
                                {{ $items->count() }} jadwal kapal — susun sebelum dikirim ke TAM.
                            @endif
                        </p>
                    </div>

                    {{-- Filter Shipping Line: label inline + dropdown, bukan search box. --}}
                    @if ($shippingLines->count() > 1)
                        <div
                            class="flex items-center gap-2"
                            x-data="{ shippingLine: '' }"
                        >
                            <span class="text-xs text-gray-500 font-medium">Shipping Line</span>
                            <select
                                x-model="shippingLine"
                                wire:change="$dispatch('vpFilterShippingLine', { value: $event.target.value })"
                                class="text-sm rounded-md border-gray-300 shadow-sm py-1.5 pl-2.5 pr-8 leading-none bg-white text-gray-700 focus:border-primary-500 focus:ring-primary-500 cursor-pointer"
                                aria-label="Shipping Line"
                            >
                                <option value="">Semua</option>
                                @foreach ($shippingLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>

                            <button
                                type="button"
                                x-show="shippingLine !== ''"
                                x-cloak
                                x-on:click="shippingLine = ''; $dispatch('vpFilterShippingLine', { value: '' })"
                                class="text-xs text-gray-500 hover:text-gray-700 underline cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>
                    @endif
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
            {{-- Sprint 15.1 — rounded-2xl+shadow-sm -> rounded-xl tanpa shadow:
                 selaras dengan .vp-workspace (Tab Jadwal) & Object Header
                 (Sprint 14.7 FINAL) — satu bahasa radius (12px) dan "Divider
                 > Border, Whitespace > Shadow" di seluruh 3 tab. --}}
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
            {{-- Sprint 15.1 — rounded-2xl+shadow-sm -> rounded-xl tanpa shadow:
                 selaras dengan .vp-workspace (Tab Jadwal) & Object Header
                 (Sprint 14.7 FINAL) — satu bahasa radius (12px) dan "Divider
                 > Border, Whitespace > Shadow" di seluruh 3 tab. --}}
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

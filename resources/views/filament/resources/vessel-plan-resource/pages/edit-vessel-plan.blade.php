@php
/**
 * Edit Vessel Plan — 3-tab layout
 *
 * Tab 1 — Jadwal   : Form edit + RelationManager + slim toolbar Shipping Line
 * Tab 2 — Review   : Review Jadwal — Decision Support Workspace
 * Tab 3 — Riwayat  : Schedule History — Draft vs Final, delta, detail drawer
 *
 * Catatan:
 *  - Tab 1 menggunakan x-show agar Livewire form + relation manager tetap
 *    di-mounted dan tidak kehilangan state saat pindah tab.
 *  - Toolbar Shipping Line di Tab 1 dispatch Livewire event ke RM child
 *    (vpFilterShippingLine) untuk live update tabel tanpa reload halaman.
 *  - Tab 2 & 3 merupakan blade-only (no Livewire) — aman dibungkus x-show.
 *  - Relasi di-eager-load di mount() EditVesselPlan.php.
 */

$record = $this->record;
$items  = $record->items->sortBy('planned_etd');
@endphp

<x-filament-panels::page
    @class([
        'fi-resource-edit-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>

    {{-- ──────────────────────────────────────────────────────────────────────
         Tab Navigation — persisten via URL ?tab=
         Default: 'schedule'
    ───────────────────────────────────────────────────────────────────────── --}}
    <div
        x-data="{
            tab: new URLSearchParams(window.location.search).get('tab') || 'schedule'
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

            <button type="button" @click="tab = 'history'" :class="tab === 'history' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-clock class="w-4 h-4" />
                Riwayat Jadwal
            </button>

        </div>

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 1 — Jadwal (Form + RelationManager)
             Menggunakan x-show agar Livewire tetap di-mounted
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'schedule'">

            {{-- Header card Tab Jadwal: identitas + slim toolbar filter Shipping Line.
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

            <div class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 mb-2">
                <div class="flex items-center justify-between flex-wrap gap-x-4 gap-y-2">
                    <div class="min-w-0">
                        <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500">Jadwal Kapal</div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
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
            </div>

            {{-- Form actions (Simpan/Batal) menempel sebagai toolbar tabel.
                 Livewire form wiring (wire:submit="save") preserved as-is. --}}
            @capture($form)
                @unless($record->isFinal())
                    <div class="rounded-t-lg border border-gray-200 border-b-0 bg-gray-50 px-4 py-2 flex justify-end gap-2">
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
                {{-- Nuansa fase: Draft netral, Sent/Revision aksen biru, Final redup terkunci --}}
                <div @class([
                    'vp-phase',
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

            <x-filament-panels::page.unsaved-data-changes-alert />

        </div>
        {{-- /tab:schedule --}}

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 2 — Review Jadwal (Decision Review Workspace)
             Blade-only, read-only — menggunakan x-show + x-cloak
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'analysis'" x-cloak>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-5">
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
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-5">
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

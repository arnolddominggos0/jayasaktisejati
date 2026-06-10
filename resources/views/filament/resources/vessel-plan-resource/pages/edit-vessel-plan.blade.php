@php
/**
 * Edit Vessel Plan — 3-tab layout
 *
 * Tab 1 — Jadwal   : Form edit + RelationManager (vessel plan items)
 * Tab 2 — Analisis : Final Schedule Analysis (read-only, TAM standards)
 * Tab 3 — Riwayat  : Schedule History — Draft vs Final, delta, detail drawer
 *
 * Catatan:
 *  - Tab 1 menggunakan x-show agar Livewire form + relation manager tetap
 *    di-mount dan tidak kehilangan state saat pindah tab.
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
        <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 mb-6">

            <button
                @click="tab = 'schedule'"
                :class="tab === 'schedule'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-calendar-days class="w-4 h-4" />
                    Jadwal
                </div>
            </button>

            <button
                @click="tab = 'analysis'"
                :class="tab === 'analysis'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-chart-bar-square class="w-4 h-4" />
                    Analisis Jadwal
                </div>
            </button>

            <button
                @click="tab = 'history'"
                :class="tab === 'history'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-clock class="w-4 h-4" />
                    Riwayat Jadwal
                </div>
            </button>

        </div>

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 1 — Jadwal (Form + RelationManager)
             Menggunakan x-show agar Livewire tetap di-mount
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'schedule'">

            @capture($form)
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
            @endcapture

            @php
                $relationManagers                          = $this->getRelationManagers();
                $hasCombinedRelationManagerTabsWithContent = $this->hasCombinedRelationManagerTabsWithContent();
            @endphp

            @if ((! $hasCombinedRelationManagerTabsWithContent) || (! count($relationManagers)))
                {{ $form() }}
            @endif

            @if (count($relationManagers))
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
            @endif

            <x-filament-panels::page.unsaved-data-changes-alert />

        </div>
        {{-- /tab:schedule --}}

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 2 — Analisis Jadwal (Final Schedule Analysis)
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

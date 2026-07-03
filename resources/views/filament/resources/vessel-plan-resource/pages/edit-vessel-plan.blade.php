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
        <div class="vp-tab-bar">

            <button @click="tab = 'schedule'" :class="tab === 'schedule' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-calendar-days class="w-4 h-4" />
                Jadwal
            </button>

            <button @click="tab = 'analysis'" :class="tab === 'analysis' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-chart-bar-square class="w-4 h-4" />
                Analisis Jadwal
            </button>

            <button @click="tab = 'history'" :class="tab === 'history' ? 'vp-tab is-active' : 'vp-tab'">
                <x-heroicon-o-clock class="w-4 h-4" />
                Riwayat Jadwal
            </button>

        </div>

        {{-- ──────────────────────────────────────────────────────────────────
             TAB 1 — Jadwal (Form + RelationManager)
             Menggunakan x-show agar Livewire tetap di-mount
        ─────────────────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'schedule'">

            @capture($form)
                {{-- Simpan/Batal butuh parent visual yang jelas — bukan
                     mengambang di whitespace kosong (form record-level ini
                     tidak punya field terlihat, hanya Simpan/Batal). --}}
                <div class="vp-form-actions-card">
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

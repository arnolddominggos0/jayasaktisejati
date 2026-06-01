<x-filament-panels::page>
    {{-- Branch/Depot Context Header --}}
    <div class="fi-page-header-context mb-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20">
                        <x-heroicon-m-building-office-2 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lingkup Operasional</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $this->getBranchName() }}
                            </h2>
                            @if ($this->hasDepotContext())
                                <span class="text-gray-400 dark:text-gray-500">→</span>
                                <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                                    {{ $this->getDepotName() }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Operational Readiness (dominant) --}}
                    @php
                        $opBadge = $this->getOperationalReadinessBadge();
                    @endphp
                    <x-filament::badge :color="$opBadge['color']" size="lg" :icon="$opBadge['icon']">
                        {{ $opBadge['label'] }}
                    </x-filament::badge>

                    {{-- Shipment Urgency --}}
                    @php
                        $urgencyCount = $this->getUrgencyCount();
                    @endphp
                    @if ($urgencyCount > 0)
                        <x-filament::badge color="danger" size="sm" icon="heroicon-m-exclamation-triangle">
                            {{ $urgencyCount }} pengiriman butuh perhatian
                        </x-filament::badge>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 1: Operational Readiness --}}
    <div class="mb-6">
        <x-filament-widgets::widgets
            :widgets="[\App\Filament\FC\Widgets\FcOperationalReadiness::class]"
            :columns="1"
        />
    </div>

    {{-- SECTION 2: MP Roster --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-user-group class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span
                class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Manpower</span>
        </div>
        <x-filament-widgets::widgets
            :widgets="[\App\Filament\FC\Widgets\FcTodayManpower::class]"
            :columns="1"
        />
    </div>

    {{-- SECTION 3: Shipment Monitoring --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-truck class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span
                class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Pengiriman</span>
        </div>
        <div class="space-y-4">
            <x-filament-widgets::widgets
                :widgets="[
                    \App\Filament\FC\Widgets\FcKpiStats::class,
                    \App\Filament\FC\Widgets\FcAttentionList::class,
                ]"
                :columns="1"
            />
        </div>
    </div>

    {{-- SECTION 4: Activity & Trends --}}
    <div class="space-y-4">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-chart-bar class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span
                class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Aktivitas</span>
        </div>
        <x-filament-widgets::widgets
            :widgets="[
                \App\Filament\FC\Widgets\FcStatusChart::class,
                \App\Filament\FC\Widgets\FcRecentActivities::class,
            ]"
            :columns="1"
        />
    </div>
</x-filament-panels::page>

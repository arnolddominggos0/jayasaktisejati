<x-filament-panels::page>
    {{-- Branch/Depot Context Header --}}
    <div class="fi-page-header-context mb-6">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20">
                        <x-heroicon-m-building-office-2 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lingkup Operasional</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $this->getBranchName() }}
                            </h2>
                            @if($this->hasDepotContext())
                                <span class="text-gray-400 dark:text-gray-500">→</span>
                                <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                                    {{ $this->getDepotName() }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="info" size="sm" icon="heroicon-m-shield-check">
                        Koordinator Lapangan
                    </x-filament::badge>
                    <x-filament::badge color="success" size="sm" icon="heroicon-m-wifi">
                        Mode: Laut
                    </x-filament::badge>
                </div>
            </div>
        </div>
    </div>

    <x-filament-widgets::widgets :columns="3" :widgets="[
        \App\Filament\FC\Widgets\FcKpiStats::class,
        \App\Filament\FC\Widgets\FcAttentionList::class,
        \App\Filament\FC\Widgets\FcStatusChart::class,
        \App\Filament\FC\Widgets\FcRecentActivities::class,
    ]" />
</x-filament-panels::page>

<x-filament-panels::page>

    {{-- ── KPI Header (YardInventoryService snapshot, 30s polling) ──────────── --}}
    @livewire(\App\Filament\FC\Widgets\YardInventoryWidget::class)

    {{-- ── Tab Navigation ─────────────────────────────────────────────────────── --}}
    <div class="mt-6">
        <div class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700">
            @foreach ($this->getTabs() as $tabKey => $tabLabel)
                @php
                    $isActive = $activeTab === $tabKey;
                    $icons = [
                        'ready_loading'      => 'heroicon-m-check-circle',
                        'bermasalah'         => 'heroicon-m-exclamation-triangle',
                        'waiting_inspection' => 'heroicon-m-clock',
                        'aging_yard'         => 'heroicon-m-calendar-days',
                        'shipment_readiness' => 'heroicon-m-chart-bar',
                    ];
                    $activeColors = [
                        'ready_loading'      => 'border-success-500 text-success-600 dark:text-success-400',
                        'bermasalah'         => 'border-danger-500 text-danger-600 dark:text-danger-400',
                        'waiting_inspection' => 'border-warning-500 text-warning-600 dark:text-warning-400',
                        'aging_yard'         => 'border-primary-500 text-primary-600 dark:text-primary-400',
                        'shipment_readiness' => 'border-info-500 text-info-600 dark:text-info-400',
                    ];
                @endphp
                <button
                    wire:click="setTab('{{ $tabKey }}')"
                    type="button"
                    @class([
                        'inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors duration-150 focus:outline-none',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' => ! $isActive,
                        $activeColors[$tabKey] => $isActive,
                    ])
                >
                    <x-dynamic-component :component="$icons[$tabKey]" class="w-4 h-4" />
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── Aging legend (Aging Yard tab only) ─────────────────────────────────── --}}
    @if($activeTab === 'aging_yard')
    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="font-medium">Legenda aging:</span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> 0–1 hr
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span> 2–3 hr
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span> 4–7 hr
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> &gt;7 hr
        </span>
    </div>
    @endif

    {{-- ── Active Table ──────────────────────────────────────────────────────── --}}
    <div class="mt-3">
        {{ $this->table }}
    </div>

</x-filament-panels::page>

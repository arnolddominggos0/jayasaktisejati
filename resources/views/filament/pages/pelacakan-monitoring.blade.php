<x-filament-panels::page>

    @php
    $summary = $workspaceSummary;
    $band = $exceptionBand;
    $pollInterval = $pollInterval ?? 60;
    $pageSize = $pageSize ?? 50;
    $exceptionFilter = $exceptionFilter ?? null;
    $groupMode = $groupMode ?? 'flat';
    @endphp

    {{-- Polling: refresh exception band + summary only --}}
    <div wire:poll.{{ $pollInterval }}s="pollRefresh"></div>

    <div class="jss-monitoring">

        {{-- ══════════════════════════════════════════════════════════════════
             HEADER SECTION
             WorkspaceSummaryData + breadcrumb + last updated
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-header mb-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-1">
                    <h1 class="text-3xl font-extrabold text-gray-900">Pelacakan & Monitoring</h1>
                    <p class="text-lg text-gray-500">Operational Control Tower</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        <span class="size-2 rounded-full bg-blue-500"></span>
                        {{ $summary->activeUnits }} unit aktif
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        {{ $summary->finishedUnits }} unit selesai
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        Route: {{ $summary->route }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        Cabang: {{ $summary->branch }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                        Filter: {{ $summary->filteredUnits }} unit
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-400">
                        Update: {{ $summary->lastRefresh->format('d M H:i') }}
                    </span>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             EXCEPTION BAND SECTION
             6 exception chips with counts
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-exception-band mb-4">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @php
                $chips = [
                    ['key' => 'delay', 'label' => 'Delay', 'count' => $band->delay_count, 'color' => 'red', 'icon' => 'heroicon-o-clock'],
                    ['key' => 'ng', 'label' => 'NG', 'count' => $band->ng_count, 'color' => 'red', 'icon' => 'heroicon-o-x-circle'],
                    ['key' => 'hold', 'label' => 'Hold', 'count' => $band->hold_count, 'color' => 'red', 'icon' => 'heroicon-o-pause-circle'],
                    ['key' => 'demurrage', 'label' => 'Demurrage', 'count' => $band->demurrage_count, 'color' => 'amber', 'icon' => 'heroicon-o-exclamation-triangle'],
                    ['key' => 'missing_voyage', 'label' => 'Missing Voyage', 'count' => $band->missing_voyage_count, 'color' => 'amber', 'icon' => 'heroicon-o-map'],
                    ['key' => 'pdi_pending', 'label' => 'PDI Pending', 'count' => $band->pdi_pending_count, 'color' => 'amber', 'icon' => 'heroicon-o-document-text'],
                ];
                @endphp

                @foreach ($chips as $chip)
                    <button
                        type="button"
                        wire:click="updateFilter('exception_filter', '{{ $chip['key'] }}')"
                        class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-gray-300 hover:shadow-sm {{ $exceptionFilter === $chip['key'] ? 'ring-2 ring-blue-500' : '' }}"
                    >
                        <span class="flex size-10 items-center justify-center rounded-lg bg-{{ $chip['color'] }}-50 text-{{ $chip['color'] }}-600">
                            <x-heroicon-o-exclamation-triangle class="size-5" />
                        </span>
                        <span class="flex flex-col">
                            <span class="text-2xl font-bold text-gray-900">{{ $chip['count'] }}</span>
                            <span class="text-xs font-medium text-gray-500">{{ $chip['label'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             TOOLBAR SECTION
             Filament Form (filter, search, group, refresh)
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-toolbar mb-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    {{ $this->form }}
                    <button
                        type="button"
                        wire:click="refresh"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                    >
                        <x-heroicon-o-arrow-path class="size-4" />
                        Refresh
                    </button>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             TABLE SECTION
             Custom Livewire component: monitoring-table
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-table">
            <livewire:monitoring.monitoring-table :rows="$rows" :group-mode="$groupMode" />
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             DETAIL SLIDE-OVER
             Custom Livewire component: monitoring-detail-slide
        ══════════════════════════════════════════════════════════════════ --}}
        <livewire:monitoring.monitoring-detail-slide />

        {{-- ══════════════════════════════════════════════════════════════════
             FOOTER SECTION
             Metadata block
        ══════════════════════════════════════════════════════════════════ --}}
        <footer class="jss-mon-footer mt-6 border-t border-gray-200 pt-4">
            <div class="flex flex-col gap-2 text-xs text-gray-400 sm:flex-row sm:items-center sm:gap-4">
                <span>Pelacakan & Monitoring v1.0</span>
                <span class="hidden sm:inline">·</span>
                <span>Read-only workspace (D1)</span>
                <span class="hidden sm:inline">·</span>
                <span>Page size: {{ $pageSize }}</span>
                <span class="hidden sm:inline">·</span>
                <span>Poll: {{ $pollInterval }}s</span>
                @if ($rows && $rows->hasPages())
                    <span class="hidden sm:inline">·</span>
                    <span>Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }} ({{ $rows->total() }} total)</span>
                @endif
            </div>
        </footer>

    </div>

</x-filament-panels::page>
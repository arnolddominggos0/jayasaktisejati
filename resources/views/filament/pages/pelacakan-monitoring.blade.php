<x-filament-panels::page>

    @php
        $summary         = $workspaceSummary;
        $band            = $exceptionBand;
        $pollInterval    = $pollInterval    ?? 60;
        $pageSize        = $pageSize        ?? 50;
        $exceptionFilter = $exceptionFilter ?? null;
        $groupMode       = $groupMode       ?? 'flat';
        $activeFilters   = $activeFilterCount ?? 0;

        $hasExceptions = ($band->delay_count + $band->ng_count + $band->hold_count
                        + $band->demurrage_count + $band->missing_voyage_count
                        + $band->pdi_pending_count) > 0;
    @endphp

    {{-- Poll: refresh exception band + summary only (not the full table) --}}
    <div wire:poll.{{ $pollInterval }}s="pollRefresh" class="hidden" aria-hidden="true"></div>

    <div class="jss-monitoring space-y-3">

        {{-- ══════════════════════════════════════════════════════════════
             1. WORKSPACE HEADER — compact bar, no card
             Identity + locked scope pills + compact status metrics.
             Scope (TAM · Laut) is workspace metadata, NOT a filter.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-header">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                {{-- Left: Identity + scope context --}}
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold leading-tight text-gray-900">
                            Pelacakan &amp; Monitoring
                        </h1>
                        {{-- Locked scope pills — workspace context, not interactive --}}
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            TAM
                        </span>
                        <span class="rounded bg-blue-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-blue-600">
                            Laut
                        </span>
                        @if ($activeFilters > 0)
                            <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[11px] font-bold text-white">
                                {{ $activeFilters }} filter aktif
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">Operational Control Tower</p>
                </div>

                {{-- Right: compact status metrics --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:shrink-0">
                    {{-- Active units: most prominent --}}
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block size-1.5 rounded-full bg-blue-500"></span>
                        <span class="text-sm font-bold text-gray-800">{{ $summary->activeUnits }}</span>
                        <span class="text-xs text-gray-400">aktif</span>
                    </div>

                    <div class="h-3.5 w-px bg-gray-200"></div>

                    {{-- Finished --}}
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-medium text-gray-500">{{ $summary->finishedUnits }}</span>
                        <span class="text-xs text-gray-400">selesai</span>
                    </div>

                    @if ($activeFilters > 0)
                        <div class="h-3.5 w-px bg-gray-200"></div>
                        <div class="flex items-center gap-1 text-xs text-amber-600">
                            <x-heroicon-o-funnel class="size-3" />
                            {{ $summary->filteredUnits }} hasil
                        </div>
                    @endif

                    <div class="h-3.5 w-px bg-gray-200"></div>

                    {{-- Branch --}}
                    <div class="flex items-center gap-1 text-xs text-gray-400">
                        <x-heroicon-o-building-office class="size-3" />
                        {{ $summary->branch }}
                    </div>

                    <div class="h-3.5 w-px bg-gray-200"></div>

                    {{-- Last refresh --}}
                    <div class="flex items-center gap-1 tabular-nums text-xs text-gray-400">
                        <x-heroicon-o-clock class="size-3" />
                        {{ $summary->lastRefresh->format('H:i:s') }}
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             2. EXCEPTION BAND
             Shows clickable alert chips when exceptions exist.
             Passive single-line indicator when all clear.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-exception-band">
        @if ($hasExceptions)
            @php
            $chips = [
                ['key' => 'delay',         'label' => 'Delay',         'count' => $band->delay_count,         'icon' => 'heroicon-o-clock',                   'color' => 'red'],
                ['key' => 'ng',            'label' => 'NG',            'count' => $band->ng_count,            'icon' => 'heroicon-o-x-circle',                'color' => 'red'],
                ['key' => 'hold',          'label' => 'Hold',          'count' => $band->hold_count,          'icon' => 'heroicon-o-pause-circle',             'color' => 'red'],
                ['key' => 'demurrage',     'label' => 'Demurrage',     'count' => $band->demurrage_count,     'icon' => 'heroicon-o-exclamation-triangle',    'color' => 'amber'],
                ['key' => 'missing_voyage','label' => 'Missing Voyage','count' => $band->missing_voyage_count,'icon' => 'heroicon-o-paper-airplane',           'color' => 'amber'],
                ['key' => 'pdi_pending',   'label' => 'PDI Pending',   'count' => $band->pdi_pending_count,  'icon' => 'heroicon-o-clipboard-document-check', 'color' => 'amber'],
            ];
            @endphp

            <div class="flex flex-wrap gap-2">
                @foreach ($chips as $chip)
                    @if ($chip['count'] > 0)
                    <button
                        type="button"
                        wire:click="updateFilter('exception_filter', '{{ $chip['key'] }}')"
                        title="{{ $chip['label'] }}"
                        class="group flex items-center gap-2.5 rounded-xl border px-3.5 py-2.5 text-left transition-all
                               {{ $exceptionFilter === $chip['key']
                                    ? 'border-blue-400 bg-blue-50 ring-2 ring-blue-500 ring-offset-1'
                                    : ($chip['color'] === 'red'
                                        ? 'border-red-200 bg-red-50 hover:border-red-300 hover:shadow-sm'
                                        : 'border-amber-200 bg-amber-50 hover:border-amber-300 hover:shadow-sm') }}"
                    >
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg
                                     {{ $chip['color'] === 'red' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                            <x-dynamic-component :component="$chip['icon']" class="size-4" />
                        </span>
                        <span class="flex flex-col leading-none">
                            <span class="text-xl font-bold {{ $chip['color'] === 'red' ? 'text-red-700' : 'text-amber-700' }}">{{ $chip['count'] }}</span>
                            <span class="mt-0.5 text-xs font-medium {{ $chip['color'] === 'red' ? 'text-red-500' : 'text-amber-500' }}">{{ $chip['label'] }}</span>
                        </span>
                    </button>
                    @endif
                @endforeach

                @if ($exceptionFilter)
                <button
                    type="button"
                    wire:click="updateFilter('exception_filter', null)"
                    class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-medium text-gray-500 transition hover:border-gray-300 hover:bg-gray-50"
                    title="Hapus filter exception"
                >
                    <x-heroicon-o-x-mark class="size-3.5" />
                    Reset
                </button>
                @endif
            </div>

        @else

            <div class="flex items-center gap-1.5 py-0.5 text-xs text-gray-400">
                <x-heroicon-o-check-circle class="size-3.5 text-emerald-400" />
                Tidak ada exception aktif
            </div>

        @endif
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             3+4. WORKSPACE CARD — Toolbar bar + Monitoring Table unified.
             Single card eliminates the visual gap between filter controls
             and data rows. The operator reads top-to-bottom without
             crossing two card boundaries.
             Toolbar: bg-gray-50/50 bar with border-b — feels like a
             console toolbar rail, not a standalone form card.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-workspace">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">

                {{-- ── Toolbar rail ── --}}
                <div class="border-b border-gray-100 bg-gray-50/50 px-4 py-3">
                    <div class="flex items-end gap-3">

                        {{-- Form: Search (primary) · Exception · Tampilan · Selesai --}}
                        <div class="min-w-0 flex-1">
                            {{ $this->form }}
                        </div>

                        {{-- Refresh: compact, aligned to input baseline --}}
                        <div class="flex shrink-0 self-end">
                            <button
                                type="button"
                                wire:click="refresh"
                                wire:loading.attr="disabled"
                                title="Refresh data"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 disabled:opacity-50"
                            >
                                <x-heroicon-o-arrow-path class="size-4 text-gray-400" wire:loading.class="animate-spin" wire:target="refresh" />
                                <span class="hidden sm:inline" wire:loading.remove wire:target="refresh">Refresh</span>
                                <span class="hidden sm:inline" wire:loading wire:target="refresh">Memuat…</span>
                            </button>
                        </div>

                    </div>
                </div>

                {{-- ── Monitoring Table (no inner card — inherits workspace card) ── --}}
                <livewire:monitoring.monitoring-table
                    :total-rows="$rows?->total() ?? 0"
                    :per-page="$rows?->perPage() ?? 50"
                    :current-page="$rows?->currentPage() ?? 1"
                    :last-page="$rows?->lastPage() ?? 1"
                    :group-mode="$groupMode" />

            </div>
        </section>

        {{-- Detail slide-over (event-driven, always rendered, visually hidden) --}}
        <livewire:monitoring.monitoring-detail-slide />

        {{-- ══════════════════════════════════════════════════════════════
             5. FOOTER — metadata strip
        ══════════════════════════════════════════════════════════════ --}}
        <footer class="jss-mon-footer border-t border-gray-100 pt-2">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                <span class="font-medium text-gray-500">Pelacakan &amp; Monitoring</span>
                <span class="hidden sm:inline">·</span>
                <span>v1.0 · Read-only</span>
                <span class="hidden sm:inline">·</span>
                <span>Page size: {{ $pageSize }}</span>
                <span class="hidden sm:inline">·</span>
                <span>Poll: {{ $pollInterval }}s</span>
                @if ($summary->filteredUnits > 0)
                    <span class="hidden sm:inline">·</span>
                    <span>{{ $summary->filteredUnits }} unit terfilter</span>
                @endif
                @if ($rows && $rows->hasPages())
                    <span class="hidden sm:inline">·</span>
                    <span>Hal {{ $rows->currentPage() }}/{{ $rows->lastPage() }} ({{ $rows->total() }} total)</span>
                @endif
                <span class="hidden sm:inline">·</span>
                <span class="tabular-nums">Diperbarui {{ $summary->lastRefresh->format('d M Y H:i:s') }}</span>
            </div>
        </footer>

    </div>

</x-filament-panels::page>

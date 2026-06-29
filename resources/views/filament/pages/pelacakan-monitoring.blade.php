<x-filament-panels::page>

    @php
        $summary = $workspaceSummary;
        $band = $exceptionBand;
        $pollInterval = $pollInterval ?? 60;
        $pageSize = $pageSize ?? 50;
        $exceptionFilter = $exceptionFilter ?? null;
        $groupMode = $groupMode ?? 'flat';
        $activeFilters = $activeFilterCount ?? 0;

        $hasExceptions =
            $band->delay_count +
                $band->ng_count +
                $band->hold_count +
                $band->demurrage_count +
                $band->missing_voyage_count +
                $band->pdi_pending_count >
            0;

        $lastRefreshFormatted = $summary->lastRefresh->format('H:i:s');
    @endphp

    {{-- Poll: refresh exception band + summary only (not the full table) --}}
    <div wire:poll.{{ $pollInterval }}s="pollRefresh" class="hidden" aria-hidden="true"></div>

    <script>
        if (typeof window.monWorkspace !== 'function') {
            window.monWorkspace = function(pollInterval) {
                return {
                    focusedIndex: -1,
                    selectedRowIndex: -1,
                    slideOpen: false,
                    pollCountdown: pollInterval - 1,
                    refreshFlash: false,
                    pollInterval: pollInterval,
                    _pollTimer: null,
                    _listeners: [],

                    init() {
                        const self = this;
                        this._pollTimer = setInterval(function() {
                            self.pollCountdown--;
                            if (self.pollCountdown <= 0) self.pollCountdown = self.pollInterval - 1;
                        }, 1000);

                        Livewire.on('open-unit-detail', function(data) {
                            self.slideOpen = true;
                            if (data && data.unitId) {
                                var row = document.querySelector('[data-unit-id="' + data.unitId + '"]');
                                if (row) {
                                    self.selectedRowIndex = parseInt(row.dataset.rowIndex || '-1');
                                    self.focusedIndex = self.selectedRowIndex;
                                }
                            }
                        });

                        Livewire.on('detail-closed', function() {
                            self.slideOpen = false;
                            self.$nextTick(function() {
                                var row = document.querySelector('[data-row-index="' + self
                                    .focusedIndex + '"]');
                                if (row) row.focus();
                            });
                        });

                        Livewire.on('refresh-complete', function() {
                            self.refreshFlash = true;
                            setTimeout(function() {
                                self.refreshFlash = false;
                            }, 1500);
                        });

                        Livewire.on('poll-complete', function() {
                            self.pollCountdown = self.pollInterval - 1;
                        });
                    },

                    handleKey(e) {
                        var tag = e.target.tagName;
                        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                            if (e.key === 'Escape') e.target.blur();
                            return;
                        }

                        if (e.key === 'ArrowDown' || e.key === 'j') {
                            e.preventDefault();
                            this.moveFocus(1);
                        } else if (e.key === 'ArrowUp' || e.key === 'k') {
                            e.preventDefault();
                            this.moveFocus(-1);
                        } else if (e.key === 'Escape') {
                            this.handleEscape();
                        } else if (e.key === '/') {
                            e.preventDefault();
                            this.focusSearch();
                        }

                        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                            e.preventDefault();
                            this.$wire.call('refresh');
                        }
                    },

                    moveFocus(dir) {
                        var wrap = document.querySelector('.jss-mon-table-wrap');
                        var total = wrap ? parseInt(wrap.dataset.totalRows || '0') : 0;
                        if (total === 0) return;

                        var currentRow = document.querySelector('[data-row-index="' + this.focusedIndex + '"]');
                        if (!currentRow && this.focusedIndex !== -1) {
                            this.focusedIndex = -1;
                        }

                        if (this.focusedIndex === -1) {
                            this.focusedIndex = dir > 0 ? 0 : total - 1;
                        } else {
                            this.focusedIndex = Math.max(0, Math.min(total - 1, this.focusedIndex + dir));
                        }

                        this.$nextTick(function() {
                            var row = document.querySelector('[data-row-index="' + this.focusedIndex + '"]');
                            if (row) {
                                row.focus();
                                row.scrollIntoView({
                                    block: 'nearest'
                                });
                            }
                        }.bind(this));
                    },

                    handleEscape() {
                        if (this.slideOpen) {
                            this.$wire.call('closeDetail');
                        } else {
                            this.focusedIndex = -1;
                            if (document.activeElement && document.activeElement.blur) {
                                document.activeElement.blur();
                            }
                        }
                    },

                    focusSearch() {
                        var input = document.querySelector('.jss-monitoring input[name="data[search]"]');
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    },

                    clearSearch() {
                        this.$wire.call('updateFilter', 'search', '');
                    }
                };
            };
        }
    </script>

    <div class="jss-monitoring" x-data="monWorkspace({{ $pollInterval }})" @keydown.window="handleKey($event)" role="application"
        aria-label="Pelacakan dan Monitoring workspace">

        {{-- ══════════════════════════════════════════════════════════════
             1. WORKSPACE HEADER — the visual anchor (Task 3)
             Compact bar, no card. Identity + locked scope pills +
             compact live metrics. Scope (TAM · Laut) is workspace metadata,
             not an interactive filter.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-header">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">

                {{-- Left: Identity + scope context --}}
                <div class="flex flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="mon-h1">Pelacakan &amp; Monitoring</h1>
                        <span class="mon-scope mon-scope-tam" title="Mode workspace">TAM</span>
                        <span class="mon-scope mon-scope-laut" title="Mode workspace">Laut</span>
                        @if ($activeFilters > 0)
                            <span class="mon-scope mon-scope-active" title="Filter aktif">{{ $activeFilters }} filter
                                aktif</span>
                        @endif
                    </div>
                    <p class="mon-subtitle">Operational Control Tower — pemantauan unit realtime lintas cabang</p>
                </div>

                {{-- Right: compact live status metrics (hierarchy via weight, not card) --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 lg:shrink-0">
                    {{-- Active units: most prominent (anchor metric) --}}
                    <span class="mon-metric">
                        <span class="size-2 rounded-full bg-blue-500"></span>
                        <span class="mon-metric-num">{{ $summary->activeUnits }}</span>
                        <span class="mon-metric-label">aktif</span>
                    </span>

                    <span class="mon-vrule"></span>

                    {{-- Finished --}}
                    <span class="mon-metric">
                        <span class="mon-metric-sub">{{ $summary->finishedUnits }}</span>
                        <span class="mon-metric-label">selesai</span>
                    </span>

                    @if ($activeFilters > 0)
                        <span class="mon-vrule"></span>
                        <span class="mon-meta text-amber-600">
                            <x-heroicon-o-funnel class="mon-ic-3" />
                            {{ $summary->filteredUnits }} hasil
                        </span>
                    @endif

                    <span class="mon-vrule"></span>

                    {{-- Branch --}}
                    <span class="mon-meta">
                        <x-heroicon-o-building-office class="mon-ic-3" />
                        {{ $summary->branch }}
                    </span>

                    <span class="mon-vrule"></span>

                    {{-- Last refresh + polling countdown --}}
                    <span class="mon-meta mon-refresh-meta">
                        <x-heroicon-o-arrow-path class="mon-ic-3" ::class="{ 'text-emerald-500 animate-spin': refreshFlash }" wire:loading.class="animate-spin"
                            wire:target="refresh" />
                        <span x-show="!refreshFlash">{{ $lastRefreshFormatted }}</span>
                        <span class="text-emerald-600" x-show="refreshFlash" x-cloak
                            x-transition.opacity.duration.200ms>Diperbarui</span>
                        <span class="mon-poll-countdown" x-show="!refreshFlash" x-cloak
                            x-text="'\u21F5 ' + pollCountdown + 's'" title="Polling berikutnya"></span>
                    </span>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             2. EXCEPTION BAND (Task 4 / Task 8)
             Clickable alert tiles when exceptions exist; passive single-line
             "all clear" indicator otherwise. Color carries operational meaning.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-exception-band" aria-label="Exception summary">
            @if ($hasExceptions)
                @php
                    $chips = [
                        [
                            'key' => 'delay',
                            'label' => 'Delay',
                            'count' => $band->delay_count,
                            'icon' => 'heroicon-o-clock',
                            'tier' => 'danger',
                        ],
                        [
                            'key' => 'ng',
                            'label' => 'NG',
                            'count' => $band->ng_count,
                            'icon' => 'heroicon-o-x-circle',
                            'tier' => 'danger',
                        ],
                        [
                            'key' => 'hold',
                            'label' => 'Hold',
                            'count' => $band->hold_count,
                            'icon' => 'heroicon-o-pause-circle',
                            'tier' => 'danger',
                        ],
                        [
                            'key' => 'demurrage',
                            'label' => 'Demurrage',
                            'count' => $band->demurrage_count,
                            'icon' => 'heroicon-o-exclamation-triangle',
                            'tier' => 'warning',
                        ],
                        [
                            'key' => 'missing_voyage',
                            'label' => 'Missing Voyage',
                            'count' => $band->missing_voyage_count,
                            'icon' => 'heroicon-o-arrow-uturn-right',
                            'tier' => 'warning',
                        ],
                        [
                            'key' => 'pdi_pending',
                            'label' => 'PDI Pending',
                            'count' => $band->pdi_pending_count,
                            'icon' => 'heroicon-o-clipboard-document-check',
                            'tier' => 'warning',
                        ],
                    ];
                @endphp

                <div class="flex flex-wrap gap-2.5" role="group" aria-label="Filter berdasarkan exception">
                    @foreach ($chips as $chip)
                        @if ($chip['count'] > 0)
                            <button type="button" wire:click="updateFilter('exception_filter', '{{ $chip['key'] }}')"
                                title="{{ $chip['label'] }}"
                                class="mon-exception-chip {{ $exceptionFilter === $chip['key'] ? 'is-active' : 'is-' . $chip['tier'] }}"
                                aria-pressed="{{ $exceptionFilter === $chip['key'] ? 'true' : 'false' }}">
                                <span class="mon-chip-ic is-{{ $chip['tier'] }}">
                                    <x-dynamic-component :component="$chip['icon']" class="mon-ic-4" />
                                </span>
                                <span class="flex flex-col leading-none">
                                    <span class="mon-chip-count is-{{ $chip['tier'] }}">{{ $chip['count'] }}</span>
                                    <span class="mon-chip-label is-{{ $chip['tier'] }}">{{ $chip['label'] }}</span>
                                </span>
                            </button>
                        @endif
                    @endforeach

                    @if ($exceptionFilter)
                        <button type="button" wire:click="updateFilter('exception_filter', null)" class="mon-reset"
                            title="Hapus filter exception" aria-label="Hapus filter exception">
                            <x-heroicon-o-x-mark class="mon-ic-3" />
                            Reset
                        </button>
                    @endif
                </div>
            @else
                <span class="mon-allclear" role="status" aria-label="Tidak ada exception aktif">
                    <x-heroicon-o-check-circle class="mon-ic-4 text-emerald-500" />
                    Tidak ada exception aktif — semua unit dalam status normal
                </span>

            @endif
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             3+4. WORKSPACE SURFACE (Task 6) — single operational plane.
             One surface unifies toolbar + table; the operator reads
             top-to-bottom without crossing two card boundaries.
             Toolbar (Task 4): Search (primary) → Exception → View →
             Show Finished → Clear → Refresh — visual weight descends left-to-right.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-mon-workspace">
            <div class="mon-surface">

                {{-- ── Toolbar rail ── --}}
                <div class="mon-toolbar">
                    <div class="flex items-end gap-3">

                        {{-- Form: Search (primary) · Exception · Tampilan · Selesai --}}
                        <div class="min-w-0 flex-1 mon-search-wrap">
                            {{ $this->form }}
                            {{-- Search clear button (Alpine) --}}
                            <button type="button" x-show="$wire.search && $wire.search.length > 0" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95" wire:click="updateFilter('search', '')"
                                class="mon-clear-search" aria-label="Hapus pencarian" title="Hapus pencarian">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Refresh: framed, aligned to input baseline --}}
                        <div class="flex shrink-0 self-end">
                            <button type="button" wire:click="refresh" wire:loading.attr="disabled"
                                title="Refresh data (Ctrl+R)" class="mon-btn"
                                :class="{ 'mon-btn-success': refreshFlash }" aria-label="Refresh data workspace">
                                <x-heroicon-o-arrow-path class="mon-ic-r" wire:loading.class="animate-spin"
                                    wire:target="refresh" />
                                <span class="hidden sm:inline" wire:loading.remove.delay wire:target="refresh"
                                    x-show="!refreshFlash">Refresh</span>
                                <span class="hidden sm:inline" wire:loading.delay wire:target="refresh">Memuat…</span>
                                <span class="hidden sm:inline text-emerald-600" x-show="refreshFlash" x-cloak
                                    x-transition.opacity.duration.200ms>Diperbarui</span>
                            </button>
                        </div>

                    </div>
                </div>

                {{-- ── Monitoring Table (inherits workspace surface) ──
                     Pure Blade partial bound to MonitoringRowData objects from
                     the paginator. Lives in the parent Livewire component's
                     template, so wire:* directives here dispatch on WorkspaceShell. --}}
                @include('livewire.monitoring.monitoring-table', [
                    'rows' => $rows?->items() ?? [],
                    'paginator' => $rows,
                    'search' => $this->search ?? '',
                    'pageSize' => $pageSize ?? 50,
                    'groupMode' => $groupMode ?? 'flat',
                ])

            </div>
        </section>

        {{-- Detail slide-over (event-driven, always rendered, visually hidden) --}}
        <livewire:monitoring.monitoring-detail-slide />

        {{-- ══════════════════════════════════════════════════════════════
             5. FOOTER (Task 1 / Task 5) — metadata strip
        ══════════════════════════════════════════════════════════════ --}}
        <footer class="jss-mon-footer" aria-label="Workspace metadata">
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 mon-foot">
                <span class="foot-label">Pelacakan &amp; Monitoring</span>
                <span class="foot-dot">·</span>
                <span>v1.0 · Read-only</span>
                <span class="foot-dot">·</span>
                <span>Page size {{ $pageSize }}</span>
                <span class="foot-dot">·</span>
                <span>Poll {{ $pollInterval }}s</span>
                @if ($summary->filteredUnits > 0)
                    <span class="foot-dot">·</span>
                    <span>{{ $summary->filteredUnits }} unit terfilter</span>
                @endif
                @if ($rows && $rows->hasPages())
                    <span class="foot-dot">·</span>
                    <span>Hal {{ $rows->currentPage() }}/{{ $rows->lastPage() }} ({{ $rows->total() }} total)</span>
                @endif
                <span class="foot-dot">·</span>
                <span class="tabular-nums">Diperbarui {{ $lastRefreshFormatted }}</span>
                <span class="foot-dot">·</span>
                <kbd class="mon-kbd">&#8593;&#8595;</kbd> <span>nav</span>
                <span class="foot-dot">·</span>
                <kbd class="mon-kbd">Enter</kbd> <span>detail</span>
                <span class="foot-dot">·</span>
                <kbd class="mon-kbd">/</kbd> <span>cari</span>
            </div>
        </footer>

    </div>

</x-filament-panels::page>
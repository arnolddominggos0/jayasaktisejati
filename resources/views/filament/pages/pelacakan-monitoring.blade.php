<x-filament-panels::page>

    @php
        $summary = $workspaceSummary;
        $band = $exceptionBand;
        $pollInterval = $pollInterval ?? 60;
        $pageSize = $pageSize ?? 50;
        $summary = $workspaceSummary;
        $band = $exceptionBand;
        $pollInterval = $pollInterval ?? 60;
        $pageSize = $pageSize ?? 50;
        $exceptionFilter = $exceptionFilter ?? null;
        $groupMode = $groupMode ?? 'flat';
        $activeFilters = $activeFilterCount ?? 0;
        $hasActiveFilters = $hasActiveFilters ?? ($activeFilters > 0);
        $filterChips = $activeFilterChips ?? [];

        $hasExceptions =
            $band->delay_count +
                $band->ng_count +
                $band->hold_count +
                $band->demurrage_count +
                $band->missing_voyage_count +
                $band->stuck_count >
            0;

        $lastRefreshFormatted = $summary->lastRefresh->format('H:i:s');

        // KPI (workspace summary) deliberately ignores search/exception; these
        // two booleans drive a small explanatory context bar + empty-state copy
        // so that's visible to the operator instead of looking like a bug. Both
        // values already exist on the page (search, exception_filter).
        $exceptionLabels = [
            'hold'           => 'Hold',
            'ng'             => 'NG',
            'demurrage'      => 'Demurrage',
            'delay'          => 'Delay',
            'stuck'          => 'Stuck',
            'missing_voyage' => 'Missing Voyage',
        ];
        $searchActive = strlen(trim($this->search ?? '')) > 0;
        $exceptionActive = filled($exceptionFilter);
        $exceptionLabel = $exceptionActive
            ? ($exceptionLabels[$exceptionFilter] ?? ucfirst(str_replace('_', ' ', $exceptionFilter)))
            : null;
        $drilldownActive = $searchActive || $exceptionActive;
        $resultCount = $rows?->total() ?? 0;
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
                            self.selectedRowIndex = -1;
                            self.$nextTick(function() {
                                var row = document.querySelector('[data-row-index="' + self
                                    .focusedIndex + '"]');
                                if (row) row.focus();
                            });
                        });

                        window.addEventListener('panel-close-requested', function() {
                            self.$wire.call('closeDetail');
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
                            if (e.key === 'Escape') {
                                if (tag === 'INPUT' && e.target.name === 'data[search]') {
                                    e.preventDefault();
                                    this.clearSearch();
                                } else {
                                    e.target.blur();
                                }
                            }
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

        {{-- ==============================================================
             1. WORKSPACE HEADER - the visual anchor (Task 3)
             Compact bar, no card. Identity + locked scope pills +
             compact live metrics. Scope (TAM · Laut) is workspace metadata,
             not an interactive filter.
        ============================================================== --}}
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
                    <p class="mon-subtitle">Operational Control Tower - pemantauan unit realtime lintas cabang</p>
                </div>

                {{-- Right: compact live status metrics (hierarchy via weight, not card) --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 lg:shrink-0">
                    {{-- Explains the KPI is a workspace-wide summary
                         (period/branch/route/mode only), not affected by
                         search/exception - only shown while one of those
                         drill-down filters is active, so the default view is
                         untouched (Acceptance: "KPI tidak berubah saat search"). --}}
                    @if ($drilldownActive)
                        <span class="mon-kpi-helper" title="KPI menghitung seluruh periode terpilih, tidak dipengaruhi oleh pencarian atau filter exception">Ringkasan Workspace</span>
                        <span class="mon-vrule"></span>
                    @endif

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

        {{-- ==============================================================
             2. EXCEPTION BAND (Task 4 / Task 8)
             Clickable alert tiles when exceptions exist; passive single-line
             "all clear" indicator otherwise. Color carries operational meaning.
        ============================================================== --}}
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
                            'key' => 'stuck',
                            'label' => 'Stuck',
                            'count' => $band->stuck_count,
                            'icon' => 'heroicon-o-arrow-path',
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
                    Tidak ada exception aktif - semua unit dalam status normal
                </span>
                    @if ($exceptionFilter)
                        <button type="button" wire:click="updateFilter('exception_filter', null)" class="mon-reset"
                            title="Hapus filter exception" aria-label="Hapus filter exception">
                            <x-heroicon-o-x-mark class="mon-ic-3" />
                            Reset
                        </button>
                    @endif

            @endif
        </section>

        {{-- Active filter chips: removable chips for non-default filters
             (search/status/view/sort/route). Exception is intentionally
             excluded - it already has its own chip+reset in the exception band
             above. Only renders when a filter is actually active - the result
             count pill is contextual to "this is what your filters produced",
             not a permanent fixture (it would otherwise duplicate the header's
             aktif/selesai metrics on the default, unfiltered view). --}}
        @if (! empty($filterChips))
            <section class="jss-mon-active-filters" aria-label="Hasil dan filter aktif">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Result count - from paginator total, no extra query --}}
                    @if ($rows && $rows->total() > 0)
                        <span class="mon-result-count" aria-live="polite" role="status">
                            {{ $rows->total() }} hasil
                        </span>
                    @endif

                    <span class="mon-active-filters-label">Filter:</span>
                    @foreach ($filterChips as $chip)
                        <button type="button"
                            wire:click="updateFilter('{{ $chip['field'] }}', '{{ $chip['clear'] }}')"
                            class="mon-active-filter-chip"
                            title="Hapus filter {{ $chip['label'] }}"
                            aria-label="Hapus filter {{ $chip['label'] }}: {{ $chip['value'] }}">
                            <span class="mon-active-filter-label">{{ $chip['label'] }}:</span>
                            <span class="mon-active-filter-value">{{ $chip['value'] }}</span>
                            <x-heroicon-o-x-mark class="mon-ic-3" />
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ==============================================================
             3+4. WORKSPACE SURFACE (Task 6) - single operational plane.
             One surface unifies toolbar + table; the operator reads
             top-to-bottom without crossing two card boundaries.
             Toolbar (Task 4): Periode -> Cari (primary) -> Exception -> Tampilan -> Selesai -> Refresh - visual weight descends left-to-right.
        ============================================================== --}}
        <section class="jss-mon-workspace">
            <div class="mon-surface">

                {{-- -- Toolbar rail -- --}}
                <div class="mon-toolbar">
                    <div class="flex items-end gap-3">

                        {{-- Form: Periode · Cari (primary) · Exception · Tampilan · Selesai --}}
                        {{-- Search clear button + loading spinner are native Filament
                             affixes on the 'search' field itself (suffixAction +
                             wire:target loading indicator) - see getFormSchema()
                             in WorkspaceShell. No manual absolute-positioned
                             overlay needed here. --}}
                        <div class="min-w-0 flex-1">
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

                {{-- Drill-down context bar: tells the operator the table below is
                     a filtered view, distinct from the workspace-wide KPI above. --}}
                @if ($drilldownActive)
                    <div class="mon-context-bar" role="status" aria-live="polite">
                        @if ($searchActive && $exceptionActive)
                            <span class="mon-context-label">Filter aktif</span>
                            <span class="mon-context-value">
                                Cari: <strong>{{ $this->search }}</strong>
                                <span class="mon-context-sep">&middot;</span>
                                Exception: <strong>{{ $exceptionLabel }}</strong>
                            </span>
                        @elseif ($searchActive)
                            <span class="mon-context-label">Menampilkan hasil pencarian</span>
                            <span class="mon-context-value">Cari: <strong>{{ $this->search }}</strong></span>
                        @else
                            <span class="mon-context-label">Filter aktif</span>
                            <span class="mon-context-value">Exception: <strong>{{ $exceptionLabel }}</strong></span>
                        @endif
                        <span class="mon-context-count">
                            {{ $resultCount }} {{ $searchActive ? 'hasil ditemukan' : 'shipment' }}
                        </span>
                    </div>
                @endif

                {{-- -- Monitoring Table (inherits workspace surface) --
                     Pure Blade partial bound to MonitoringRowData objects from
                     the paginator. Lives in the parent Livewire component's
                     template, so wire:* directives here dispatch on WorkspaceShell. --}}
                @include('livewire.monitoring.monitoring-table', [
                    'rows' => $rows?->items() ?? [],
                    'paginator' => $rows,
                    'search' => $this->search ?? '',
                    'pageSize' => $pageSize ?? 50,
                    'groupMode' => $groupMode ?? 'flat',
                    'hasActiveFilters' => $hasActiveFilters,
                    'exceptionFilter' => $exceptionFilter,
                    'exceptionLabel' => $exceptionLabel,
                ])

            </div>
        </section>

        {{-- Detail slide-over (event-driven, always rendered, visually hidden) --}}
        <livewire:monitoring.monitoring-detail-slide />

        {{-- ==============================================================
             5. FOOTER (Task 1 / Task 5) - metadata strip
        ============================================================== --}}
        <footer class="jss-mon-footer" aria-label="Workspace metadata">
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 mon-foot">
                {{-- Identity --}}
                <span class="foot-group">
                    <span class="foot-label">Pelacakan &amp; Monitoring</span>
                    <span class="foot-dot">·</span>
                    <span>v1.0 · Read-only</span>
                </span>

                {{-- Operational config --}}
                <span class="foot-group">
                    <span>Page size {{ $pageSize }}</span>
                    <span class="foot-dot">·</span>
                    <span>Poll {{ $pollInterval }}s</span>
                    @if ($rows && $rows->hasPages())
                        <span class="foot-dot">·</span>
                        <span>Hal {{ $rows->currentPage() }}/{{ $rows->lastPage() }} ({{ $rows->total() }} total)</span>
                    @endif
                </span>

                {{-- Live status --}}
                <span class="foot-group">
                    <span class="tabular-nums">Diperbarui {{ $lastRefreshFormatted }}</span>
                </span>

                {{-- Keyboard hints --}}
                <span class="foot-group">
                    <kbd class="mon-kbd">&#8593;&#8595;</kbd> <span>nav</span>
                    <span class="foot-dot">·</span>
                    <kbd class="mon-kbd">Enter</kbd> <span>detail</span>
                    <span class="foot-dot">·</span>
                    <kbd class="mon-kbd">/</kbd> <span>cari</span>
                </span>
            </div>
        </footer>

    </div>

</x-filament-panels::page>
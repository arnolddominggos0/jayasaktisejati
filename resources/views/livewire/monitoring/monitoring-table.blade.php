@php
    /**
     * Operational Control Tower — Monitoring Grid (Sprint 6.3B + 6.3C)
     * --------------------------------------------------------------------------
     * Pure Blade partial — bound to App\ViewModels\Monitoring\MonitoringRowData
     * objects. NO queries, NO business logic here. Every cell renders from the
     * ViewModel. Lives inside WorkspaceShell's Livewire template, so wire:*
     * directives dispatch on the parent (WorkspaceShell).
     *
     * View variables:
     *  - $rows             : array<int, MonitoringRowData>
     *  - $paginator        : LengthAwarePaginator<MonitoringRowData>|null
     *  - $search           : string  (active search term, for highlight only)
     *  - $pageSize         : int
     *  - $groupMode        : string
     *  - $hasActiveFilters : bool (Sprint 6.4.1 — distinguishes "filtered empty"
     *                        from "database empty" in the empty state)
     *  - $exceptionFilter  : ?string (Sprint 6.4.3-R1 — drives the exception-
     *                        specific empty state copy; null when "Semua")
     *  - $exceptionLabel   : ?string (human-readable label, e.g. "Delay")
     */

    use App\ViewModels\Monitoring\MonitoringRowData;
    use App\ViewModels\Monitoring\ExceptionChipData;
    use App\Support\Monitoring\SearchHighlighter;

    /** Map StageResolver flow_zone → mon-badge colour (presentation only). */
    $flowZoneBadge = [
        'pickup'   => 'mon-badge-accent',
        'depot'    => 'mon-badge-warning',
        'vessel'   => 'mon-badge-accent',
        'port'     => 'mon-badge-warning',
        'dooring'  => 'mon-badge-success',
        'hold'     => 'mon-badge-danger',
        'cancelled'=> 'mon-badge-danger',
    ];

    /** Exception priority per Task 7 spec (highest first). */
    $exceptionPriority = [
        'hold', 'ng', 'demurrage', 'delay', 'stuck', 'missing_voyage',
    ];

    $activeSearch = is_string($search ?? null) && trim($search ?? '') !== '';
    $term = $activeSearch ? trim($search) : '';

    // Sprint 6.4.3-R1: KPI vs Table consistency UX — empty state explains
    // *why* the table is empty (search vs exception vs both) instead of a
    // generic "no unit" message, since the KPI above intentionally doesn't
    // reflect these two drill-down filters. Presentation only — no query change.
    $exceptionFilter = $exceptionFilter ?? null;
    $exceptionActive = filled($exceptionFilter);
    $exceptionLabel = $exceptionLabel ?? null;

    /** Sprint 6.4.3: reusable highlight helper (app/Support/Monitoring/SearchHighlighter). */

    $visibleRows = is_array($rows) ? $rows : [];
    $total = $paginator?->total() ?? count($visibleRows);
    $perPage = $paginator?->perPage() ?? $pageSize ?? 50;
    $currentPage = $paginator?->currentPage() ?? 1;
    $lastPage = $paginator?->lastPage() ?? 1;
    $rangeStart = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
    $rangeEnd = (int) min($currentPage * $perPage, $total);

    $skeletonCount = min($perPage, 8);
    $rowCount = count($visibleRows);
@endphp

<div class="jss-mon-table-wrap"
     wire:key="mon-table-{{ $currentPage }}-{{ md5($term) }}"
     data-total-rows="{{ $rowCount }}"
     role="grid"
     aria-label="Monitoring table — {{ $total }} unit"
     aria-rowcount="{{ $total }}"
     tabindex="-1">

    <div class="mon-table-scroll">
        <table class="mon-table" role="table">

            {{-- ── Table Header — 13px sticky, navy ── --}}
            <thead role="rowgroup">
                <tr role="row">
                    <th scope="col" role="columnheader">Unit</th>
                    <th scope="col" role="columnheader">SPPB</th>
                    <th scope="col" role="columnheader">Route</th>
                    <th scope="col" role="columnheader">Stage</th>
                    <th scope="col" role="columnheader">Progress</th>
                    <th scope="col" role="columnheader" class="col-num">Age</th>
                    <th scope="col" role="columnheader">Exception</th>
                    <th scope="col" role="columnheader">Voyage</th>
                    <th scope="col" role="columnheader" class="col-num">ETA</th>
                </tr>
            </thead>

            {{-- ── Loading skeleton — shown whenever a Livewire
                 action (search/refresh/polling/filter/pagination) is in-flight. ── --}}
            <tbody wire:loading.delay class="mon-skel-tbody" role="rowgroup" aria-hidden="true">
                @foreach (range(1, $skeletonCount) as $_)
                <tr role="row" aria-hidden="true">
                    <td><div class="flex flex-col gap-1.5"><span class="mon-skel h-3 w-28"></span><span class="mon-skel h-2.5 w-20"></span></div></td>
                    <td><span class="mon-skel h-3 w-32"></span></td>
                    <td><span class="mon-skel h-3 w-16"></span></td>
                    <td><span class="mon-skel h-5 w-24 rounded-full"></span></td>
                    <td><div class="flex items-center gap-2"><span class="mon-skel h-2 w-16 rounded-full"></span><span class="mon-skel h-2.5 w-8"></span></div></td>
                    <td class="col-num"><span class="mon-skel h-3 w-10 ml-auto inline-block"></span></td>
                    <td><span class="mon-skel h-4 w-16 rounded-full"></span></td>
                    <td><div class="flex flex-col gap-1.5"><span class="mon-skel h-3 w-12"></span><span class="mon-skel h-2.5 w-20"></span></div></td>
                    <td class="col-num"><span class="mon-skel h-3 w-20 ml-auto inline-block"></span></td>
                </tr>
                @endforeach
            </tbody>

            {{-- ── Real table body — HIDDEN while any Livewire action is in-flight ── --}}
            <tbody wire:loading.remove.delay role="rowgroup">
                @if (!empty($visibleRows))
                    @foreach ($visibleRows as $index => $row)
                        @php
                            /** @var MonitoringRowData $row */
                            // Unit identity (Task 2): reg_no bold, model/chassis muted
                            $unitPrimary   = $row->unit_reg_no ?? $row->shipment_code;
                            $unitSecondary = $row->unit_model_no
                                ?? ($row->unit_chassis_no ? 'Chassis ' . $row->unit_chassis_no : null)
                                ?? $row->customer_name;

                            // Stage badge (Task 4) — flow_zone drives colour
                            $zone   = $row->stage->flow_zone;
                            $badge  = $flowZoneBadge[$zone] ?? 'mon-badge-neutral';
                            $stageLabel = $row->stage->is_cancelled ? 'Dibatalkan'
                                : ($row->stage->is_held ? 'Ditahan' : $row->stage->stage_label);

                            // Progress (Task 5) — already computed by ProgressCalculator
                            $pct = (int) $row->progress_pct;
                            $progressFillClass = match(true) {
                                $row->stage->is_cancelled        => 'is-neutral',
                                $pct >= 100                       => 'is-success',
                                $pct >= 75                        => '',
                                default                           => 'is-warning',
                            };

                            // Age (Task 6) — purely from AgeData (no recomputation in Blade)
                            $ageLabel = $row->age->label;
                            $ageClass = match(true) {
                                $row->stage->is_held, $row->age->is_stuck => 'is-old',
                                $row->age->fallback_used                    => 'is-stale',
                                default                                     => 'is-fresh',
                            };
                            if ($row->age->fallback_used) {
                                $ageLabel .= ' (est)';
                            }

                            // Exceptions (Task 7) — priority-sorted chips
                            $sortedExceptions = collect($row->exceptions)
                                ->sortBy(fn(ExceptionChipData $c) =>
                                    array_search($c->type, $exceptionPriority, true) === false
                                        ? 99
                                        : array_search($c->type, $exceptionPriority, true))
                                ->values();

                            // Voyage (Task 8)
                            $hasVoyage = filled($row->voyage_no) || filled($row->vessel_name);

                            // ETA (Task 9)
                            $eta = $row->eta_label;

                            $clickTarget = $row->unit_id ?? $row->shipment_id;
                        @endphp

                        <tr
                            tabindex="0"
                            role="row"
                            aria-label="Buka detail unit {{ $unitPrimary }}"
                            data-unit-id="{{ $clickTarget }}"
                            data-row-index="{{ $index }}"
                            wire:click="$dispatch('open-unit-detail', { unitId: {{ $clickTarget }} })"
                            wire:keydown.enter.prevent="$dispatch('open-unit-detail', { unitId: {{ $clickTarget }} })"
                            class="mon-row mon-row-fadein"
                            :class="{ 'is-focused': focusedIndex === {{ $index }}, 'is-selected': selectedRowIndex === {{ $index }} }"
                            style="--row-idx: {{ $index }}"
                        >
                            {{-- Unit (Task 2 + Task 10 highlight) --}}
                            <td role="cell">
                                <div class="flex flex-col gap-0.5">
                                    <span class="mon-unit-code">{!! SearchHighlighter::highlight($unitPrimary, $term) !!}</span>
                                    <span class="mon-unit-sub">{!! SearchHighlighter::highlight($unitSecondary, $term) !!}</span>
                                </div>
                            </td>

                            {{-- SPPB (Task 10 highlight) --}}
                            <td role="cell">
                                <span class="mon-unit-code text-[13px]">{!! SearchHighlighter::highlight($row->doc_number, $term) !!}</span>
                            </td>

                            {{-- Route (Task 3) — simple with icon --}}
                            <td role="cell">
                                <span class="inline-flex items-center gap-1 text-gray-600">
                                    <x-heroicon-o-arrow-right class="w-3.5 h-3.5 text-gray-400" />
                                    <span class="font-medium">{{ $row->route_label }}</span>
                                </span>
                            </td>

                            {{-- Stage (Task 4) — flow-zone badge --}}
                            <td role="cell">
                                <span class="mon-badge {{ $badge }}">{{ $stageLabel }}</span>
                            </td>

                            {{-- Progress (Task 5) --}}
                            <td role="cell">
                                <div class="flex items-center gap-2">
                                    <div class="mon-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress {{ $pct }} persen">
                                        <div class="mon-progress-fill mon-progress-anim {{ $progressFillClass }}" style="width: {{ max(0, min(100, $pct)) }}%"></div>
                                    </div>
                                    <span class="mon-pct">{{ $pct }}%</span>
                                </div>
                            </td>

                            {{-- Age (Task 6) — right-aligned, colour-coded --}}
                            <td role="cell" class="col-num">
                                <span class="mon-age {{ $ageClass }}">{{ $ageLabel }}</span>
                            </td>

                            {{-- Exception (Task 7) — priority chips or em-dash --}}
                            <td role="cell">
                                @if ($sortedExceptions->isNotEmpty())
                                    <div class="flex flex-wrap gap-1" role="list" aria-label="Exceptions">
                                        @foreach ($sortedExceptions as $ex)
                                            @php
                                                $exClass = $ex->severity === 'critical'
                                                    ? 'mon-badge-danger'
                                                    : ($ex->severity === 'warning' ? 'mon-badge-warning' : 'mon-badge-neutral');
                                                $exLabel = $ex->count ? $ex->label . ' · ' . $ex->count : $ex->label;
                                            @endphp
                                            <span class="mon-badge mon-badge-anim {{ $exClass }}" title="{{ $ex->detail ?? $ex->label }}" role="listitem">
                                                {{ $exLabel }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-300" aria-label="No exceptions">—</span>
                                @endif
                            </td>

                            {{-- Voyage (Task 8 + Task 10 highlight on voyage_no) --}}
                            <td role="cell">
                                @if ($hasVoyage)
                                    <div class="flex flex-col gap-0.5">
                                        <span class="mon-unit-code text-[13px]">{!! SearchHighlighter::highlight($row->voyage_no, $term) !!}</span>
                                        @if (filled($row->vessel_name))
                                            <span class="mon-unit-sub">{!! SearchHighlighter::highlight($row->vessel_name, $term) !!}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="mon-badge mon-badge-neutral">Belum Assign</span>
                                @endif
                            </td>

                            {{-- ETA (Task 9) — simple, right-aligned --}}
                            <td role="cell" class="col-num">
                                <span class="text-gray-700 tabular-nums">{{ $eta ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach

                @else

                    {{-- ── Empty state (Task 7 / Sprint 6.4.1) — enterprise-calm with CTA.
                         Distinguishes "no filter matches" from "database genuinely empty". ── --}}
                    @php $filtersActive = $hasActiveFilters ?? false; @endphp
                    <tr role="row">
                        <td colspan="9" role="cell">
                            @if ($activeSearch && $exceptionActive)
                                {{-- Sprint 6.4.3-R1: search + exception both active —
                                     combined message, no need to spell out both values
                                     here since the context bar above already shows them. --}}
                                <div class="mon-empty" role="status" aria-label="Tidak ada shipment yang cocok dengan filter yang dipilih">
                                    <div class="mon-empty-icon">
                                        <x-heroicon-o-funnel class="w-8 h-8" />
                                    </div>
                                    <p class="mon-empty-title">Tidak ada shipment yang cocok</p>
                                    <p class="mon-empty-sub">dengan filter yang dipilih.</p>
                                    <button
                                        type="button"
                                        wire:click="resetFilters"
                                        class="mon-empty-cta"
                                        aria-label="Reset semua filter"
                                    >
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                        Reset Filter
                                    </button>
                                </div>
                            @elseif ($activeSearch)
                                <div class="mon-empty mon-empty-search" role="status" aria-label="Tidak ditemukan hasil pencarian untuk {{{ $term }}}">
                                    <div class="mon-empty-icon">
                                        <x-heroicon-o-magnifying-glass class="w-8 h-8" />
                                    </div>
                                    <p class="mon-empty-title">Tidak ditemukan hasil pencarian</p>
                                    <p class="mon-empty-sub">Tidak ada shipment yang cocok dengan:</p>
                                    <p class="mon-empty-term">{{{ $term }}}</p>
                                    <button
                                        type="button"
                                        wire:click="updateFilter('search', '')"
                                        class="mon-empty-cta"
                                        aria-label="Hapus pencarian"
                                    >
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                        Hapus pencarian
                                    </button>
                                </div>
                            @elseif ($exceptionActive)
                                {{-- Sprint 6.4.3-R1: exception-only empty state (new). --}}
                                <div class="mon-empty" role="status" aria-label="Tidak ada shipment dengan exception {{ $exceptionLabel }}">
                                    <div class="mon-empty-icon">
                                        <x-heroicon-o-funnel class="w-8 h-8" />
                                    </div>
                                    <p class="mon-empty-title">Tidak ada shipment dengan exception:</p>
                                    <p class="mon-empty-term">{{ $exceptionLabel }}</p>
                                    <button
                                        type="button"
                                        wire:click="updateFilter('exception_filter', null)"
                                        class="mon-empty-cta"
                                        aria-label="Hapus filter exception"
                                    >
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                        Hapus filter
                                    </button>
                                </div>
                            @elseif ($filtersActive)
                                <div class="mon-empty" role="status" aria-label="Tidak ada unit yang sesuai dengan filter">
                                    <div class="mon-empty-icon">
                                        <x-heroicon-o-funnel class="w-8 h-8" />
                                    </div>
                                    <p class="mon-empty-title">Tidak ada unit yang sesuai dengan filter.</p>
                                    <p class="mon-empty-sub">Coba ubah atau hapus filter yang sedang aktif</p>
                                    <button
                                        type="button"
                                        wire:click="resetFilters"
                                        class="mon-empty-cta"
                                        aria-label="Reset semua filter"
                                    >
                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                        Reset Filter
                                    </button>
                                </div>
                            @else
                                <div class="mon-empty" role="status" aria-label="Tidak ada unit yang sedang dipantau">
                                    <div class="mon-empty-icon">
                                        <x-heroicon-o-inbox class="w-8 h-8" />
                                    </div>
                                    <p class="mon-empty-title">Tidak ada unit yang sedang dipantau</p>
                                    <p class="mon-empty-sub">Periksa filter aktif atau tekan Refresh untuk memuat ulang data</p>
                                    <button
                                        type="button"
                                        wire:click="refresh"
                                        class="mon-empty-cta"
                                        aria-label="Muat ulang data workspace"
                                    >
                                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                                        Refresh
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>

                @endif
            </tbody>
        </table>
    </div>

    {{-- Table foot strip — range info + pagination --}}
    @if ($total > 0)
        <div class="mon-table-foot" role="navigation" aria-label="Pagination">
            <span class="mon-foot" aria-live="polite">
                Menampilkan <strong class="text-gray-600">{{ $rangeStart }}&ndash;{{ $rangeEnd }}</strong> dari <strong class="text-gray-600">{{ $total }}</strong> unit
            </span>

            @if ($lastPage > 1)
                <nav class="flex items-center gap-1" aria-label="Navigasi halaman">
                    @if ($currentPage > 1)
                        <button type="button" wire:click="gotoPage({{ $currentPage - 1 }})" class="mon-page" aria-label="Halaman sebelumnya">
                            <x-heroicon-o-chevron-left class="w-4 h-4" />
                        </button>
                    @endif

                    @php
                        $pages = collect(range(max(1, $currentPage - 2), min($lastPage, $currentPage + 2)));
                    @endphp
                    @if ($pages->first() > 1)
                        <button type="button" wire:click="gotoPage(1)" class="mon-page" aria-label="Halaman 1">1</button>
                        @if ($pages->first() > 2)<span class="mon-page-dots" aria-hidden="true">&hellip;</span>@endif
                    @endif

                    @foreach ($pages as $p)
                        <button
                            type="button"
                            wire:click="gotoPage({{ $p }})"
                            class="mon-page {{ $p === $currentPage ? 'is-active' : '' }}"
                            aria-current="{{ $p === $currentPage ? 'page' : 'false' }}"
                            aria-label="Halaman {{ $p }}"
                        >{{ $p }}</button>
                    @endforeach

                    @if ($pages->last() < $lastPage)
                        @if ($pages->last() < $lastPage - 1)<span class="mon-page-dots" aria-hidden="true">&hellip;</span>@endif
                        <button type="button" wire:click="gotoPage({{ $lastPage }})" class="mon-page" aria-label="Halaman {{ $lastPage }}">{{ $lastPage }}</button>
                    @endif

                    @if ($currentPage < $lastPage)
                        <button type="button" wire:click="gotoPage({{ $currentPage + 1 }})" class="mon-page" aria-label="Halaman berikutnya">
                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                        </button>
                    @endif
                </nav>
            @endif
        </div>
    @endif

</div>
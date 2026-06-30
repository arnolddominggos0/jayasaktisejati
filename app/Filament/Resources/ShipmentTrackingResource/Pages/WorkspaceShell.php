<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\DTO\Monitoring\MonitoringFilter;
use App\Filament\Resources\ShipmentTrackingResource;
use App\Services\Monitoring\ExceptionCounterService;
use App\Services\Monitoring\MonitoringQueryService;
use App\Services\Monitoring\WorkspaceSummaryBuilder;
use App\Support\Monitoring\PeriodResolver;
use App\Support\Monitoring\RouteResolver;
use App\ViewModels\Monitoring\ExceptionBandData;
use App\ViewModels\Monitoring\WorkspaceSummaryData;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class WorkspaceShell extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ShipmentTrackingResource::class;

    protected static string $view = 'filament.pages.pelacakan-monitoring';

    protected ?string $maxContentWidth = 'full';

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Pelacakan & Monitoring';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        // Heading is rendered inside the custom workspace header card.
        return '';
    }

    // ── Public Livewire state — Sprint 6.4.1: all filter dimensions are
    // URL-stateable (#[Url]) so a browser refresh never loses the current
    // filter/sort/page. `except` keeps the URL clean when at the default. ──

    /**
     * Sprint 6.4.2: workspace branch context. URL-stateable for Super Admin
     * sharing; forcibly overwritten in mount() for Office Admin regardless
     * of what the URL says — see mount() for the authorization ordering.
     */
    #[Url(as: 'branch', except: null)]
    public ?int $branch_id = null;

    public ?string $mode = null;

    #[Url(except: '')]
    public ?string $route = null;

    #[Url(as: 'exception', except: null)]
    public ?string $exception_filter = null;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'view', except: 'flat')]
    public string $group_mode = 'flat';

    /** 'active' | 'finished' | 'all'. Replaces the old boolean show_finished. */
    #[Url(except: 'active')]
    public string $status = 'active';

    /**
     * Hotfix (status toggle desync): the 'show_finished' Toggle field is the
     * only toolbar field whose Filament form key didn't match any declared
     * Livewire property — every other field (period/search/exception_filter/
     * group_mode) maps 1:1 to a real typed property, so $this->form->fill()
     * just assigns the existing property. For 'show_finished' there was no
     * such property, so PHP created one dynamically at runtime (the
     * "Creation of dynamic property ...::$show_finished is deprecated"
     * warning visible in every request). Livewire's hydrate/dehydrate cycle
     * is built around reflecting a component's *declared* properties, so an
     * undeclared one isn't reliably round-tripped across requests — the
     * toggle could flip visually (its own Alpine-entangled state) without
     * the corresponding afterStateUpdated() -> updateFilter('status', ...)
     * reliably reaching $status. Declaring it here makes it behave exactly
     * like every sibling field. Not Url-bound itself — $status remains the
     * single URL-bound source of truth; this is purely a derived UI mirror
     * of it (see mount()'s form fill: 'show_finished' => $status === 'all').
     */
    public bool $show_finished = false;

    #[Url(except: 'exception-first')]
    public string $sort = 'exception-first';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(as: 'per_page', except: 50)]
    public int $page_size = 50;

    /**
     * Sprint 6.4.2: workspace period context, format 'YYYY-MM'. No `except`
     * clause — the period is always shown in the URL (e.g. ?period=2026-06)
     * per the sprint's own shareable-link example, even when it's the
     * current month.
     */
    #[Url(as: 'period')]
    public string $period = '';

    // ── Computed data (all protected — Livewire 3 cannot serialize these types) ──

    protected ?LengthAwarePaginator $rows = null;

    protected ?ExceptionBandData $exceptionBand = null;

    protected ?WorkspaceSummaryData $workspaceSummary = null;

    // ── Lifecycle ──────────────────────────────────────────────────────────

    public function mount(): void
    {
        $user = auth_user();

        // Period/page-size validated regardless of persona — defends against
        // URL tampering (?period=garbage, ?per_page=99999, ?period= empty).
        // Belt-and-suspenders: Livewire's own #[Url] hydration already
        // coerces a blank query value to '' (not null) for a non-nullable
        // typed property, but normalizePeriod() is the single source of
        // truth shared with updateFilter() — see its docblock.
        $this->period    = $this->normalizePeriod($this->period);
        $this->page_size = $this->normalizePageSize($this->page_size);
        $this->route   ??= RouteResolver::default();

        // Authorization boundary: runs AFTER Url-attribute hydration, so an
        // Office Admin's own branch always wins even if ?branch=<other> was
        // shared with them. Super Admin keeps whatever the URL/default gave.
        if ($user?->isOfficeAdmin()) {
            $this->branch_id = $user->effectiveBranchId();
        }

        $this->form->fill([
            // Sprint 6.4.2-R1: period moved from the standalone context bar
            // into the toolbar form — same $this->period property/URL state.
            'period'            => $this->period,
            'exception_filter'  => $this->exception_filter,
            'search'            => $this->search,
            'group_mode'        => $this->group_mode,
            // The Toggle's own field key stays 'show_finished' (Filament form
            // artifact only — no Livewire property of that name exists anymore).
            // ON maps to status='all', OFF maps to status='active'.
            'show_finished'     => $this->status === 'all',
        ]);

        $this->generateData();
    }

    // ── Form schema ────────────────────────────────────────────────────────

    protected function getFormSchema(): array
    {
        return [
            Grid::make()
                // Sprint 6.4.2-R1: 12 → 14 columns to fit Period as the first
                // field without shrinking Search. Order: Periode, Cari,
                // Exception, Tampilan, Selesai (Refresh sits outside the form).
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 14])
                ->schema([
                    // Periode: FIRST — workspace's primary table filter (Sprint 6.4.2-R1)
                    Select::make('period')
                        ->label('Periode')
                        ->options(PeriodResolver::options())
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('period', $state))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),

                    // Search: PRIMARY — widest (lg:6), icon prefix.
                    // Clear button + loading spinner are native Filament affixes
                    // (suffixAction / wire:target loading indicator), scoped to
                    // this field's own input wrapper — NOT a manually absolute-
                    // positioned overlay on the whole form. That hand-rolled
                    // approach broke visually once Periode shifted the grid
                    // (Sprint 6.4.2-R1): the overlay anchored to the bottom-right
                    // of the entire multi-field form instead of just this input,
                    // landing as a stray floating button between Tampilan and
                    // Selesai. Native affixes can't drift like that.
                    TextInput::make('search')
                        ->label('Cari')
                        ->prefixIcon('heroicon-o-magnifying-glass')
                        ->placeholder('Cari unit, SPPB, chassis, voyage…')
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('search', $state ?? ''))
                        ->suffixAction(
                            Action::make('clearSearch')
                                ->icon('heroicon-o-x-mark')
                                ->label('Hapus pencarian')
                                ->visible(fn (?string $state): bool => filled($state))
                                ->action(fn () => $this->updateFilter('search', '')),
                        )
                        ->extraAttributes(['wire:target' => 'data.search'])
                        ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 6]),

                    // Exception: SECONDARY — compact dropdown (lg:2)
                    Select::make('exception_filter')
                        ->label('Exception')
                        ->placeholder('Semua')
                        ->options([
                            'hold'           => 'Hold',
                            'ng'             => 'NG',
                            'demurrage'      => 'Demurrage',
                            'delay'          => 'Delay',
                            'stuck'          => 'Stuck',
                            'missing_voyage' => 'Missing Voyage',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('exception_filter', $state))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),

                    // Group: TERTIARY — Select dropdown (ToggleButtons removed: rarely
                    // changed, not a primary action; dropdown is compact + extensible)
                    Select::make('group_mode')
                        ->label('Tampilan')
                        ->placeholder('Flat')
                        ->options([
                            'flat'   => 'Flat',
                            'sppb'   => 'Per SPPB',
                            'voyage' => 'Per Voyage',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('group_mode', $state ?? 'flat'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),

                    // Show Finished: compact toggle — single word fits lg:2 without wrap.
                    // Writes to the new 3-state $status (OFF='active', ON='all');
                    // 'finished'-only is reachable via URL/method call (?status=finished).
                    Toggle::make('show_finished')
                        ->label('Selesai')
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('status', $state ? 'all' : 'active'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),
                ]),
        ];
    }

    // ── Actions ────────────────────────────────────────────────────────────

    /**
     * Hotfix (period hydration): generic setter for every toolbar field —
     * including 'period', which is a non-nullable typed property
     * (`public string $period`, deliberately kept non-nullable: the
     * Workspace must always have an active period). A blank Select state
     * (null/'') reaching this method via afterStateUpdated() would
     * otherwise hit `$this->{$field} = $value` directly and throw a
     * TypeError before normalizePeriod() ever runs — so 'period' is
     * special-cased here, ahead of the generic assignment, rather than
     * trusting the caller to already have a valid value.
     */
    public function updateFilter(string $field, mixed $value): void
    {
        if ($field === 'period') {
            $value = $this->normalizePeriod($value);
        }

        $this->{$field} = $value;
        $this->page = 1;
        $this->generateData();
    }

    /**
     * Sprint 6.4.2: dedicated branch setter — native <select> values arrive
     * as strings, and `branch_id` is a typed ?int, so the generic
     * updateFilter() would throw a TypeError on the empty-string
     * "Semua Cabang" option. Office Admins never see this control (their
     * branch is read-only), so there's no authorization check to repeat here.
     */
    public function updateBranch(string $value): void
    {
        $this->branch_id = $value === '' ? null : (int) $value;
        $this->page = 1;
        $this->generateData();
    }

    /**
     * Sprint 6.4.1: validated page-size switch. Resets to page 1 since the
     * total page count changes with the page size.
     */
    public function updatePageSize(int $size): void
    {
        $this->page_size = $this->normalizePageSize($size);
        $this->page = 1;
        $this->generateData();
    }

    /**
     * Sprint 6.4.1: reset every filter dimension to its default in one call
     * (used by the empty-state "Reset Filter" CTA). Branch/route scope is
     * left untouched — that's an authorization boundary, not a user filter.
     * Period is also left untouched — it's workspace context, not a filter
     * being "reset away".
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->exception_filter = null;
        $this->status = 'active';
        $this->group_mode = 'flat';
        $this->sort = 'exception-first';
        $this->page = 1;

        $this->form->fill([
            'exception_filter' => null,
            'search'           => '',
            'group_mode'       => 'flat',
            'show_finished'    => false,
        ]);

        $this->generateData();
    }

    public function refresh(): void
    {
        $this->generateData();
        $this->dispatch('refresh-complete');
    }

    #[On('close-detail')]
    public function closeDetail(): void
    {
        $this->dispatch('close-detail')->to(\App\Livewire\Monitoring\MonitoringDetailSlide::class);
    }

    public function pollRefresh(): void
    {
        $filter = $this->buildFilter();
        $this->exceptionBand    = app(ExceptionCounterService::class)->count($filter);
        $this->workspaceSummary = app(WorkspaceSummaryBuilder::class)->build($filter);
        $this->dispatch('poll-complete');
    }

    /**
     * Pagination binding — small Livewire action so the monitoring-table
     * partial can request page changes. Presentation only; no query rule.
     */
    public function gotoPage(int $page): void
    {
        if ($page < 1) {
            $page = 1;
        }
        $this->page = $page;
        $this->generateData();
    }

    // ── Data generation ────────────────────────────────────────────────────

    protected function generateData(): void
    {
        $filter = $this->buildFilter();

        try {
            $this->rows             = app(MonitoringQueryService::class)->paginate($filter);
            $this->exceptionBand    = app(ExceptionCounterService::class)->count($filter);
            $this->workspaceSummary = app(WorkspaceSummaryBuilder::class)->build($filter);
        } catch (\Throwable $e) {
            logger()->error('[WORKSPACE_SHELL] data generation failed', [
                'filter' => $filter->toArray(),
                'error'  => $e->getMessage(),
            ]);

            $this->rows             = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $filter->page_size);
            $this->exceptionBand    = ExceptionBandData::empty();
            $this->workspaceSummary = WorkspaceSummaryData::empty();
        }
    }

    protected function buildFilter(): MonitoringFilter
    {
        return new MonitoringFilter(
            branch_id:        $this->branch_id,
            mode:             $this->mode ?: null,
            route:            $this->route ?: null,
            exception_filter: $this->exception_filter ?: null,
            search:           $this->search ?? '',
            group_mode:       $this->group_mode ?: 'flat',
            status:           $this->normalizeStatus($this->status),
            sort:             $this->sort ?: 'exception-first',
            page:             $this->page,
            page_size:        $this->page_size,
            period:           PeriodResolver::normalize($this->period),
        );
    }

    // ── View data ──────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        // Recompute any protected data that was lost across Livewire round-trips
        if ($this->rows === null || $this->exceptionBand === null || $this->workspaceSummary === null) {
            $filter = $this->buildFilter();
            $this->rows             ??= app(MonitoringQueryService::class)->paginate($filter);
            $this->exceptionBand    ??= app(ExceptionCounterService::class)->count($filter);
            $this->workspaceSummary ??= app(WorkspaceSummaryBuilder::class)->build($filter);
        }

        // Sprint 6.4.2-R1: Branch is no longer rendered as a toolbar/context
        // selector (read-only indicator only, via $summary->branch below), so
        // the per-request Branch::query() lookup that fed the old selector
        // is no longer computed here — backend (branch_id, updateBranch(),
        // URL state) stays fully intact for when a selector is reintroduced.
        $isOfficeAdmin = (bool) auth_user()?->isOfficeAdmin();

        return [
            'rows'              => $this->rows,
            'exceptionBand'     => $this->exceptionBand,
            'workspaceSummary'  => $this->workspaceSummary,
            'pollInterval'      => config('monitoring.poll_interval', 60),
            'pageSize'          => $this->page_size,
            'exceptionFilter'   => $this->exception_filter,
            'groupMode'         => $this->group_mode,
            'activeFilterCount' => $this->countActiveFilters($isOfficeAdmin),
            'hasActiveFilters'  => $this->countActiveFilters($isOfficeAdmin) > 0,
            'activeFilterChips' => $this->activeFilterChips(),
        ];
    }

    private function countActiveFilters(bool $isOfficeAdmin): int
    {
        return collect([
            $this->mode                                                 ? 1 : 0,
            ($this->route && $this->route !== RouteResolver::default()) ? 1 : 0,
            $this->exception_filter                                     ? 1 : 0,
            strlen($this->search) > 0                                   ? 1 : 0,
            $this->status !== 'active'                                  ? 1 : 0,
            ($this->sort && $this->sort !== 'exception-first')          ? 1 : 0,
            // Branch is only a "filter" when Super Admin actively chose one —
            // for Office Admin it's a fixed scope, not something they toggled.
            (! $isOfficeAdmin && $this->branch_id)                      ? 1 : 0,
            ($this->period !== PeriodResolver::default())               ? 1 : 0,
        ])->sum();
    }

    /**
     * Sprint 6.4.1: removable filter chips for the Active Filter row.
     * Exception is deliberately excluded — it already has its own chip+reset
     * in the exception band (section 2), no need to duplicate that control.
     * Sprint 6.4.2-R1: Period now gets a chip when it isn't the current
     * month, same pattern as Status/Tampilan (which also have persistent
     * toolbar controls AND a chip when non-default). Branch stays excluded —
     * it isn't a toolbar filter for Office Admin, and Super Admin has no
     * selector yet (backend-only, per this sprint's scope).
     */
    private function activeFilterChips(): array
    {
        $chips = [];

        if ($this->period !== PeriodResolver::default()) {
            $chips[] = [
                'field' => 'period',
                'label' => 'Periode',
                'value' => ucfirst(PeriodResolver::bounds($this->period)[0]->translatedFormat('F Y')),
                'clear' => PeriodResolver::default(),
            ];
        }

        if (strlen($this->search) > 0) {
            $chips[] = [
                'field' => 'search',
                'label' => 'Cari',
                'value' => $this->search,
                'clear' => '',
            ];
        }

        if ($this->status !== 'active') {
            $statusLabels = ['finished' => 'Selesai', 'all' => 'Semua Status'];
            $chips[] = [
                'field' => 'status',
                'label' => 'Status',
                'value' => $statusLabels[$this->status] ?? $this->status,
                'clear' => 'active',
            ];
        }

        if ($this->group_mode !== 'flat') {
            $groupLabels = ['sppb' => 'Per SPPB', 'voyage' => 'Per Voyage'];
            $chips[] = [
                'field' => 'group_mode',
                'label' => 'Tampilan',
                'value' => $groupLabels[$this->group_mode] ?? $this->group_mode,
                'clear' => 'flat',
            ];
        }

        if ($this->sort !== 'exception-first') {
            $chips[] = [
                'field' => 'sort',
                'label' => 'Urutan',
                'value' => $this->sort,
                'clear' => 'exception-first',
            ];
        }

        if ($this->route && $this->route !== RouteResolver::default()) {
            $chips[] = [
                'field' => 'route',
                'label' => 'Route',
                'value' => strtoupper($this->route),
                'clear' => RouteResolver::default(),
            ];
        }

        return $chips;
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, config('monitoring.status_options', ['active', 'finished', 'all']), true)
            ? $status
            : 'active';
    }

    /**
     * Hotfix (period hydration): coerce any incoming value — null, '',
     * or a non-string type from a malformed Livewire/URL payload — to a
     * guaranteed-valid 'Y-m' period string before it ever reaches the
     * `public string $period` property. PeriodResolver::normalize() already
     * falls back to the current month for an invalid *string*; the
     * is_string() guard here exists only so a non-string (e.g. an
     * unexpected null/array) never reaches it — PeriodResolver::normalize()
     * is typed `?string`, so passing a non-string/non-null value (e.g. an
     * array) would itself throw before the format regex ever runs.
     * PeriodResolver itself is untouched.
     */
    private function normalizePeriod(mixed $value): string
    {
        return PeriodResolver::normalize(is_string($value) && $value !== '' ? $value : null);
    }

    private function normalizePageSize(int $size): int
    {
        $allowed = config('monitoring.page_size_options', [25, 50, 100, 200]);

        return in_array($size, $allowed, true) ? $size : (int) config('monitoring.page_size', 50);
    }
}

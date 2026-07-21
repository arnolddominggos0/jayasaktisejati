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

    /** Office Admin is always pinned to their own branch (enforced in mount()). */
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

    /** 'active' | 'finished' | 'all'. */
    #[Url(except: 'active')]
    public string $status = 'active';

    public bool $show_finished = false;

    #[Url(except: 'exception-first')]
    public string $sort = 'exception-first';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(as: 'per_page', except: 50)]
    public int $page_size = 50;

    public ?int $selectedUnitId = null;
    public bool $detailOpen = false;
    public bool $detailLoading = false;

    #[Url(as: 'period')]
    public string $period = '';

    // Protected: Livewire 3 cannot serialize these types.
    protected ?LengthAwarePaginator $rows = null;

    protected ?ExceptionBandData $exceptionBand = null;

    protected ?WorkspaceSummaryData $workspaceSummary = null;

    public function mount(): void
    {
        $user = auth_user();

        $this->period    = $this->normalizePeriod($this->period);
        $this->page_size = $this->normalizePageSize($this->page_size);
        $this->route   ??= RouteResolver::default();

        // Office Admin is always pinned to their own branch, ignoring any shared ?branch.
        if ($user?->isOfficeAdmin()) {
            $this->branch_id = $user->effectiveBranchId();
        }

        $this->form->fill([
            'exception_filter'  => $this->exception_filter,
            'search'            => $this->search,
            'group_mode'        => $this->group_mode,
            'branch_id'         => $this->branch_id,
        ]);

        $this->generateData();
    }

    protected function getFormSchema(): array
    {
        $isOfficeAdmin = (bool) auth_user()?->isOfficeAdmin();

        return [
            Grid::make()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 14])
                ->schema([
                    TextInput::make('search')
                        ->label('Cari')
                        ->prefixIcon('heroicon-o-magnifying-glass')
                        ->placeholder('Cari unit, SPPB, chassis, voyage…')
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
                        ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => $isOfficeAdmin ? 8 : 6]),

                    Select::make('exception_filter')
                        ->label('Exception')
                        ->placeholder('Semua')
                        ->options([
                            'hold'           => 'Ditahan',
                            'ng'             => 'Temuan NG',
                            'demurrage'      => 'Demurrage',
                            'delay'          => 'Terlambat',
                            'stuck'          => 'Perlu Tindak Lanjut',
                            'missing_voyage' => 'Belum Ada Voyage',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('exception_filter', $state))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),

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

                    Select::make('branch_id')
                        ->label('Cabang')
                        ->placeholder('Semua Cabang')
                        ->options(fn () => \App\Models\Branch::orderBy('name')->pluck('name', 'id'))
                        ->visible(! $isOfficeAdmin)
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateBranch((string) ($state ?? '')))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),
                ]),
        ];
    }

    /** 'period' is non-nullable, so a blank value is normalized before assignment. */
    public function updateFilter(string $field, mixed $value): void
    {
        if ($field === 'period') {
            $value = $this->normalizePeriod($value);
        }

        $this->{$field} = $value;
        $this->page = 1;
        $this->generateData();
    }

    /** '' ("Semua Cabang") maps to null. */
    public function updateBranch(string $value): void
    {
        $this->branch_id = $value === '' ? null : (int) $value;
        $this->page = 1;
        $this->generateData();
    }

    /** Resets to page 1 because the page count changes with the page size. */
    public function updatePageSize(int $size): void
    {
        $this->page_size = $this->normalizePageSize($size);
        $this->page = 1;
        $this->generateData();
    }

    /** Branch/route (authorization scope) and period (workspace context) are intentionally preserved. */
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
        ]);

        $this->generateData();
    }

    public function refresh(): void
    {
        $this->generateData();
        $this->dispatch('refresh-complete');
    }

    #[On('open-unit-detail')]
    public function openDetail(int $unitId): void
    {
        $this->selectedUnitId = $unitId;
        $this->detailOpen = true;
        $this->detailLoading = true;
    }

    #[On('detail-loaded')]
    public function onDetailLoaded(): void
    {
        $this->detailLoading = false;
    }

    #[On('close-detail')]
    public function closeDetail(): void
    {
        $this->selectedUnitId = null;
        $this->detailOpen = false;
        $this->detailLoading = false;
        $this->dispatch('close-detail')->to(\App\Livewire\Monitoring\MonitoringDetailSlide::class);
    }

    public function pollRefresh(): void
    {
        $filter = $this->buildFilter();
        $this->exceptionBand    = app(ExceptionCounterService::class)->count($filter);
        $this->workspaceSummary = app(WorkspaceSummaryBuilder::class)->build($filter);
        $this->dispatch('poll-complete');
    }

    public function gotoPage(int $page): void
    {
        if ($page < 1) {
            $page = 1;
        }
        $this->page = $page;
        $this->generateData();
        $this->dispatch('poll-complete');
    }

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
            // Monitoring is an active-units workspace only (UX Architecture
            // Freeze v1.1) — historical/finished units belong to the future
            // Monitoring Archive module, not this page.
            status:           'active',
            sort:             $this->sort ?: 'exception-first',
            page:             $this->page,
            page_size:        $this->page_size,
            period:           PeriodResolver::normalize($this->period),
        );
    }

    protected function getViewData(): array
    {
        // Protected data is lost between requests; rebuild it when missing.
        if ($this->rows === null || $this->exceptionBand === null || $this->workspaceSummary === null) {
            $filter = $this->buildFilter();
            $this->rows             ??= app(MonitoringQueryService::class)->paginate($filter);
            $this->exceptionBand    ??= app(ExceptionCounterService::class)->count($filter);
            $this->workspaceSummary ??= app(WorkspaceSummaryBuilder::class)->build($filter);
        }

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
            ($this->sort && $this->sort !== 'exception-first')          ? 1 : 0,
            // Branch counts as a filter only when Super Admin chose one.
            (! $isOfficeAdmin && $this->branch_id)                      ? 1 : 0,
        ])->sum();
    }

    /** Exception and Branch are excluded — they already have their own chip/indicator elsewhere. */
    private function activeFilterChips(): array
    {
        $chips = [];

        if (strlen($this->search) > 0) {
            $chips[] = [
                'field' => 'search',
                'label' => 'Cari',
                'value' => $this->search,
                'clear' => '',
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

    /** Coerces any value to a valid 'Y-m' string; the current month is the fallback. */
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

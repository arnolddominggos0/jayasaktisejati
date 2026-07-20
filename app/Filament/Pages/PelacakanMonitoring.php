<?php

namespace App\Filament\Pages;

use App\DTO\Monitoring\MonitoringFilter;
use App\Services\Monitoring\ExceptionCounterService;
use App\Services\Monitoring\MonitoringQueryService;
use App\Services\Monitoring\WorkspaceSummaryBuilder;
use App\Support\Monitoring\PeriodResolver;
use App\Support\Monitoring\RouteResolver;
use App\ViewModels\Monitoring\ExceptionBandData;
use App\ViewModels\Monitoring\WorkspaceSummaryData;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;

class PelacakanMonitoring extends Page implements HasForms
{
    use InteractsWithForms;

    // Navigation handled by ShipmentTrackingResource/WorkspaceShell.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string $view = 'filament.pages.pelacakan-monitoring';

    protected static ?string $title = 'Pelacakan & Monitoring';

    protected ?string $maxContentWidth = 'full';

    // Public state (Livewire): URL-stateable filters.

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

    #[Url(except: 'exception-first')]
    public string $sort = 'exception-first';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(as: 'per_page', except: 50)]
    public int $page_size = 50;

    /** Workspace period context, format 'YYYY-MM'. */
    #[Url(as: 'period')]
    public string $period = '';

    // ── Computed data (protected — Livewire 3 cannot serialize LengthAwarePaginator) ──

    protected ?LengthAwarePaginator $rows = null;

    protected ?ExceptionBandData $exceptionBand = null;

    protected ?WorkspaceSummaryData $workspaceSummary = null;

    public function mount(): void
    {
        $user = auth_user();

        $this->period    = PeriodResolver::normalize($this->period ?: null);
        $this->page_size = $this->normalizePageSize($this->page_size);
        $this->route   ??= RouteResolver::default();

        if ($user?->isOfficeAdmin()) {
            $this->branch_id = $user->effectiveBranchId();
        }

        $this->form->fill([
            'exception_filter' => $this->exception_filter,
            'search'           => $this->search,
            'group_mode'       => $this->group_mode,
            'show_finished'    => $this->status === 'all',
        ]);

        $this->generateData();
    }

    protected function getFormSchema(): array
    {
        // Sea-mode TAM only; mode select is removed.
        return [
            Grid::make()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 12])
                ->schema([
                    TextInput::make('search')
                        ->label('Cari')
                        ->prefixIcon('heroicon-o-magnifying-glass')
                        ->placeholder('Unit, SPPB, chassis, voyage...')
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('search', $state ?? ''))
                        ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 6]),

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

                    Toggle::make('show_finished')
                        ->label('Selesai')
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->updateFilter('status', $state ? 'all' : 'active'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 2]),
                ]),
        ];
    }

    public function updateFilter(string $field, mixed $value): void
    {
        $this->{$field} = $value;
        $this->page = 1;
        $this->generateData();
    }

    public function updateBranch(string $value): void
    {
        $this->branch_id = $value === '' ? null : (int) $value;
        $this->page = 1;
        $this->generateData();
    }

    public function updatePageSize(int $size): void
    {
        $this->page_size = $this->normalizePageSize($size);
        $this->page = 1;
        $this->generateData();
    }

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
    }

    public function pollRefresh(): void
    {
        $filter = $this->buildFilter();

        $this->exceptionBand = app(ExceptionCounterService::class)->count($filter);
        $this->workspaceSummary = app(WorkspaceSummaryBuilder::class)->build($filter);
    }

    public function gotoPage(int $page): void
    {
        if ($page < 1) {
            $page = 1;
        }
        $this->page = $page;
        $this->generateData();
    }

    protected function generateData(): void
    {
        $filter = $this->buildFilter();

        try {
            $this->rows = app(MonitoringQueryService::class)->paginate($filter);
            $this->exceptionBand = app(ExceptionCounterService::class)->count($filter);
            $this->workspaceSummary = app(WorkspaceSummaryBuilder::class)->build($filter);
        } catch (\Throwable $e) {
            logger()->error('[PELACAKAN_MONITORING] data generation failed', [
                'filter' => $filter->toArray(),
                'error' => $e->getMessage(),
            ]);

            $this->rows = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $filter->page_size);
            $this->exceptionBand = ExceptionBandData::empty();
            $this->workspaceSummary = WorkspaceSummaryData::empty();
        }
    }

    protected function buildFilter(): MonitoringFilter
    {
        return new MonitoringFilter(
            branch_id: $this->branch_id,
            mode: $this->mode ?: null,
            route: $this->route ?: null,
            exception_filter: $this->exception_filter ?: null,
            search: $this->search ?? '',
            group_mode: $this->group_mode ?: 'flat',
            status: $this->normalizeStatus($this->status),
            sort: $this->sort ?: 'exception-first',
            page: $this->page,
            page_size: $this->page_size,
            period: PeriodResolver::normalize($this->period),
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
            'period'            => $this->period,
            'periodOptions'     => PeriodResolver::options(),
            'isOfficeAdmin'     => $isOfficeAdmin,
            'branchId'          => $this->branch_id,
            'branchOptions'     => $isOfficeAdmin ? [] : Branch::query()->orderBy('name')->pluck('name', 'id')->all(),
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
            (! $isOfficeAdmin && $this->branch_id)                      ? 1 : 0,
            ($this->period !== PeriodResolver::default())               ? 1 : 0,
        ])->sum();
    }

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

    private function normalizePageSize(int $size): int
    {
        $allowed = config('monitoring.page_size_options', [25, 50, 100, 200]);

        return in_array($size, $allowed, true) ? $size : (int) config('monitoring.page_size', 50);
    }
}

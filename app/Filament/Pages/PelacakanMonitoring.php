<?php

namespace App\Filament\Pages;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentMode;
use App\Services\Monitoring\ExceptionCounterService;
use App\Services\Monitoring\MonitoringQueryService;
use App\Services\Monitoring\WorkspaceSummaryBuilder;
use App\Support\Monitoring\RouteResolver;
use App\ViewModels\Monitoring\ExceptionBandData;
use App\ViewModels\Monitoring\WorkspaceSummaryData;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PelacakanMonitoring extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Pelacakan & Monitoring';

    protected static ?string $navigationGroup = 'Pengiriman';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.pelacakan-monitoring';

    protected static ?string $title = 'Pelacakan & Monitoring';

    protected ?string $maxContentWidth = 'full';

    // ── Public state (Livewire) ─────────────────────────────────────────────

    public ?int $branch_id = null;

    public ?string $mode = null;

    public ?string $route = null;

    public ?string $exception_filter = null;

    public string $search = '';

    public string $group_mode = 'flat';

    public bool $show_finished = false;

    public string $sort = 'exception-first';

    public int $page = 1;

    // ── Computed data ──────────────────────────────────────────────────────

    public ?LengthAwarePaginator $rows = null;

    public ?ExceptionBandData $exceptionBand = null;

    public ?WorkspaceSummaryData $workspaceSummary = null;

    public function mount(): void
    {
        $user = auth_user();

        if ($user?->isOfficeAdmin()) {
            $this->branch_id = $user->effectiveBranchId();
        }

        $this->route = RouteResolver::default();

        $this->form->fill([
            'branch_id' => $this->branch_id,
            'mode' => $this->mode,
            'route' => $this->route,
            'exception_filter' => $this->exception_filter,
            'search' => $this->search,
            'group_mode' => $this->group_mode,
            'show_finished' => $this->show_finished,
            'sort' => $this->sort,
        ]);

        $this->generateData();
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 6])
                ->schema([
                    Select::make('route')
                        ->label('Route')
                        ->options([
                            'tam' => 'TAM',
                            'all' => 'Semua',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn($state) => $this->updateFilter('route', $state))
                        ->columnSpan(1),

                    Select::make('mode')
                        ->label('Moda')
                        ->placeholder('Semua moda')
                        ->options([
                            ShipmentMode::Sea->value => 'Laut',
                            ShipmentMode::Land->value => 'Darat',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn($state) => $this->updateFilter('mode', $state))
                        ->columnSpan(1),

                    Select::make('exception_filter')
                        ->label('Exception')
                        ->placeholder('Semua')
                        ->options([
                            'delay' => 'Delay',
                            'ng' => 'NG',
                            'hold' => 'Hold',
                            'demurrage' => 'Demurrage',
                            'missing_voyage' => 'Missing Voyage',
                            'pdi_pending' => 'PDI Pending',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn($state) => $this->updateFilter('exception_filter', $state))
                        ->columnSpan(1),

                    ToggleButtons::make('group_mode')
                        ->label('Group')
                        ->options([
                            'flat' => 'Flat',
                            'sppb' => 'SPPB',
                            'voyage' => 'Voyage',
                        ])
                        ->inline()
                        ->reactive()
                        ->afterStateUpdated(fn($state) => $this->updateFilter('group_mode', $state))
                        ->columnSpan(1),

                    Toggle::make('show_finished')
                        ->label('Tampilkan selesai')
                        ->reactive()
                        ->afterStateUpdated(fn($state) => $this->updateFilter('show_finished', (bool) $state))
                        ->columnSpan(1),

                    TextInput::make('search')
                        ->label('Cari')
                        ->placeholder('Cari unit / SPPB / chassis...')
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn($state) => $this->updateFilter('search', $state ?? ''))
                        ->columnSpan(1),
                ]),
        ];
    }

    public function updateFilter(string $field, mixed $value): void
    {
        $this->{$field} = $value;
        $this->page = 1;
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

            $this->rows = new \Illuminate\Pagination\LengthAwarePaginator([], 0, config('monitoring.page_size', 50));
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
            show_finished: $this->show_finished,
            sort: $this->sort ?: 'exception-first',
            page: $this->page,
        );
    }

    protected function getViewData(): array
    {
        return [
            'rows' => $this->rows,
            'exceptionBand' => $this->exceptionBand ?? ExceptionBandData::empty(),
            'workspaceSummary' => $this->workspaceSummary ?? WorkspaceSummaryData::empty(),
            'pollInterval' => config('monitoring.poll_interval', 60),
            'pageSize' => config('monitoring.page_size', 50),
            'exceptionFilter' => $this->exception_filter,
            'groupMode' => $this->group_mode,
        ];
    }
}
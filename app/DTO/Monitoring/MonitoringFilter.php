<?php

namespace App\DTO\Monitoring;

use App\Support\Monitoring\PeriodResolver;

final readonly class MonitoringFilter
{
    public function __construct(
        /** Workspace branch context. See applyBranch() in the query builders. */
        public readonly ?int $branch_id,
        /**
         * Extension point — not applied by the current query builders, where
         * the mode is hard-pinned to 'sea' via MonitoringDomain. Re-enable in
         * query builders and expose in the UI when land mode is added.
         */
        public readonly ?string $mode,
        public readonly ?string $route,
        public readonly ?string $exception_filter,
        public readonly string $search,
        public readonly string $group_mode,
        /**
         * One of 'active' (default, hide finished), 'finished' (finished only),
         * or 'all' (no status restriction). See config('monitoring.status_options').
         */
        public readonly string $status,
        public readonly string $sort,
        public readonly int $page,
        public readonly int $page_size,
        /**
         * Workspace period context, format 'YYYY-MM'. The primary context
         * filter — Search/Exception/Status/Summary all operate within this
         * period, not the whole database.
         */
        public readonly string $period,
    ) {}

    public static function default(?int $branchId = null): self
    {
        return new self(
            branch_id: $branchId,
            mode: null,
            route: config('monitoring.default_route', 'tam'),
            exception_filter: null,
            search: '',
            group_mode: 'flat',
            status: 'active',
            sort: 'exception-first',
            page: 1,
            page_size: config('monitoring.page_size', 50),
            period: PeriodResolver::default(),
        );
    }

    public function cacheKey(): string
    {
        // `mode` is excluded — sea mode is hard-pinned in all query builders,
        // so varying on mode would produce duplicate cache entries.
        return md5(serialize([
            $this->branch_id,
            $this->route,
            $this->status,
            $this->period,
        ]));
    }

    public function toArray(): array
    {
        return [
            'branch_id'        => $this->branch_id,
            'mode'             => $this->mode,
            'route'            => $this->route,
            'exception_filter' => $this->exception_filter,
            'search'           => $this->search,
            'group_mode'       => $this->group_mode,
            'status'           => $this->status,
            'sort'             => $this->sort,
            'page'             => $this->page,
            'page_size'        => $this->page_size,
            'period'           => $this->period,
        ];
    }
}

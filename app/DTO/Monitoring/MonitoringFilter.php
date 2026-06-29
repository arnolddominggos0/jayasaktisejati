<?php

namespace App\DTO\Monitoring;

final readonly class MonitoringFilter
{
    public function __construct(
        public readonly ?int $branch_id,
        /**
         * v2 extension point — not applied by v1 query builders.
         * In v1 the mode is hard-pinned to 'sea' via MonitoringDomain.
         * Re-enable in query builders and expose in the UI when land mode is added.
         * See ADR-009.
         */
        public readonly ?string $mode,
        public readonly ?string $route,
        public readonly ?string $exception_filter,
        public readonly string $search,
        public readonly string $group_mode,
        public readonly bool $show_finished,
        public readonly string $sort,
        public readonly int $page,
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
            show_finished: false,
            sort: 'exception-first',
            page: 1,
        );
    }

    public function cacheKey(): string
    {
        // `mode` is excluded — v1 hard-pins sea mode in all query builders
        // so varying on mode would produce duplicate cache entries. See ADR-009.
        return md5(serialize([
            $this->branch_id,
            $this->route,
            $this->show_finished,
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
            'show_finished'    => $this->show_finished,
            'sort'             => $this->sort,
            'page'             => $this->page,
        ];
    }
}

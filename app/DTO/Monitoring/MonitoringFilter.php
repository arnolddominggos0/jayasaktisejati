<?php

namespace App\DTO\Monitoring;

final readonly class MonitoringFilter
{
    public function __construct(
        public readonly ?int $branch_id,
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
        return md5(serialize([
            $this->branch_id,
            $this->mode,
            $this->route,
            $this->show_finished,
        ]));
    }

    public function toArray(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'mode' => $this->mode,
            'route' => $this->route,
            'exception_filter' => $this->exception_filter,
            'search' => $this->search,
            'group_mode' => $this->group_mode,
            'show_finished' => $this->show_finished,
            'sort' => $this->sort,
            'page' => $this->page,
        ];
    }
}
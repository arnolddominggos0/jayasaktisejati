<?php

namespace App\ViewModels\Monitoring;

use Illuminate\Support\Carbon;

final readonly class WorkspaceSummaryData
{
    public function __construct(
        public readonly int $activeUnits,
        public readonly int $finishedUnits,
        public readonly string $route,
        public readonly string $branch,
        public readonly Carbon $lastRefresh,
        public readonly int $filteredUnits,
    ) {}

    public static function empty(): self
    {
        return new self(
            activeUnits: 0,
            finishedUnits: 0,
            route: '—',
            branch: '—',
            lastRefresh: now(),
            filteredUnits: 0,
        );
    }
}
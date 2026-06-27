<?php

namespace App\ViewModels\Monitoring;

final readonly class UnitTimeline
{
    public function __construct(
        public readonly array $stages,
        public readonly int $completed_count,
        public readonly int $total_count,
    ) {}

    public static function empty(): self
    {
        return new self(
            stages: [],
            completed_count: 0,
            total_count: 0,
        );
    }
}
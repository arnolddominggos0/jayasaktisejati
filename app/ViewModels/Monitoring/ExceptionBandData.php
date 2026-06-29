<?php

namespace App\ViewModels\Monitoring;

final readonly class ExceptionBandData
{
    /**
     * @param array<ExceptionChipData> $chips  Ordered, non-zero chips — ready for Blade iteration.
     */
    public function __construct(
        public readonly int $hold_count,
        public readonly int $ng_count,
        public readonly int $demurrage_count,
        public readonly int $delay_count,
        public readonly int $stuck_count,
        public readonly int $missing_voyage_count,
        public readonly int $total,
        public readonly array $chips,
    ) {}

    public static function empty(): self
    {
        return new self(
            hold_count: 0,
            ng_count: 0,
            demurrage_count: 0,
            delay_count: 0,
            stuck_count: 0,
            missing_voyage_count: 0,
            total: 0,
            chips: [],
        );
    }
}

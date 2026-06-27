<?php

namespace App\ViewModels\Monitoring;

final readonly class ExceptionBandData
{
    public function __construct(
        public readonly int $delay_count,
        public readonly int $ng_count,
        public readonly int $hold_count,
        public readonly int $demurrage_count,
        public readonly int $missing_voyage_count,
        public readonly int $pdi_pending_count,
        public readonly int $total,
    ) {}

    public static function empty(): self
    {
        return new self(
            delay_count: 0,
            ng_count: 0,
            hold_count: 0,
            demurrage_count: 0,
            missing_voyage_count: 0,
            pdi_pending_count: 0,
            total: 0,
        );
    }
}
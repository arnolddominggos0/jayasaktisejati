<?php

namespace App\ViewModels\Monitoring;

final readonly class LeadTimeData
{
    public function __construct(
        public readonly array $stages,
        public readonly string $total_badge,
        public readonly ?string $summary_text,
    ) {}

    public static function empty(): self
    {
        return new self(
            stages: [],
            total_badge: 'Pending',
            summary_text: null,
        );
    }
}
<?php

namespace App\ViewModels\Monitoring;

final readonly class InspectionSummary
{
    public function __construct(
        public readonly array $stages,
        public readonly int $total_stages,
        public readonly int $submitted_stages,
        public readonly int $pending_stages,
        public readonly int $ng_item_count,
        public readonly ?string $overall_gate_decision,
    ) {}

    public static function empty(): self
    {
        return new self(
            stages: [],
            total_stages: 0,
            submitted_stages: 0,
            pending_stages: 0,
            ng_item_count: 0,
            overall_gate_decision: null,
        );
    }
}
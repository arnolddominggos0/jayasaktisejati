<?php

namespace App\ViewModels\Monitoring;

use Illuminate\Support\Carbon;

final readonly class InspectionStageSummary
{
    public function __construct(
        public readonly string $stage,
        public readonly string $stage_label,
        public readonly string $status,
        public readonly ?string $gate_decision,
        public readonly int $ng_count,
        public readonly bool $is_submitted,
        public readonly ?string $summary_1line,
        public readonly ?Carbon $checked_at,
        public readonly ?string $inspector_name,
    ) {}
}
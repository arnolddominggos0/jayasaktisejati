<?php

namespace App\ViewModels\Monitoring;

use App\Enums\TrackStatus;

final readonly class CurrentStageData
{
    public function __construct(
        public readonly TrackStatus $current_stage,
        public readonly ?TrackStatus $next_stage,
        public readonly string $stage_label,
        public readonly int $stage_order,
        public readonly bool $is_held,
        public readonly bool $is_cancelled,
        public readonly bool $is_delivered,
        public readonly string $flow_zone,
    ) {}

    public static function empty(): self
    {
        return new self(
            current_stage: TrackStatus::Pickup,
            next_stage: null,
            stage_label: '—',
            stage_order: 0,
            is_held: false,
            is_cancelled: false,
            is_delivered: false,
            flow_zone: 'pickup',
        );
    }
}
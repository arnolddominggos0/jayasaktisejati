<?php

namespace App\ViewModels\Monitoring;

use App\Enums\TrackStatus;
use Illuminate\Support\Carbon;

final readonly class TimelineStage
{
    public function __construct(
        public readonly TrackStatus $status,
        public readonly string $label,
        public readonly string $icon,
        public readonly string $color_zone,
        public readonly string $state,
        public readonly ?Carbon $tracked_at,
        public readonly ?string $note,
        public readonly ?string $location,
        public readonly ?Carbon $plan_loading_time_at,
        public readonly ?Carbon $plan_closing_time_at,
        public readonly ?Carbon $actual_loading_time_at,
        public readonly ?Carbon $actual_closing_time_at,
    ) {}
}
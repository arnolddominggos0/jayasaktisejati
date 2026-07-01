<?php

namespace App\ViewModels\Monitoring;

final readonly class SiblingUnitData
{
    public function __construct(
        public readonly int $unit_id,
        public readonly ?string $reg_no,
        public readonly ?string $model_no,
        public readonly ?string $color,
        public readonly ?string $container_display,
        public readonly bool $has_ng,
        public readonly ?string $inspection_status,
        public readonly ?string $stage_label,
    ) {}
}
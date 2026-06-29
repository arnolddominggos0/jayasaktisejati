<?php

namespace App\ViewModels\Monitoring;

final readonly class ExceptionChipData
{
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $severity,
        public readonly string $color,
        public readonly int $count,
        public readonly ?string $detail = null,
        public readonly ?string $icon = null,
    ) {}
}

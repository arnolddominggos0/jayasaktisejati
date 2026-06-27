<?php

namespace App\ViewModels\Monitoring;

final readonly class ExceptionChipData
{
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $severity,
        public readonly ?string $detail = null,
        public readonly ?string $icon = null,
        public readonly ?int $count = null,
    ) {}

    public static function empty(string $type): self
    {
        return new self(
            type: $type,
            label: ucfirst(str_replace('_', ' ', $type)),
            severity: 'warning',
        );
    }
}
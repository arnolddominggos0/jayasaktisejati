<?php

namespace App\ViewModels\Monitoring;

final readonly class AgeData
{
    public function __construct(
        public readonly ?int $days,
        public readonly string $label,
        public readonly bool $is_stuck,
        public readonly bool $fallback_used,
    ) {}

    public static function empty(): self
    {
        return new self(
            days: null,
            label: '—',
            is_stuck: false,
            fallback_used: false,
        );
    }
}
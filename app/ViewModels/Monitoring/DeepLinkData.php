<?php

namespace App\ViewModels\Monitoring;

final readonly class DeepLinkData
{
    public function __construct(
        public readonly string $label,
        public readonly string $url,
        public readonly string $icon,
        public readonly ?string $description = null,
    ) {}
}
<?php

namespace App\ViewModels\Monitoring;

final readonly class LeadTimeStageData
{
    public function __construct(
        public readonly string $key,
        public readonly ?int $actual,
        public readonly ?int $limit,
        public readonly string $status,
    ) {}
}
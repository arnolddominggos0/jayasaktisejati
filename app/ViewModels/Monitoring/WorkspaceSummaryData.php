<?php

namespace App\ViewModels\Monitoring;

final readonly class WorkspaceSummaryData
{
    public function __construct(
        public readonly int $total_units,
        public readonly int $active_shipments,
        public readonly int $in_transit_units,
        public readonly int $at_port_units,
        public readonly int $delivered_today,
    ) {}

    public static function empty(): self
    {
        return new self(
            total_units: 0,
            active_shipments: 0,
            in_transit_units: 0,
            at_port_units: 0,
            delivered_today: 0,
        );
    }
}
<?php

namespace App\ViewModels\Monitoring;

use App\Enums\ShipmentMode;

final readonly class MonitoringRowData
{
    public function __construct(
        public readonly int $shipment_id,
        public readonly string $shipment_code,
        public readonly string $doc_number,
        public readonly ?int $unit_id,
        public readonly ?string $unit_reg_no,
        public readonly ?string $unit_model_no,
        public readonly ?string $unit_chassis_no,
        public readonly ?string $unit_color,
        public readonly ?string $container_display,
        public readonly string $customer_name,
        public readonly ?string $branch_name,
        public readonly string $route_label,
        public readonly ShipmentMode $mode,
        public readonly ?string $voyage_no,
        public readonly ?string $vessel_name,
        public readonly CurrentStageData $stage,
        public readonly AgeData $age,
        public readonly int $progress_pct,
        public readonly array $exceptions,
        public readonly ?string $eta_label,
        public readonly ?string $lead_time_summary,
        public readonly bool $is_search_match,
        public readonly bool $is_finished,
        public readonly int $unit_count,
    ) {}

    public static function empty(): self
    {
        return new self(
            shipment_id: 0,
            shipment_code: '',
            doc_number: '',
            unit_id: null,
            unit_reg_no: null,
            unit_model_no: null,
            unit_chassis_no: null,
            unit_color: null,
            container_display: null,
            customer_name: '',
            branch_name: null,
            route_label: '—',
            mode: ShipmentMode::Sea,
            voyage_no: null,
            vessel_name: null,
            stage: CurrentStageData::empty(),
            age: AgeData::empty(),
            progress_pct: 0,
            exceptions: [],
            eta_label: null,
            lead_time_summary: null,
            is_search_match: false,
            is_finished: false,
            unit_count: 0,
        );
    }
}
<?php

namespace App\ViewModels\Monitoring;

use App\Enums\ShipmentMode;

final readonly class UnitDetailData
{
    public function __construct(
        public readonly int $unit_id,
        public readonly string $unit_reg_no,
        public readonly ?string $unit_model_no,
        public readonly ?string $unit_chassis_no,
        public readonly string $unit_color,
        public readonly ?string $container_display,
        public readonly int $shipment_id,
        public readonly string $shipment_code,
        public readonly string $doc_number,
        public readonly string $customer_name,
        public readonly string $route_label,
        public readonly ShipmentMode $mode,
        public readonly CurrentStageData $stage,
        public readonly AgeData $age,
        public readonly int $progress_pct,
        public readonly UnitTimeline $timeline,
        public readonly InspectionSummary $inspection,
        public readonly ?LeadTimeData $lead_time,
        public readonly AdministrativeInfo $admin,
        public readonly array $sibling_units,
        public readonly array $deep_links,
        public readonly array $exceptions,
    ) {}

    public static function empty(): self
    {
        return new self(
            unit_id: 0,
            unit_reg_no: '—',
            unit_model_no: null,
            unit_chassis_no: null,
            unit_color: '—',
            container_display: null,
            shipment_id: 0,
            shipment_code: '—',
            doc_number: '—',
            customer_name: '—',
            route_label: '—',
            mode: ShipmentMode::Sea,
            stage: CurrentStageData::empty(),
            age: AgeData::empty(),
            progress_pct: 0,
            timeline: UnitTimeline::empty(),
            inspection: InspectionSummary::empty(),
            lead_time: null,
            admin: AdministrativeInfo::empty(),
            sibling_units: [],
            deep_links: [],
            exceptions: [],
        );
    }
}
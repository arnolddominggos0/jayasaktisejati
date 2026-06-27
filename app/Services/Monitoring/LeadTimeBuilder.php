<?php

namespace App\Services\Monitoring;

use App\Models\Shipment;
use App\ViewModels\Monitoring\LeadTimeData;

final class LeadTimeBuilder
{
    public function build(Shipment $shipment): ?LeadTimeData
    {
        // TODO Sprint 6.2: implement Manado KPI lead time summary
        return null;
    }
}
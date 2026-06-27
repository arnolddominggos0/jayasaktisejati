<?php

namespace App\Services\Monitoring;

use App\Models\Shipment;
use App\ViewModels\Monitoring\ExceptionChipData;

final class ExceptionEvaluator
{
    public function evaluate(Shipment $shipment): array
    {
        // TODO Sprint 6.2: implement 6 exception type evaluation
        return [];
    }

    public static function types(): array
    {
        return config('monitoring.exception_types', [
            'delay', 'ng', 'hold', 'demurrage', 'missing_voyage', 'pdi_pending',
        ]);
    }
}
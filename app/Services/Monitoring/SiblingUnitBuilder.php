<?php

namespace App\Services\Monitoring;

use App\Models\Shipment;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\ViewModels\Monitoring\SiblingUnitData;

final class SiblingUnitBuilder
{
    public function build(Shipment $shipment, int $selfUnitId): array
    {
        $units = $shipment->relationLoaded('units') ? $shipment->units : collect();

        return $units->map(fn(Unit $unit) => $this->toData($unit))->values()->all();
    }

    private function toData(Unit $unit): SiblingUnitData
    {
        $inspections = $unit->relationLoaded('inspections') ? $unit->inspections : collect();

        $hasNg = false;
        $inspStatus = null;

        if ($inspections->isNotEmpty()) {
            foreach ($inspections as $insp) {
                $items = $insp->relationLoaded('items') ? $insp->items : collect();
                if ($items->where('result', 'ng')->isNotEmpty()) {
                    $hasNg = true;
                }
            }

            $hasFailed = $inspections->contains('status', UnitInspection::STATUS_FAILED);
            $hasPending = $inspections->contains(fn($i) => $i->submitted_at === null);

            $inspStatus = match (true) {
                $hasFailed => 'failed',
                $hasPending => 'pending',
                default => 'passed',
            };
        }

        return new SiblingUnitData(
            unit_id: $unit->id,
            reg_no: $unit->reg_no,
            model_no: $unit->model_no,
            color: $unit->color,
            container_display: $unit->container_display,
            has_ng: $hasNg,
            inspection_status: $inspStatus,
        );
    }
}

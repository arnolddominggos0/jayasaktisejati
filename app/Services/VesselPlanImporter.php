<?php

namespace App\Services;

use App\Models\VesselPlan;
use App\Models\Voyage;
use App\Enums\VesselPlanStatus;

class VesselPlanImporter
{
    public function import(VesselPlan $plan, int $userId): void
    {
        foreach ($plan->items()->orderBy('planned_etd')->get() as $i => $item) {
            Voyage::create([
                'vessel_plan_item_id' => $item->id,
                'vessel_id'           => $item->vessel_id,
                'pol_id'              => $item->pol_id,
                'pod_id'              => $item->pod_id,
                'voyage_no'           => $this->generateVoyageNo($plan, $item, $i + 1),
                'etd'                 => $item->planned_etd,
                'eta'                 => $item->planned_eta,
                'period_month'        => $plan->period_month,
                'is_final'            => true,
                'finalized_at'        => now(),
                'finalized_by'        => $userId,
                'finalized_by_name'   => auth_user()->name,
            ]);
        }

        $plan->update([
            'status' => VesselPlanStatus::Final,
        ]);
    }

    protected function generateVoyageNo(VesselPlan $plan, $item, int $seq): string
    {
        $period = $plan->period_month->format('ym');
        $vcode  = $item->vessel?->short_name ?? 'VSL';

        return strtoupper("{$vcode}-{$period}-" . str_pad($seq, 2, '0', STR_PAD_LEFT));
    }
}

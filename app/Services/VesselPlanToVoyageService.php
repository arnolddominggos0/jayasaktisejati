<?php

namespace App\Services;

use App\Models\VesselPlan;
use App\Models\Voyage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DomainException;

class VesselPlanToVoyageService
{
    public function generate(VesselPlan $plan): void
    {
        if ($plan->status !== \App\Enums\VesselPlanStatus::Sent) {
            throw new DomainException('Vessel Plan belum direview TAM.');
        }

        DB::transaction(function () use ($plan) {
            foreach ($plan->items()->with(['vessel'])->orderBy('planned_etd')->get() as $item) {
                Voyage::create([
                    'vessel_plan_id' => $plan->id,
                    'vessel_id'      => $item->vessel_id,
                    'pol_id'         => $item->pol_id,
                    'pod_id'         => $item->pod_id,
                    'voyage_no'      => null, // memang belum ada
                    'etd'            => $item->planned_etd,
                    'eta'            => $item->planned_eta,
                    'period_month'   => $plan->period_month,
                    'final_source'   => 'vessel_plan',
                    'is_final'       => true,
                    'finalized_at'   => now(),
                    'finalized_by'   => Auth::id(),
                    'finalized_by_name' => Auth::user()->name,
                ]);
            }

            $plan->update([
                'status'       => \App\Enums\VesselPlanStatus::Final,
                'finalized_at' => now(),
                'finalized_by' => Auth::id(),
            ]);
        });
    }
}

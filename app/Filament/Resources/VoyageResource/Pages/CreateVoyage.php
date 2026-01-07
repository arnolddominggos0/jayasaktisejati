<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use App\Models\VesselPlan;
use Filament\Resources\Pages\CreateRecord;

class CreateVoyage extends CreateRecord
{
    protected static string $resource = VoyageResource::class;

    public function mount(): void
    {
        parent::mount();

        $planId = request()->query('vessel_plan_id');

        if (! $planId) {
            return;
        }

        $plan = VesselPlan::with([
            'items' => fn ($q) => $q->orderBy('planned_etd'),
        ])->find($planId);

        if (! $plan || $plan->items->isEmpty()) {
            return;
        }

        $item = $plan->items->first();

        $this->form->fill([
            'vessel_plan_id' => $plan->id,
            'vessel_id'      => $item->vessel_id,
            'pol_id'         => $plan->pol_id,
            'pod_id'         => $plan->pod_id,
            'etd'            => $item->planned_etd,
            'eta'            => $item->planned_eta,
            'period_month'   => $plan->period_month,
        ]);
    }
}

<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Models\Voyage;
use App\Models\VesselPlan;
use Filament\Widgets\Widget;

class VesselPlanDashboard extends Widget
{
    protected static string $view =
        'filament.resources.vessel-plan-resource.widgets.vessel-plan-dashboard';

    protected int|string|array $columnSpan = 'full';

    public ?VesselPlan $record = null;

    protected function getViewData(): array
    {
        if (! $this->record || ! $this->record->isFinal()) {
            return [];
        }

        $voyages = Voyage::where('vessel_plan_id', $this->record->id)->get();

        return [
            'totalVoyages'   => $voyages->count(),
            'totalCargoPlan' => $voyages->sum('cargo_plan'),
            'avgDwelling'    => round($voyages->avg('dwelling_days') ?? 0, 1),
            'delayCount'     => $voyages->where('is_delayed', true)->count(),
        ];
    }
}

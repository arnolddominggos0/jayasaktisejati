<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use Illuminate\Support\Carbon;

class VesselPlanGenerator
{
    public function generateNextMonth(): ?VesselPlan
    {
        $latest = VesselPlan::orderByDesc('period_month')->first();

        $nextMonth = $latest
            ? Carbon::parse($latest->period_month)->addMonth()->startOfMonth()
            : now()->startOfMonth();

        if (VesselPlan::where('period_month', $nextMonth)->exists()) {
            return null;
        }

        return VesselPlan::create([
            'period_month' => $nextMonth,
            'route_code'   => $latest?->route_code ?? 'JKT-BTG',
            'status'       => VesselPlanStatus::Draft,
        ]);
    }
}

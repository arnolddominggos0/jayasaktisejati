<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use Illuminate\Support\Carbon;

class VesselPlanGenerator
{
    public function generateNextMonth(): ?VesselPlan
    {
        $latest = VesselPlan::query()
            ->orderByDesc('period_month')
            ->first();

        $nextMonth = $latest
            ? Carbon::parse($latest->period_month)
                ->addMonth()
                ->startOfMonth()
            : now()->startOfMonth();

        $existing = VesselPlan::query()
            ->whereDate('period_month', $nextMonth)
            ->first();

        if ($existing) {
            return null;
        }

        $defaultPorts = VesselPlan::query()
            ->getModel()
            ->resolveRoutePortIds();

        $plan = VesselPlan::create([
            'period_month' => $nextMonth->toDateString(),

            'route_code'   => $latest?->route_code ?? 'JKT-BTG',

            'pol_id'       => $latest?->pol_id
                ?? $defaultPorts['pol_id']
                ?? null,

            'pod_id'       => $latest?->pod_id
                ?? $defaultPorts['pod_id']
                ?? null,

            'status'       => VesselPlanStatus::Draft,
        ]);

        return $plan;
    }

    public function generateForMonth(Carbon $periodMonth): VesselPlan
    {
        $period = $periodMonth
            ->copy()
            ->startOfMonth();

        $existing = VesselPlan::query()
            ->whereDate('period_month', $period)
            ->first();

        if ($existing) {
            return $existing;
        }

        $plan = VesselPlan::create([
            'period_month' => $period->toDateString(),

            'route_code'   => 'JKT-BTG',

            'status'       => VesselPlanStatus::Draft,
        ]);

        $plan->syncRoutePorts();

        return $plan;
    }
}
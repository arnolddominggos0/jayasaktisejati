<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\Port;
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
            'customer_id'  => VesselPlan::resolveTamCustomer()?->id,

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
            'customer_id' => VesselPlan::resolveTamCustomer()?->id,
            'period_month' => $period->toDateString(),
            'route_code' => 'JKT-BTG',

            'pol_id' => Port::where('code', config('tam.route.pol_code'))->value('id'),
            'pod_id' => Port::where('code', config('tam.route.pod_code'))->value('id'),

            'status' => VesselPlanStatus::Draft,
        ]);

        $plan->syncRoutePorts();

        return $plan;
    }
}

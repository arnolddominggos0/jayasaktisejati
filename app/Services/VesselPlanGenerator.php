<?php

namespace App\Services;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use Illuminate\Support\Carbon;
use DomainException;

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
            'customer_id'  => $latest?->customer_id ?? VesselPlan::resolveTamCustomerId(),
            'pol_id'       => $latest?->pol_id ?? VesselPlan::query()->getModel()->resolveRoutePortIds()['pol_id'] ?? null,
            'pod_id'       => $latest?->pod_id ?? VesselPlan::query()->getModel()->resolveRoutePortIds()['pod_id'] ?? null,
            'status'       => VesselPlanStatus::Draft,
        ]);
    }

    public function generateForMonth(Carbon $periodMonth): VesselPlan
    {
        $period = $periodMonth->copy()->startOfMonth();

        $existing = VesselPlan::query()->whereDate('period_month', $period)->first();
        if ($existing) {
            return $existing;
        }

        $customerId = VesselPlan::resolveTamCustomerId();
        if (! $customerId) {
            throw new DomainException('Customer TAM (Toyota Astra Motor) belum tersedia.');
        }

        $plan = VesselPlan::create([
            'period_month' => $period->toDateString(),
            'route_code' => 'JKT-BTG',
            'customer_id' => $customerId,
            'status' => VesselPlanStatus::Draft,
        ]);

        $plan->syncRoutePorts();

        return $plan;
    }
}

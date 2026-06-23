<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Models\VesselPlan;
use App\Supports\RouteCode;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateVesselPlan extends CreateRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['period_month'] = Carbon::parse($data['period_month'])
            ->startOfMonth()
            ->toDateString();
        $data['customer_id'] = $data['customer_id'] ?? VesselPlan::resolveTamCustomerId();
        $data['route_code'] = $data['route_code'] ?? RouteCode::default();

        $draft = new VesselPlan($data);
        $ports = $draft->resolveRoutePortIds();
        $data['pol_id'] = $data['pol_id'] ?? $ports['pol_id'];
        $data['pod_id'] = $data['pod_id'] ?? $ports['pod_id'];

        return $data;
    }
}

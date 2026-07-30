<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Models\VesselPlan;
use App\Supports\RouteCode;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateVesselPlan extends CreateRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['period_month'] = Carbon::parse($data['period_month'])
            ->startOfMonth()
            ->toDateString();

        $data['route_code'] = $data['route_code']
            ?? RouteCode::default();

        if (
            VesselPlan::query()
            ->whereDate('period_month', $data['period_month'])
            ->where('route_code', $data['route_code'])
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'period_month' => 'Vessel Plan untuk periode dan rute tersebut sudah ada.',
            ]);
        }

        $draft = new VesselPlan($data);

        $ports = $draft->resolveRoutePortIds();

        $data['pol_id'] = $ports['pol_id'];
        $data['pod_id'] = $ports['pod_id'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}

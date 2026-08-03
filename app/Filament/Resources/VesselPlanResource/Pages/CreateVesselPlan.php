<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Models\Port;
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

        $polCode = Port::query()->whereKey($data['pol_id'] ?? null)->value('code');
        $podCode = Port::query()->whereKey($data['pod_id'] ?? null)->value('code');

        $routeCode = ($polCode && $podCode)
            ? RouteCode::businessFromPortCodes($polCode, $podCode)
            : null;

        if (! $routeCode) {
            throw ValidationException::withMessages([
                'pod_id' => 'Kombinasi POL dan POD tersebut belum terdaftar sebagai rute layanan.',
            ]);
        }

        $data['route_code'] = $routeCode;

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

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}

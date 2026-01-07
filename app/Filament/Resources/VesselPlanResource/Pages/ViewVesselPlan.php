<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use Filament\Resources\Pages\ViewRecord;

class ViewVesselPlan extends ViewRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderWidgets(): array
    {
        return [VesselPlanAnalysis::class];
    }
}

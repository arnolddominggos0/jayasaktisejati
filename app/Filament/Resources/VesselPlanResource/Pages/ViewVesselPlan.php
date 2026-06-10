<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanReviewHistory;
use Filament\Resources\Pages\ViewRecord;

class ViewVesselPlan extends ViewRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderWidgets(): array
    {
        return [VesselPlanAnalysis::class];
    }

    public function getHeaderWidgetsColumns(): int
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [VesselPlanReviewHistory::class];
    }

    public function getFooterWidgetsColumns(): int
    {
        return 1;
    }
}

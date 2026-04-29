<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Models\VesselPlan;
use App\Supports\MonthParam;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVesselPlans extends ListRecords
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderActions(): array
    {
        $month = MonthParam::resolve(request('month'));

        return [
            Action::make('generate')
                ->label("Generate Vessel Plan {$month['label']}")
                ->icon('heroicon-o-plus')
                ->visible(fn () =>
                    ! VesselPlan::where('period_month', $month['start'])->exists()
                )
                ->action(fn () =>
                    VesselPlan::generateForMonth($month['start'])
                ),
        ];
    }
}

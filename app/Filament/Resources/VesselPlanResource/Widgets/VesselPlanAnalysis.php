<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Models\VesselPlan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VesselPlanAnalysis extends StatsOverviewWidget
{
    public ?VesselPlan $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $analysis = $this->record->analyze();

        $maxGap = $analysis['max_gap'] ?? 0;
        $gapLimit = $analysis['gap_limit'] ?? 6;
        $gapOk = $analysis['gap_ok'] ?? false;

        return [
            Stat::make('Jadwal', $this->record->items->count()),

            Stat::make('Rencana Muatan', $this->record->items->sum('cargo_plan').' Unit'),

            Stat::make('ETD Gap', $maxGap.' Hari')
                ->description('Target SOP ≤ '.$gapLimit.' Hari')
                ->descriptionColor($gapOk ? 'gray' : ($maxGap <= 10 ? 'warning' : 'danger')),
        ];
    }
}

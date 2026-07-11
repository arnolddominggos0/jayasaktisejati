<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Models\VesselPlan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

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
        $unitSuffix = fn(string $unit) => '<span class="text-base font-normal text-gray-500"> ' . $unit . '</span>';

        return [
            Stat::make('Jadwal', $this->record->items->count())
                ->description('Jumlah jadwal yang direncanakan')
                ->descriptionColor('gray'),

            Stat::make(
                'Rencana Muatan',
                new HtmlString(
                    $this->record->items->sum('cargo_plan') . $unitSuffix('Unit')
                )
            )
                ->description('Total rencana muatan')
                ->descriptionColor('gray'),

            Stat::make(
                'ETD Gap',
                new HtmlString(
                    $maxGap . $unitSuffix('Hari')
                )
            )
                ->description("Target ≤ {$gapLimit} Hari")
                ->descriptionColor('gray'),

            Stat::make('Gap OK', $gapOk ? 'Ya' : 'Tidak')
                ->description($gapOk ? 'Semua jadwal sesuai target' : 'Ada jadwal yang melebihi target')
                ->descriptionColor($gapOk ? 'success' : 'danger'),
        ];
    }
}

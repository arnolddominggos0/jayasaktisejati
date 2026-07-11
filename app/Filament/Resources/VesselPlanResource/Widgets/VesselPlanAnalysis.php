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

        // Unit di dalam value ("Unit"/"Hari") pakai style secondary bawaan
        // Filament (text-base font-normal text-gray-500, sama seperti
        // sub-teks pada description) supaya angka tetap fokus utama —
        // bukan seluruh value jadi bold rata dengan ukuran yang sama.
        $unitSuffix = fn (string $unit) => '<span class="text-base font-normal text-gray-500"> '.$unit.'</span>';

        return [
            Stat::make('Jadwal', $this->record->items->count()),

            Stat::make('Rencana Muatan', new HtmlString(
                $this->record->items->sum('cargo_plan').$unitSuffix('Unit')
            )),

            Stat::make('ETD Gap', new HtmlString($maxGap.$unitSuffix('Hari')))
                ->description('Target SOP ≤ '.$gapLimit.' Hari')
                ->descriptionColor($gapOk ? 'gray' : ($maxGap <= 10 ? 'warning' : 'danger')),
        ];
    }
}

<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\VesselPlan;

class VesselPlanAnalysis extends Widget
{
    protected static string $view =
        'filament.resources.vessel-plan-resource.widgets.vessel-plan-analysis';

    protected int|string|array $columnSpan = 'full';

    public ?VesselPlan $record = null;

    protected function getViewData(): array
    {
        $analysis = $this->record->analyze();

        return [
            'total'       => $this->record->items()->count(),
            'maxGap'      => $analysis['max_gap'],
            'idealGap'    => 6,
            'statusLabel' => $analysis['ok'] ? 'SESUAI SOP' : 'MELANGGAR SOP',
            'statusColor' => $analysis['ok'] ? 'text-green-600' : 'text-red-600',
            'statusBg'    => $analysis['ok'] ? 'bg-green-50' : 'bg-red-50',
        ];
    }
}

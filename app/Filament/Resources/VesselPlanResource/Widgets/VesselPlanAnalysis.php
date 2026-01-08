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
        if (! $this->record) {
            return [];
        }

        $total = $this->record->items()->count();
        $sop   = $this->record->sopStatus();

        return [
            'total'    => $total,
            'maxGap'   => $total > 0 ? $this->record->maxEtdGap() : 0,
            'idealGap' => 6,

            'statusLabel' => $sop['label'],

            'statusColor' => match ($sop['color']) {
                'success' => 'text-green-600',
                'danger'  => 'text-red-600',
                default   => 'text-gray-600',
            },

            'statusBg' => match ($sop['color']) {
                'success' => 'bg-green-50',
                'danger'  => 'bg-red-50',
                default   => 'bg-gray-50',
            },
        ];
    }
}

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

        $analysis = $this->record->analyze();
        $sop      = $this->record->sopStatus();

        return [
            'total'          => $analysis['schedule_count'] ?? 0,
            'sailingAvg'     => $analysis['sailing_avg']    ?? 0,
            'maxGap'         => $analysis['max_gap']        ?? 0,
            'idealGap'       => $analysis['gap_limit']      ?? 6,
            'gapOk'          => $analysis['gap_ok']         ?? false,
            'violations'     => $analysis['violations']     ?? [],
            'riskLevel'      => $analysis['risk_level']     ?? 'valid',
            'violationCount' => count($analysis['violations'] ?? []),
            'statusLabel'    => $sop['label'],
            // Shade 700: chip 11px di atas bg-*-50 butuh >=4.5:1 (WCAG AA);
            // shade 600 hanya ~3.1:1 pada ukuran sekecil ini.
            'statusColor' => match ($sop['color']) {
                'success' => 'text-green-700',
                'warning' => 'text-amber-700',
                'danger'  => 'text-red-700',
                default   => 'text-gray-600',
            },
            'statusBg' => match ($sop['color']) {
                'success' => 'bg-green-50',
                'warning' => 'bg-amber-50',
                'danger'  => 'bg-red-50',
                default   => 'bg-gray-50',
            },
            'statusBorder' => match ($sop['color']) {
                'success' => 'border-green-200',
                'warning' => 'border-amber-200',
                'danger'  => 'border-red-200',
                default   => 'border-gray-200',
            },
        ];
    }
}

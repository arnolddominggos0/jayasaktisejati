<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Models\VesselPlan;
use Filament\Widgets\Widget;

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
        $sop = $this->record->sopStatus();

        // Sprint 14.3 — Health Strip: hanya Max ETD Gap + Risiko.
        // Jumlah Jadwal sudah tampil di Hero meta; Avg Sailing adalah metrik
        // analitis dan tetap tersedia di tab Review Jadwal.
        return [
            'maxGap' => $analysis['max_gap'] ?? 0,
            'idealGap' => $analysis['gap_limit'] ?? 6,
            'gapOk' => $analysis['gap_ok'] ?? false,
            'violations' => $analysis['violations'] ?? [],
            'riskLevel' => $analysis['risk_level'] ?? 'valid',
            'violationCount' => count($analysis['violations'] ?? []),
            'statusLabel' => $sop['label'],
            // Subtitle pendek untuk status card — versi ringkas dari
            // violations (yang tetap tampil lengkap di baris detail).
            'statusSub' => match ($analysis['risk_level'] ?? 'valid') {
                'warning' => 'ETD Gap melewati target',
                'critical' => 'ETD Gap sangat tinggi',
                default => 'Dalam batas SOP',
            },
            // Sprint 14.3A — verdict tipografis (bukan chip berlatar):
            // shade 700 di atas surface muted tetap lolos WCAG AA.
            'statusColor' => match ($sop['color']) {
                'success' => 'text-green-700',
                'warning' => 'text-amber-700',
                'danger' => 'text-red-700',
                default => 'text-gray-600',
            },
        ];
    }
}

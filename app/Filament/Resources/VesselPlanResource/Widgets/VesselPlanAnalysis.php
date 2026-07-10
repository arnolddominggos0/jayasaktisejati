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

        $riskLevel = $analysis['risk_level'] ?? 'valid';
        $isEmpty = ($analysis['schedule_count'] ?? 0) === 0;

        // Label verdict sengaja tidak memakai kata "Revisi" — istilah itu sudah
        // dipakai VesselPlanStatus::Revision untuk konsep lain (TAM meminta
        // revisi plan), beda arti dari "ETD Gap melebihi SOP".
        $gapWarnings = $analysis['gap_warnings'] ?? [];
        $criticalCount = count(array_filter($gapWarnings, fn ($w) => $w['severity'] === 'critical'));
        $warningCount = count($gapWarnings) - $criticalCount;

        [$statusColor, $verdictIcon, $statusLabel, $statusSub] = match (true) {
            $isEmpty => ['text-gray-600', '○', 'Belum Ada Jadwal', 'Tambahkan rencana kapal untuk memulai.'],
            $riskLevel === 'critical' => ['text-red-700', '✕', 'Perlu Perhatian Segera', $criticalCount.' jadwal ETD Gap sangat tinggi (>10 hari)'],
            $riskLevel === 'warning' => ['text-amber-700', '⚠', 'Perlu Ditinjau', $warningCount.' jadwal ETD Gap melewati target SOP'],
            default => ['text-green-700', '✓', 'Siap Dikirim', 'Semua ETD Gap masih dalam SOP'],
        };

        // Rencana Muatan dijumlah dari relasi items yang sudah dimuat (juga
        // dipakai analyze()) — agregasi presentasi, bukan query baru. Avg
        // Sailing sengaja tidak dikembalikan ke sini; itu metrik analitis
        // yang rumahnya di tab Review Jadwal, bukan ringkasan keputusan.
        return [
            'cargoTotal' => $this->record->items->sum('cargo_plan'),
            'maxGap' => $analysis['max_gap'] ?? 0,
            'idealGap' => $analysis['gap_limit'] ?? 6,
            'gapOk' => $analysis['gap_ok'] ?? false,
            'violations' => $analysis['violations'] ?? [],
            'riskLevel' => $riskLevel,
            'violationCount' => count($analysis['violations'] ?? []),
            'verdictIcon' => $verdictIcon,
            'statusLabel' => $statusLabel,
            'statusSub' => $statusSub,
            'statusColor' => $statusColor,
        ];
    }
}

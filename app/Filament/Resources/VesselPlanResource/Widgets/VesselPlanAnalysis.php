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

        // Sprint 14.4 — subtitle verdict dari gap_warnings yang SUDAH dihitung
        // analyzer (severity per vessel), bukan angka baru. Tidak memakai kata
        // "Revisi" — sudah dipakai VesselPlanStatus::Revision untuk konsep lain
        // (TAM meminta revisi plan), beda arti dari "ETD Gap melebihi SOP".
        $gapWarnings = $analysis['gap_warnings'] ?? [];
        $criticalCount = count(array_filter($gapWarnings, fn ($w) => $w['severity'] === 'critical'));
        $warningCount = count($gapWarnings) - $criticalCount;

        [$statusColor, $verdictIcon, $statusLabel, $statusSub] = match (true) {
            $isEmpty => ['text-gray-600', '○', 'Belum Ada Jadwal', 'Tambahkan rencana kapal untuk memulai.'],
            $riskLevel === 'critical' => ['text-red-700', '✕', 'Perlu Perhatian Segera', $criticalCount.' jadwal ETD Gap sangat tinggi (>10 hari)'],
            $riskLevel === 'warning' => ['text-amber-700', '⚠', 'Perlu Ditinjau', $warningCount.' jadwal ETD Gap melewati target SOP'],
            default => ['text-green-700', '✓', 'Siap Dikirim', 'Semua ETD Gap masih dalam SOP'],
        };

        // Sprint 14.4 — Decision Summary: maks. 3 blok (Rencana Muatan, ETD
        // Gap, Verdict). Rencana Muatan dijumlah dari relasi items yang SUDAH
        // dimuat (dipakai analyze() juga) — agregasi presentasi, bukan query
        // baru. Jumlah Jadwal & Avg Sailing tetap tidak dikembalikan ke sini
        // (lihat audit Sprint 14.2 & 14.3) — Avg Sailing tetap rumah di tab
        // Review Jadwal.
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

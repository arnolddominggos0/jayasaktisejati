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
        $draftSnapshot = $this->record->draftSnapshot();
        $finalSnapshot = $this->record->finalSnapshot();
        $draftPayload = $draftSnapshot?->kpi_payload ?? null;
        $finalPayload = $finalSnapshot?->kpi_payload ?? null;
        $sop = $this->record->sopStatus();
        $draftPanel = $this->getDraftPanelMeta();
        $finalPanel = $this->getFinalPanelMeta();

        return [
            'total'    => $analysis['schedule_count'] ?? 0,
            'sailingAvg' => $analysis['sailing_avg'] ?? 0,
            'maxGap'   => $analysis['max_gap'] ?? 0,
            'idealGap' => $analysis['gap_limit'] ?? 6,
            'gapOk' => $analysis['gap_ok'] ?? false,
            'violations' => $analysis['violations'] ?? [],
            'draftPayload' => $draftPayload,
            'finalPayload' => $finalPayload,
            'draftPanelTitle' => $draftPanel['title'],
            'draftPanelCaption' => $draftPanel['caption'],
            'finalPanelTitle' => $finalPanel['title'],
            'finalPanelCaption' => $finalPanel['caption'],

            'statusLabel'    => $sop['label'],
            'statusReason'   => $sop['reason'] ?? '',
            'riskLevel'      => $analysis['risk_level'] ?? 'valid',
            'violationCount' => count($analysis['violations'] ?? []),
            'statusColor' => match ($sop['color']) {
                'success' => 'text-green-600',
                'warning' => 'text-amber-600',
                'danger'  => 'text-red-600',
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

    protected function getDraftPanelMeta(): array
    {
        $snapshot = $this->record?->draftSnapshot();

        if (! $snapshot) {
            return [
                'title' => 'Snapshot Draft',
                'caption' => 'Belum ada snapshot draft yang pernah dikirim.',
            ];
        }

        $title = match (true) {
            $this->record?->isDraft() => 'Snapshot Draft Terakhir',
            $this->record?->isSent() => 'Draft Terkirim',
            $this->record?->isFinal() => 'Draft Yang Disetujui',
            default => 'Snapshot Draft',
        };

        return [
            'title' => $title,
            'caption' => 'Tersimpan pada ' . $snapshot->created_at?->format('d M Y H:i'),
        ];
    }

    protected function getFinalPanelMeta(): array
    {
        $snapshot = $this->record?->finalSnapshot();

        if (! $snapshot) {
            return [
                'title' => 'Snapshot Final',
                'caption' => 'Belum ada snapshot final.',
            ];
        }

        return [
            'title' => 'Snapshot Final',
            'caption' => 'Disetujui pada ' . $snapshot->created_at?->format('d M Y H:i'),
        ];
    }
}
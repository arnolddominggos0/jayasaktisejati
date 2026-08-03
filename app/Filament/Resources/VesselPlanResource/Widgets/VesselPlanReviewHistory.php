<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Enums\VesselPlanStatus;
use App\Models\VesselPlan;
use Filament\Widgets\Widget;

class VesselPlanReviewHistory extends Widget
{
    protected static string $view =
        'filament.resources.vessel-plan-resource.widgets.vessel-plan-review-history';

    protected int|string|array $columnSpan = 'full';

    public ?VesselPlan $record = null;

    protected function getViewData(): array
    {
        if (! $this->record) {
            return ['entries' => [], 'hideWhenEmpty' => true];
        }

        $entries = $this->record->reviews()
            ->with('actor:id,name')
            ->get()
            ->map(function ($review) {
                return [
                    'action' => $this->formatActionLabel($review->action),
                    'note' => $review->note,
                    // Alasan revisi berasal dari customer; event lain netral.
                    'note_label' => $review->action === VesselPlan::REVIEW_ACTION_REVISION_REQUESTED
                        ? 'Catatan Customer'
                        : 'Ringkasan',
                    'actor' => $review->actor?->name ?? 'System',
                    'acted_at' => $review->acted_at?->format('d M Y H:i'),
                    'badge_color' => $this->resolveBadgeColor($review->action),
                    'snapshot' => $this->buildSnapshot($review->meta ?? []),
                ];
            })
            ->all();

        // Draft belum punya apa pun untuk dicatat — sembunyikan seluruh
        // widget alih-alih menampilkan kotak kosong "Belum ada riwayat."
        return [
            'entries' => $entries,
            'hideWhenEmpty' => $this->record->isDraft() && $entries === [],
        ];
    }

    protected function buildSnapshot(array $meta): array
    {
        $snapshot = [];

        if (! empty($meta['status'])) {
            $status = VesselPlanStatus::tryFrom($meta['status']);
            $snapshot[] = [
                'label' => 'Status Plan',
                'value' => $status?->label() ?? $meta['status'],
            ];
        }

        if (array_key_exists('voyage_count', $meta)) {
            $snapshot[] = [
                'label' => 'Jumlah Jadwal',
                'value' => (string) $meta['voyage_count'],
            ];
        }

        return $snapshot;
    }

    protected function formatActionLabel(string $action): string
    {
        return match ($action) {
            VesselPlan::REVIEW_ACTION_DRAFT_SUBMITTED    => 'Draft dikirim ke customer',
            VesselPlan::REVIEW_ACTION_REVISION_REQUESTED => 'Customer meminta revisi',
            VesselPlan::REVIEW_ACTION_APPROVED           => 'Customer menyetujui draft',
            default => str_replace('_', ' ', ucfirst($action)),
        };
    }

    protected function resolveBadgeColor(string $action): string
    {
        return match ($action) {
            VesselPlan::REVIEW_ACTION_DRAFT_SUBMITTED => 'bg-blue-50 text-blue-700 border-blue-200',
            VesselPlan::REVIEW_ACTION_REVISION_REQUESTED => 'bg-amber-50 text-amber-700 border-amber-200',
            VesselPlan::REVIEW_ACTION_APPROVED => 'bg-green-50 text-green-700 border-green-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        };
    }
}

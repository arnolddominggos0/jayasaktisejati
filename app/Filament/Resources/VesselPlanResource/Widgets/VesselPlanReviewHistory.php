<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

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
            return ['entries' => []];
        }

        $entries = $this->record->reviews()
            ->with('actor:id,name')
            ->get()
            ->map(function ($review) {
                return [
                    'action' => $this->formatActionLabel($review->action),
                    'note' => $review->note,
                    'actor' => $review->actor?->name ?? 'System',
                    'acted_at' => $review->acted_at?->format('d M Y H:i'),
                    'badge_color' => $this->resolveBadgeColor($review->action),
                    'meta' => $review->meta ?? [],
                ];
            })
            ->all();

        return ['entries' => $entries];
    }

    protected function formatActionLabel(string $action): string
    {
        return match ($action) {
            VesselPlan::REVIEW_ACTION_DRAFT_SUBMITTED => 'Draft Dikirim',
            VesselPlan::REVIEW_ACTION_REVISION_REQUESTED => 'Revisi Diminta',
            VesselPlan::REVIEW_ACTION_APPROVED => 'Final Disetujui',
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

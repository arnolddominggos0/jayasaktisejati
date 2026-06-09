<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Filament\FC\Widgets\FcTodayBriefingSummary;
use App\Models\BriefingSession;
use App\Models\Depot;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBriefingSessions extends ListRecords
{
    protected static string $resource = BriefingSessionResource::class;

    // -------------------------------------------------------------------------
    // Header widget — today's briefing summary card
    // -------------------------------------------------------------------------

    protected function getHeaderWidgets(): array
    {
        return [FcTodayBriefingSummary::class];
    }

    // -------------------------------------------------------------------------
    // Header actions
    // -------------------------------------------------------------------------

    protected function getHeaderActions(): array
    {
        return [
            // "Buat Briefing Hari Ini" — only visible when no briefing exists today.
            // When today already has a briefing, this button disappears.
            // The summary widget above handles the "already exists" state visually.
            CreateAction::make()
                ->label('Buat Briefing Hari Ini')
                ->icon('heroicon-m-plus-circle')
                ->visible(fn () => ! $this->todayBriefingExists()),
        ];
    }

    // -------------------------------------------------------------------------
    // Tabs
    // -------------------------------------------------------------------------

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => static::getModel()::where('depot_id', $this->getScopedDepotId())->count()),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', now()->toDateString()))
                ->badge(fn () => static::getModel()::whereDate('date', now()->toDateString())->where('depot_id', $this->getScopedDepotId())->count())
                ->icon('heroicon-o-calendar'),

            'in_progress' => Tab::make('Sedang Berjalan')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('mp_check_status', [
                    MPCheckStatus::OnCheck->value,
                    MPCheckStatus::WaitingAction->value,
                    MPCheckStatus::Draft->value,
                ]))
                ->badge(fn () => static::getModel()::whereIn('mp_check_status', [
                    MPCheckStatus::OnCheck->value,
                    MPCheckStatus::WaitingAction->value,
                    MPCheckStatus::Draft->value,
                ])->where('depot_id', $this->getScopedDepotId())->count())
                ->icon('heroicon-o-clock'),

            'cleared' => Tab::make('Cleared')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('mp_check_status', MPCheckStatus::Cleared->value))
                ->badge(fn () => static::getModel()::where('mp_check_status', MPCheckStatus::Cleared->value)->where('depot_id', $this->getScopedDepotId())->count())
                ->icon('heroicon-o-check-circle'),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * True when a briefing already exists for today + this depot.
     * Used to conditionally show/hide the "Buat Briefing Hari Ini" button.
     */
    protected function todayBriefingExists(): bool
    {
        $depotId = $this->getScopedDepotId();

        if (! $depotId) {
            return false;
        }

        return BriefingSession::whereDate('date', today())
            ->where('depot_id', $depotId)
            ->exists();
    }

    /**
     * Resolves the depot the current FC is scoped to.
     * Mirrors ShipmentOwnership / BriefingSessionResource query logic.
     */
    protected function getScopedDepotId(): ?int
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return null;
        }

        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        if ($user->scope_unit_type === 'depot' && $user->scope_unit_id) {
            return (int) $user->scope_unit_id;
        }

        $raw = Depot::where('coordinator_user_id', $user->id)->value('id');

        return $raw ? (int) $raw : null;
    }
}

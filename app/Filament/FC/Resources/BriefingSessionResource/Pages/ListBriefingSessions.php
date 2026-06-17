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

    protected function getHeaderWidgets(): array
    {
        return [
            FcTodayBriefingSummary::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Manual create — for legacy / override scenarios.
            // Normal flow: BriefingSession is auto-created by Shipment::sendToFc().
            // Hidden when today's briefing for this depot already exists.
            CreateAction::make()
                ->label('Buat Briefing Manual')
                ->icon('heroicon-m-plus-circle')
                ->visible(fn () => $this->todayBriefingMissing()),
        ];
    }

    protected function todayBriefingMissing(): bool
    {
        $depotId = $this->getScopedDepotId();

        if (! $depotId) {
            return true; // no depot scope — let form validation handle duplicates
        }

        return ! BriefingSession::whereDate('date', today())
            ->where('depot_id', $depotId)
            ->exists();
    }

    public function getTabs(): array
    {
        $depotId = $this->getScopedDepotId();

        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => BriefingSession::when($depotId, fn ($q) => $q->where('depot_id', $depotId))->count()),

            'needs_check' => Tab::make('Perlu MP Check')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('mp_check_status', [
                    MPCheckStatus::Draft->value,
                    MPCheckStatus::OnCheck->value,
                    MPCheckStatus::WaitingAction->value,
                ]))
                ->badge(fn () => BriefingSession::whereIn('mp_check_status', [
                    MPCheckStatus::Draft->value,
                    MPCheckStatus::OnCheck->value,
                    MPCheckStatus::WaitingAction->value,
                ])
                    ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
                    ->count())
                ->icon('heroicon-o-clock'),

            'cleared' => Tab::make('Cleared')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('mp_check_status', MPCheckStatus::Cleared->value))
                ->badge(fn () => BriefingSession::where('mp_check_status', MPCheckStatus::Cleared->value)
                    ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
                    ->count())
                ->icon('heroicon-o-check-circle'),

            'failed' => Tab::make('Gagal / Ditangguhkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('mp_check_status', [
                    MPCheckStatus::Failed->value,
                ]))
                ->badge(fn () => BriefingSession::whereIn('mp_check_status', [
                    MPCheckStatus::Failed->value,
                ])->when($depotId, fn ($q) => $q->where('depot_id', $depotId))->count())
                ->icon('heroicon-o-x-circle')
                ->badgeColor('danger'),
        ];
    }

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

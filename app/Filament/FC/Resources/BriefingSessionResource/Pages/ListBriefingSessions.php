<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBriefingSessions extends ListRecords
{
    protected static string $resource = BriefingSessionResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => static::getModel()::where('depot_id', $this->getScopedDepotId())->count()),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', now()->toDateString()))
                ->badge(fn () => static::getModel()::whereDate('date', now()->toDateString())->where('depot_id', $this->getScopedDepotId())->count())
                ->icon('heroicon-o-calendar'),

            'needs_approval' => Tab::make('Perlu Approve')
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

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('mp_check_status', MPCheckStatus::Approved->value))
                ->badge(fn () => static::getModel()::where('mp_check_status', MPCheckStatus::Approved->value)->where('depot_id', $this->getScopedDepotId())->count())
                ->icon('heroicon-o-check-circle'),
        ];
    }

    protected function getScopedDepotId(): ?int
    {
        $user = Filament::auth()->user();

        return $user?->scope_unit_type === 'depot'
            ? $user->scope_unit_id
            : Depot::where('coordinator_user_id', $user?->id)->value('id');
    }
}

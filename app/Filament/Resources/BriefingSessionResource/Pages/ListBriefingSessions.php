<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Filament\Resources\BriefingSessionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBriefingSessions extends ListRecords
{
    protected static string $resource = BriefingSessionResource::class;

    // Super admin tidak bisa membuat sesi — FC yang bertugas input harian.
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->icon('heroicon-m-list-bullet'),

            'perlu_review' => Tab::make('Perlu Review')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('approved_at')),

            'sudah_approve' => Tab::make('Sudah Disetujui')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('approved_at')),
        ];
    }
}

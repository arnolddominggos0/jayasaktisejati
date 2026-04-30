<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use App\Models\Depot;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\ListRecords;

class ListLoadingSessions extends ListRecords
{
    protected static string $resource = LoadingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Sesi Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getSubNavigation(): array
    {
        return [
            NavigationItem::make('Semua Sesi')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-o-list-bullet'),
            NavigationItem::make('Sedang Berjalan')
                ->url(static::getResource()::getUrl('index', ['tableFilters' => ['status' => ['values' => ['in_progress']]]]))
                ->icon('heroicon-o-play'),
            NavigationItem::make('Hari Ini')
                ->url(static::getResource()::getUrl('index', ['tableFilters' => ['today' => ['isActive' => true]]]))
                ->icon('heroicon-o-calendar'),
            NavigationItem::make('Isu Kritis')
                ->url(static::getResource()::getUrl('index', ['tableFilters' => ['has_critical' => ['isActive' => true]]]))
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Widgets\RecentShipmentActivities;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Layout as FiltersLayout;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn() => $this->dispatch('export-shipments')), // gantilah dengan implementasi exportmu
            Actions\CreateAction::make()
                ->label('Buat Permintaan')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ShipmentStatus::class,            // span 8/12
            RecentShipmentActivities::class, // span 4/12
        ];
    }

    /** Grid 12 kolom untuk widget */
    public function getHeaderWidgetsColumns(): int | array
    {
        return 12;
    }

    // protected function getTableFiltersLayout(): ?string
    // {
    //     return FiltersLayout::AboveContent;
    // }


    protected function getTableFiltersFormColumns(): int
    {
        return 4;
    }

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make()
                ->icon('heroicon-o-queue-list'),

            'Laut' => Tab::make()
                ->modifyQueryUsing(fn(Builder $q) => $q->where('mode', 'sea'))
                ->icon('heroicon-o-cog-8-tooth'),

            'Darat' => Tab::make()
                ->modifyQueryUsing(fn(Builder $q) => $q->where('mode', 'land'))
                ->icon('heroicon-o-truck'),

            'Draft' => Tab::make()
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', ShipmentStatus::Draft->value))
                ->badgeColor('gray'),

            'Proses' => Tab::make()
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', ShipmentStatus::Pending->value))
                ->badgeColor('warning'),

            'Sedang Berjalan' => Tab::make()
                ->modifyQueryUsing(fn(Builder $q) => $q->whereIn('status', [
                    ShipmentStatus::Pickup->value,
                    ShipmentStatus::Transit->value,
                ]))
                ->badgeColor('info'),

            'Selesai/Bulan Ini' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $q) => $q
                        ->where('status', ShipmentStatus::Delivered->value)
                        ->whereMonth('updated_at', now()->month)
                        ->whereYear('updated_at', now()->year)
                )
                ->badgeColor('success'),
        ];
    }
}

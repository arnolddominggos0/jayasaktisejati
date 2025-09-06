<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use App\Models\Shipment;
use App\Enums\ShipmentStatus;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentShipmentActivities extends BaseWidget
{
    protected int|string|array $columnSpan = 4;

    protected function getTableQuery(): Builder
    {
        return Shipment::query()->latest('updated_at')->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('updated_at')
                ->since()
                ->label('Waktu')
                ->sortable(),

            Tables\Columns\TextColumn::make('code')
                ->badge()
                ->label('Kode')
                ->copyable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->getStateUsing(fn ($record) =>
                    $record->status instanceof ShipmentStatus
                        ? $record->status->value
                        : (string) $record->status
                )
                ->colors([
                    'gray'    => ['draft'],
                    'warning' => ['pending','hold'],
                    'info'    => ['pickup','transit'],
                    'success' => ['delivered'],
                    'danger'  => ['cancelled'],
                ])
                ->formatStateUsing(fn (?string $state) => match ($state) {
                    'draft' => 'Draft',
                    'pending' => 'Pending',
                    'pickup' => 'Pickup',
                    'transit' => 'Transit',
                    'delivered' => 'Delivered',
                    'hold' => 'Hold',
                    'cancelled' => 'Cancelled',
                    default => $state ? ucfirst($state) : '-',
                }),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Aktivitas Terbaru';
    }
}

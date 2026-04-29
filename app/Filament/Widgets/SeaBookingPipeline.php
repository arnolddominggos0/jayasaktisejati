<?php

namespace App\Filament\Widgets;

use App\Enums\SeaBookingStatus;
use App\Models\SeaBooking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SeaBookingPipeline extends BaseWidget
{
    protected function getStats(): array
    {
        $counts = fn(string $s) => SeaBooking::where('status', $s)->count();

        return [
            Stat::make('Draft', (string) $counts(SeaBookingStatus::Draft->value))->description('RO/RC diterima'),
            Stat::make('Requested', (string) $counts(SeaBookingStatus::Requested->value))->description('Request ke carrier'),
            Stat::make('Confirmed', (string) $counts(SeaBookingStatus::Confirmed->value))->description('SLI/Booking ok'),
            Stat::make('In Progress', (string) $counts(SeaBookingStatus::InProgress->value))->description('Container jalan'),
            Stat::make('Completed', (string) $counts(SeaBookingStatus::Completed->value))->description('Selesai'),
        ];
    }
}

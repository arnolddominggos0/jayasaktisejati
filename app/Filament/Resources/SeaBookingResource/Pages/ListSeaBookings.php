<?php

namespace App\Filament\Resources\SeaBookingResource\Pages;

use App\Filament\Resources\SeaBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeaBookings extends ListRecords
{
    protected static string $resource = SeaBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

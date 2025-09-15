<?php

namespace App\Filament\Resources\ShippingLineResource\Pages;

use App\Filament\Resources\ShippingLineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingLines extends ListRecords
{
    protected static string $resource = ShippingLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

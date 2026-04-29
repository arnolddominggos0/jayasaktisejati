<?php

namespace App\Filament\Resources\ShippingLineResource\Pages;

use App\Filament\Resources\ShippingLineResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListShippingLines extends ListRecords
{
    protected static string $resource = ShippingLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Pelayaran'),
        ];
    }
}

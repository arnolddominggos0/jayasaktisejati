<?php

namespace App\Filament\Resources\ShippingLineResource\Pages;

use App\Filament\Resources\ShippingLineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShippingLine extends EditRecord
{
    protected static string $resource = ShippingLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

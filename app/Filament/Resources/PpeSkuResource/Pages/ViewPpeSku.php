<?php

namespace App\Filament\Resources\PpeSkuResource\Pages;

use App\Filament\Resources\PpeSkuResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPpeSku extends ViewRecord
{
    protected static string $resource = PpeSkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

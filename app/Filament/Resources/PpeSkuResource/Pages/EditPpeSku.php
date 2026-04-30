<?php

namespace App\Filament\Resources\PpeSkuResource\Pages;

use App\Filament\Resources\PpeSkuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPpeSku extends EditRecord
{
    protected static string $resource = PpeSkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PpeItemResource\Pages;

use App\Filament\Resources\PpeItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPpeItem extends EditRecord
{
    protected static string $resource = PpeItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

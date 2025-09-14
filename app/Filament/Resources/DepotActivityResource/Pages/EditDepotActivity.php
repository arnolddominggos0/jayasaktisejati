<?php

namespace App\Filament\Resources\DepotActivityResource\Pages;

use App\Filament\Resources\DepotActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepotActivity extends EditRecord
{
    protected static string $resource = DepotActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

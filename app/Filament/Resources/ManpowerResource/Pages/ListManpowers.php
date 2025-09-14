<?php

namespace App\Filament\Resources\ManpowerResource\Pages;

use App\Filament\Resources\ManpowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManpowers extends ListRecords
{
    protected static string $resource = ManpowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

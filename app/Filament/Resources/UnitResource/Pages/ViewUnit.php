<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewUnit extends ViewRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('← Kembali')
                ->url(UnitResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}

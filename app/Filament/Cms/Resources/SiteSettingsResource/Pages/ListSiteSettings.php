<?php

namespace App\Filament\Cms\Resources\SiteSettingsResource\Pages;

use App\Filament\Cms\Resources\SiteSettingsResource;
use App\Models\JslSiteSettings;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    protected static string $resource = SiteSettingsResource::class;

    protected function getHeaderActions(): array
    {
        if (JslSiteSettings::exists()) {
            return [];
        }

        return [
            Actions\CreateAction::make(),
        ];
    }
}

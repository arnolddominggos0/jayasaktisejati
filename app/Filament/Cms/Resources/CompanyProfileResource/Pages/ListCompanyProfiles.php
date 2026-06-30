<?php

namespace App\Filament\Cms\Resources\CompanyProfileResource\Pages;

use App\Filament\Cms\Resources\CompanyProfileResource;
use App\Models\JslCompanyProfile;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyProfiles extends ListRecords
{
    protected static string $resource = CompanyProfileResource::class;

    protected function getHeaderActions(): array
    {
        if (JslCompanyProfile::exists()) {
            return [];
        }

        return [
            Actions\CreateAction::make(),
        ];
    }
}

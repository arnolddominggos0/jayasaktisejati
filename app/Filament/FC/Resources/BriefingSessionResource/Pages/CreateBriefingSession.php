<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Filament\FC\Resources\BriefingSessionResource;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBriefingSession extends CreateRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['coordinator_user_id'] = $user->id;

        if (empty($data['depot_id'])) {
            $depot = Depot::where('coordinator_user_id', $user->id)->first();
            if ($depot) {
                $data['depot_id'] = $depot->id;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}

<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Filament\Resources\BriefingSessionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBriefingSession extends CreateRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();

        $key = [
            'date'     => $data['date'],
            'depot_id' => $data['depot_id'],
        ];

        return $model::query()->updateOrCreate($key, $data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sesi disimpan';
    }
}

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;
        if (request()->has('data.roles')) {
            $record->syncRoles((array) data_get($this->data, 'roles', []));
        }
    }
}

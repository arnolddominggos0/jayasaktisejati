<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use App\Enums\LoadingStatus;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLoadingSession extends CreateRecord
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Buat Sesi Loading Baru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        // Set coordinator
        $data['coordinator_user_id'] = $user->id;

        // Set depot
        if (empty($data['depot_id'])) {
            $depotId = app()->bound('scope.depot_id')
                ? app('scope.depot_id')
                : Depot::where('coordinator_user_id', $user->id)->value('id');
            $data['depot_id'] = $depotId;
        }

        // Set branch
        if (empty($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id ?? app('scope.branch_id') ?? null;
        }

        // Set initial status
        $data['status'] = LoadingStatus::Draft;
        $data['current_step'] = 'mp_attendance_check';
        $data['started_at'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sesi loading berhasil dibuat';
    }
}

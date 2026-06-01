<?php

namespace App\Filament\Resources\ArmadaMaintenanceResource\Pages;

use App\Enums\MaintenanceStatus;
use App\Filament\Resources\ArmadaMaintenanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArmadaMaintenance extends CreateRecord
{
    protected static string $resource = ArmadaMaintenanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $status = $data['status'] ?? MaintenanceStatus::InProgress->value;

        if ($status === MaintenanceStatus::Scheduled->value) {
            $data['started_at'] = null;
            $data['closed_at']  = null;
        } elseif ($status === MaintenanceStatus::InProgress->value) {
            $data['started_at'] = $data['started_at'] ?? now();  // <— pastikan isi
            $data['closed_at']  = null;
        } elseif ($status === MaintenanceStatus::Closed->value) {
            $data['started_at'] = $data['started_at'] ?? now();
            $data['closed_at']  = $data['closed_at'] ?? now();
        }

        return $data;
    }
}

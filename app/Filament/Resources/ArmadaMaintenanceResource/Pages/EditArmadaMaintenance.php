<?php

namespace App\Filament\Resources\ArmadaMaintenanceResource\Pages;

use App\Enums\MaintenanceStatus;
use App\Filament\Resources\ArmadaMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArmadaMaintenance extends EditRecord
{
    protected static string $resource = ArmadaMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Lihat'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] ?? null;

        if ($status === MaintenanceStatus::Scheduled->value) {
            $data['started_at'] = null;
            $data['closed_at']  = null;
        } elseif ($status === MaintenanceStatus::InProgress->value) {
            $data['started_at'] = $data['started_at'] ?? now();
            $data['closed_at']  = null;
        } elseif ($status === MaintenanceStatus::Closed->value) {
            $data['started_at'] = $data['started_at'] ?? now();
            $data['closed_at']  = $data['closed_at'] ?? now();
        }
        return $data;
    }
}

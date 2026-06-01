<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVoyage extends EditRecord
{
    protected static string $resource = VoyageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Ubah Voyage';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $voyage = $this->record;

        // ── Canonical checkpoint sync (code, name, scheduled_at, completed_at) ──

        if ($voyage->etb) {
            $voyage->checkpoints()->updateOrCreate(
                ['code' => 'ETB'],
                [
                    'name' => 'Estimasi Sandar',
                    'scheduled_at' => $voyage->etb,
                ]
            );
        }

        if ($voyage->atb_at) {
            $voyage->checkpoints()->updateOrCreate(
                ['code' => 'ATB'],
                [
                    'name' => 'Aktual Sandar',
                    'scheduled_at' => $voyage->atb_at,
                    'completed_at' => $voyage->atb_at,
                ]
            );
        }
    }
}

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
        return 'Edit Voyage';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $voyage = $this->record;

        if ($voyage->etb) {

            $voyage->checkpoints()->updateOrCreate(
                ['type' => 'etb'],
                [
                    'title' => 'Estimasi Sandar (ETB)',
                    'scheduled_at' => $voyage->etb,
                ]
            );
        }

        if ($voyage->atb_at) {

            $voyage->checkpoints()->updateOrCreate(
                ['type' => 'atb'],
                [
                    'title' => 'Aktual Sandar (ATB)',
                    'scheduled_at' => $voyage->atb_at,
                    'checked_at' => $voyage->atb_at,
                ]
            );
        }
    }
}

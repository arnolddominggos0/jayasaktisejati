<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Enums\VoyagePlanState;
use App\Filament\Resources\VoyageResource;
use Filament\Resources\Pages\EditRecord;

class EditVoyage extends EditRecord
{
    protected static string $resource = VoyageResource::class;

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $etd = $data['etd'] ?? null;
        $eta = $data['plan_eta'] ?? null;
        if ($etd || $eta) {
            $this->record->upsertPlan(
                VoyagePlanState::Final,
                ['etd' => $etd, 'eta' => $eta],
                $data['plan_notes'] ?? null,
                $data['plan_source'] ?? 'manual',
                auth()->id()
            );
        }
    }
}

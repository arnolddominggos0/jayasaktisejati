<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Filament\FC\Resources\BriefingSessionResource;
use App\Models\Depot;
use App\Models\StockApdCheck;
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

    /**
     * Phase 2 — Auto-generate 4 stock APD rows after session is created.
     *
     * required_quantity is pre-filled from summary_headcount so FC only
     * needs to enter the actual stock_available per type.
     * Uses firstOrCreate to stay idempotent (safe to re-run).
     */
    protected function afterCreate(): void
    {
        $record    = $this->record;
        $headcount = (int) ($record->summary_headcount ?? 0);

        $ppeTypes = ['helm', 'rompi', 'sepatu', 'sarung_tangan'];

        foreach ($ppeTypes as $type) {
            StockApdCheck::firstOrCreate(
                [
                    'session_id' => $record->id,
                    'ppe_type'   => $type,
                ],
                [
                    'required_quantity' => $headcount,
                    'stock_available'   => null,
                    'remark'            => null,
                ]
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}

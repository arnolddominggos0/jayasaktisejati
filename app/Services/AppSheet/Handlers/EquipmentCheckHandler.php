<?php

namespace App\Services\AppSheet\Handlers;

use App\Models\EquipmentCheck;
use App\Models\LoadingSession;
use Exception;

class EquipmentCheckHandler extends BaseSyncHandler
{
    protected bool $useFirstOrCreate = false;

    protected function modelClass(): string
    {
        return EquipmentCheck::class;
    }

    protected function primaryKey(): string|array
    {
        return 'loading_session_id';
    }

    protected function validateData(array $mappedData, array $rawData): void
    {
        $loadingSessionId = $mappedData['loading_session_id'] ?? null;

        if (! $loadingSessionId) {
            throw new Exception('Loading Session ID is required');
        }
    }

    public function resolveScopeContext(array $mappedData): ?array
    {
        $loadingSessionId = $mappedData['loading_session_id'] ?? null;

        if ($loadingSessionId) {
            $session = LoadingSession::find($loadingSessionId);

            return [
                'branch_id' => $session?->branch_id,
                'depot_id' => $session?->depot_id,
            ];
        }

        return null;
    }

    public function resolveShipmentId(array $mappedData): ?int
    {
        $loadingSessionId = $mappedData['loading_session_id'] ?? null;

        if ($loadingSessionId) {
            return LoadingSession::where('id', $loadingSessionId)->value('shipment_id');
        }

        return null;
    }
}
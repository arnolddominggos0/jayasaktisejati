<?php

namespace App\Services\AppSheet\Handlers;

use App\Models\LoadingSession;

class LoadingSessionHandler extends BaseSyncHandler
{
    protected function modelClass(): string
    {
        return LoadingSession::class;
    }

    protected function primaryKey(): string|array
    {
        return 'code';
    }

    public function resolveScopeContext(array $mappedData): ?array
    {
        return [
            'branch_id' => $mappedData['branch_id'] ?? null,
            'depot_id' => $mappedData['depot_id'] ?? null,
        ];
    }

    public function resolveShipmentId(array $mappedData): ?int
    {
        return $mappedData['shipment_id'] ?? null;
    }
}
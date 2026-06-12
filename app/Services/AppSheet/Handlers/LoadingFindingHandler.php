<?php

/**
 * @deprecated DEAD CODE — not called from any route or controller.
 * Active path: AppSheetService::syncLoadingFinding(). See BaseSyncHandler for details.
 */

namespace App\Services\AppSheet\Handlers;

use App\Models\LoadingFinding;
use App\Models\LoadingSession;

class LoadingFindingHandler extends BaseSyncHandler
{
    protected bool $useFirstOrCreate = false;

    protected function modelClass(): string
    {
        return LoadingFinding::class;
    }

    protected function primaryKey(): string|array
    {
        return 'id';
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
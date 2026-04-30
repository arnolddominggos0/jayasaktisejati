<?php

namespace App\Services\AppSheet\Handlers;

use App\Models\BriefingSession;
use App\Models\Depot;
use Exception;

class BriefingSessionHandler extends BaseSyncHandler
{
    protected function modelClass(): string
    {
        return BriefingSession::class;
    }

    protected function primaryKey(): string|array
    {
        return ['date', 'depot_id'];
    }

    protected function validateData(array $mappedData, array $rawData): void
    {
        $date = $mappedData['date'] ?? null;
        $depotId = $mappedData['depot_id'] ?? null;

        if (! $date || ! $depotId) {
            throw new Exception('Tanggal dan Depot ID wajib diisi untuk Briefing Session');
        }

        Depot::where('id', $depotId)->exists()
            || throw new Exception("Depot ID {$depotId} tidak ditemukan");
    }

    public function resolveScopeContext(array $mappedData): ?array
    {
        $depotId = $mappedData['depot_id'] ?? null;

        if ($depotId) {
            $depot = Depot::find($depotId);

            return [
                'branch_id' => $depot?->branch_id,
                'depot_id' => $depotId,
            ];
        }

        return null;
    }
}
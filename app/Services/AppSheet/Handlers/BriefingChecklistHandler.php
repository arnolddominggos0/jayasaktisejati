<?php

namespace App\Services\AppSheet\Handlers;

use App\Models\BriefingChecklist;
use App\Models\BriefingSession;
use Exception;

class BriefingChecklistHandler extends BaseSyncHandler
{
    protected function modelClass(): string
    {
        return BriefingChecklist::class;
    }

    protected function primaryKey(): string|array
    {
        return ['session_id', 'item'];
    }

    protected function validateData(array $mappedData, array $rawData): void
    {
        $sessionId = $mappedData['session_id'] ?? null;
        $item = $mappedData['item'] ?? null;

        if (! $sessionId || ! $item) {
            throw new Exception('Session ID dan Item wajib diisi untuk Briefing Checklist');
        }

        BriefingSession::where('id', $sessionId)->exists()
            || throw new Exception("Briefing Session ID {$sessionId} tidak ditemukan");
    }

    public function resolveScopeContext(array $mappedData): ?array
    {
        $sessionId = $mappedData['session_id'] ?? null;

        if ($sessionId) {
            $session = BriefingSession::with('depot')->find($sessionId);

            return [
                'branch_id' => $session?->depot?->branch_id,
                'depot_id' => $session?->depot_id,
            ];
        }

        return null;
    }
}
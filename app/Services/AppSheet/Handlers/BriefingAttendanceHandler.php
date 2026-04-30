<?php

namespace App\Services\AppSheet\Handlers;

use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Services\AppSheet\Concerns\RecalculatesBriefingSession;
use Exception;

class BriefingAttendanceHandler extends BaseSyncHandler
{
    use RecalculatesBriefingSession;

    protected function modelClass(): string
    {
        return BriefingAttendance::class;
    }

    protected function primaryKey(): string|array
    {
        return ['session_id', 'manpower_id'];
    }

    protected function validateData(array $mappedData, array $rawData): void
    {
        $sessionId = $mappedData['session_id'] ?? null;
        $manpowerId = $mappedData['manpower_id'] ?? null;

        if (! $sessionId || ! $manpowerId) {
            throw new Exception('Session ID dan Manpower ID wajib diisi untuk Briefing Attendance');
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

    public function afterSync($result): void
    {
        if ($result instanceof BriefingAttendance) {
            $this->recalculateBriefingSession($result->session_id);
        }
    }
}
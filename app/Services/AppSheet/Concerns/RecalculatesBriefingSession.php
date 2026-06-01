<?php

namespace App\Services\AppSheet\Concerns;

use App\Models\BriefingSession;
use Illuminate\Support\Facades\Log;

trait RecalculatesBriefingSession
{
    protected function recalculateBriefingSession(int $sessionId): void
    {
        $session = BriefingSession::find($sessionId);

        if (! $session) {
            return;
        }

        $present = $session->attendances()
            ->where('attendance_status', 'present')
            ->count();

        $target = (int) $session->summary_headcount;
        $session->summary_sufficient = $target > 0 && $present >= $target;
        $session->saveQuietly();

        if (config('appsheet.logging_enabled', true)) {
            Log::channel('appsheet')->info("Recalculated briefing session #{$sessionId}", [
                'present' => $present,
                'target' => $target,
                'sufficient' => $session->summary_sufficient,
            ]);
        }
    }
}
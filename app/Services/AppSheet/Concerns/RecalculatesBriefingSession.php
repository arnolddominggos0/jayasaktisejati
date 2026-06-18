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

        $session->summary_sufficient = $session->isOperationallyReady();
        $session->saveQuietly();

        if (config('appsheet.logging_enabled', true)) {
            Log::channel('appsheet')->info("Recalculated briefing session #{$sessionId}", [
                'ready' => $session->readyManpowerCount(),
                'target' => $session->summary_headcount,
                'sufficient' => $session->summary_sufficient,
            ]);
        }
    }
}
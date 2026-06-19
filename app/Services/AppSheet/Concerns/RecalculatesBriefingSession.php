<?php

namespace App\Services\AppSheet\Concerns;

use App\Models\BriefingSession;
use App\Services\BriefingSessionEvaluator;
use Illuminate\Support\Facades\Log;

trait RecalculatesBriefingSession
{
    protected function recalculateBriefingSession(int $sessionId): void
    {
        $session = BriefingSession::find($sessionId);

        if (! $session) {
            return;
        }

        BriefingSessionEvaluator::evaluate($session);

        if (config('appsheet.logging_enabled', true)) {
            Log::channel('appsheet')->info("Recalculated briefing session #{$sessionId}", [
                'ready'      => $session->readyManpowerCount(),
                'target'     => $session->summary_headcount,
                'sufficient' => $session->summary_sufficient,
                'status'     => is_object($session->mp_check_status)
                    ? $session->mp_check_status->value
                    : $session->mp_check_status,
            ]);
        }
    }
}
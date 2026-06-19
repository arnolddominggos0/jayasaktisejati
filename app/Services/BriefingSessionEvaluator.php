<?php

namespace App\Services;

use App\Enums\MPCheckStatus;
use App\Models\BriefingSession;

/**
 * Single source of truth for BriefingSession readiness evaluation.
 *
 * Resolves the SC.5D.4D defect: summary_sufficient and mp_check_status
 * previously had different writers (Filament path only updated summary_sufficient;
 * AppSheet path updated both), causing permanent divergence when sessions were
 * created or modified via Filament.
 *
 * All callers — model hooks, AppSheetService, manual recalculation — must
 * delegate here. Do not inline readiness logic elsewhere.
 */
class BriefingSessionEvaluator
{
    /**
     * Evaluate and persist summary_sufficient + mp_check_status for a session.
     *
     * Rules (applied in priority order):
     *   1. pending_activity = true  → WaitingAction  (summary_sufficient reflects headcount math)
     *   2. ready < required         → OnCheck         (summary_sufficient = false)
     *   3. ready >= required        → Cleared          (summary_sufficient = true)
     *
     * Auto-reopen: a previously Cleared session will revert to OnCheck if
     * readiness drops below the required headcount. Status always reflects
     * current live readiness, never stale.
     *
     * Statuses that are NOT overwritten: Failed, Approved.
     * These represent terminal human decisions and must not be reset by formula.
     */
    public static function evaluate(BriefingSession $session): void
    {
        $ready    = $session->readyManpowerCount();
        $required = (int) $session->summary_headcount;

        // Determine new mp_check_status.
        // Failed and Approved are terminal — leave them unchanged.
        $current = $session->mp_check_status instanceof MPCheckStatus
            ? $session->mp_check_status
            : MPCheckStatus::tryFrom((string) $session->mp_check_status);

        if (in_array($current, [MPCheckStatus::Failed, MPCheckStatus::Approved])) {
            // Still refresh summary_sufficient so the flag stays accurate.
            $session->summary_sufficient = $required > 0 && $ready >= $required;
            $session->saveQuietly();
            return;
        }

        if ($session->pending_activity) {
            $session->summary_sufficient = $required > 0 && $ready >= $required;
            $session->mp_check_status    = MPCheckStatus::WaitingAction;
        } elseif ($required > 0 && $ready >= $required) {
            $session->summary_sufficient = true;
            $session->mp_check_status    = MPCheckStatus::Cleared;
        } else {
            $session->summary_sufficient = false;
            $session->mp_check_status    = MPCheckStatus::OnCheck;
        }

        $session->saveQuietly();
    }
}

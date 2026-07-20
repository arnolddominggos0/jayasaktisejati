<?php

namespace App\Services;

use App\Enums\MPCheckStatus;
use App\Models\BriefingSession;

class BriefingSessionEvaluator
{
    public static function evaluate(BriefingSession $session): void
    {
        $ready    = $session->readyManpowerCount();
        $required = (int) $session->summary_headcount;


        $current = $session->mp_check_status instanceof MPCheckStatus
            ? $session->mp_check_status
            : MPCheckStatus::tryFrom((string) $session->mp_check_status);

        if (in_array($current, [MPCheckStatus::Failed, MPCheckStatus::Approved])) {
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

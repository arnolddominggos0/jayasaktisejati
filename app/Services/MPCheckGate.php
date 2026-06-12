<?php

namespace App\Services;

use App\Models\BriefingSession;
use App\Models\Depot;
use Illuminate\Support\Carbon;

class MpCheckGate
{
    public static function ensureApproved(Depot $depot, ?Carbon $date = null): void
    {
        $date ??= now();

        $cleared = BriefingSession::query()
            ->where('depot_id', $depot->id)
            ->whereDate('date', $date)
            ->where('mp_check_status', 'cleared')
            ->exists();

        if (! $cleared) {
            throw new \DomainException(
                'MP Check belum selesai (Cleared) untuk depot dan tanggal ini.'
            );
        }
    }
}


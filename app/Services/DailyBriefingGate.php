<?php

namespace App\Services;

use App\Models\BriefingSession;
use Illuminate\Support\Carbon;

class DailyBriefingGate
{
    public static function activeSessionFor(int $depotId, ?Carbon $date = null): ?BriefingSession
    {
        $date ??= Carbon::today();

        return BriefingSession::query()
            ->where('depot_id', $depotId)
            ->whereDate('date', $date)
            ->latest('id')
            ->first();
    }
    public static function isReady(int $depotId, ?Carbon $date = null): bool
    {
        $session = self::activeSessionFor($depotId, $date);

        if (! $session) {
            return false;
        }

        return $session->isOperationallyReady();
    }
    public static function blockReason(int $depotId, ?Carbon $date = null): ?string
    {
        if (self::isReady($depotId, $date)) {
            return null;
        }

        return 'Operasional hari ini belum dibuka. Silakan selesaikan Briefing Harian terlebih dahulu.';
    }
}

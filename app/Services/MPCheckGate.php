<?php

namespace App\Services;

use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\Shipment;
use Illuminate\Support\Carbon;

class MpCheckGate
{
    /**
     * SC.3B.20 — Pivot-based gate.
     *
     * Validates that the shipment has at least one BriefingSession (via the
     * briefing_session_shipments pivot) with mp_check_status = 'cleared'.
     * This is the single source of truth for work-start authorisation.
     *
     * @throws \DomainException if not cleared
     */
    public static function ensureApproved(Shipment $shipment): void
    {
        $cleared = $shipment->briefingSessions()
            ->where('mp_check_status', 'cleared')
            ->exists();

        if (! $cleared) {
            throw new \DomainException(
                "MP Check belum Cleared untuk shipment {$shipment->code}. " .
                'Koordinator harus menyelesaikan Briefing Session dan mengeset status ke Cleared.'
            );
        }
    }

    /**
     * Legacy depot+date check — kept for backward-compat with console commands
     * that have not been migrated to shipment-based flow.
     *
     * @deprecated Use ensureApproved(Shipment) instead.
     */
    public static function ensureApprovedByDepot(Depot $depot, ?Carbon $date = null): void
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


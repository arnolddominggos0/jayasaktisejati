<?php

namespace App\Services;

use App\Enums\TrackStatus;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Gate-based operational ownership for FC shipment visibility.
 *
 * ORIGIN gate  (pickup → vessel_depart):   Origin FC (assigned_depot_id)
 * DESTINATION gate (vessel_arrival → delivered): Destination FC (pod → depots.port_id)
 *
 * Hold / Cancelled: inherit last non-ambiguous gate.
 * Pending (no tracked records): ORIGIN.
 */
class ShipmentOperationalGateResolver
{
    const ORIGIN      = 'origin';
    const DESTINATION = 'destination';

    // ─── PHP-level: individual record ────────────────────────────────────────

    /**
     * Resolve the current operational gate for a single shipment.
     *
     * Hold / Cancelled: strips ambiguous status → uses last real status.
     * Pending (no real tracks): returns ORIGIN so origin FC sees new shipments.
     */
    public static function resolve(Shipment $shipment): string
    {
        $status = self::effectiveGateStatus($shipment);

        if (! $status) {
            return self::ORIGIN;
        }

        return in_array($status, self::destinationStatuses(), true)
            ? self::DESTINATION
            : self::ORIGIN;
    }

    /**
     * Origin depot ID: directly from shipments.assigned_depot_id.
     */
    public static function resolveOriginDepotId(Shipment $shipment): ?int
    {
        return $shipment->assigned_depot_id ? (int) $shipment->assigned_depot_id : null;
    }

    /**
     * Destination depot ID: resolved via port_id.
     *
     * Step 1: shipment.pod_id → depots.port_id
     * Step 2: shipment.voyage.pod_id → depots.port_id  (fallback when pod_id is NULL)
     */
    public static function resolveDestinationDepotId(Shipment $shipment): ?int
    {
        if ($shipment->pod_id) {
            $id = Depot::where('port_id', $shipment->pod_id)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        // voyageRecord is the safe alias for the Voyage relation. Never use
        // $shipment->voyage here — that resolves to the string snapshot column.
        $voyagePodId = $shipment->voyage_id
            ? Voyage::whereKey($shipment->voyage_id)->value('pod_id')
            : null;

        if ($voyagePodId) {
            $id = Depot::where('port_id', $voyagePodId)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    // ─── SQL-level: query scope for getEloquentQuery() ───────────────────────

    /**
     * Scope a Shipment query to shipments visible to a given depot/user.
     *
     * Three arms (OR):
     *   A. Origin gate:  assigned_depot matches  AND  effective gate is ORIGIN
     *   B. Dest gate:    pod resolves to depot   AND  effective gate is DESTINATION
     *   C. Legacy:       coordinator_id = user   (safety net / pre-assignment)
     *
     * "Effective gate" = latest tracked status excluding hold/cancelled.
     */
    public static function scopeForDepot(Builder $query, int $depotId, int $userId): Builder
    {
        $portId     = Depot::whereKey($depotId)->value('port_id');
        $originVals = self::originStatusValues();
        $destVals   = self::destinationStatusValues();
        $ambigLit   = implode("','", [TrackStatus::Hold->value, TrackStatus::Cancelled->value]);

        // Correlated subquery: latest non-ambiguous track status.
        // Returns NULL when no real tracked record exists (= pending shipment).
        $effectiveSql = "(SELECT status FROM shipment_tracks
                          WHERE shipment_id = shipments.id
                            AND tracked_at IS NOT NULL
                            AND status NOT IN ('{$ambigLit}')
                          ORDER BY tracked_at DESC LIMIT 1)";

        return $query->where(function ($q) use (
            $depotId, $portId, $userId,
            $originVals, $destVals, $effectiveSql
        ) {
            // ── A. Origin gate ──────────────────────────────────────────────
            $q->where(function ($orig) use ($depotId, $originVals, $effectiveSql) {
                $orig
                    ->where('assigned_depot_id', $depotId)
                    ->where(function ($phase) use ($originVals, $effectiveSql) {
                        $phase
                            // Pending: no tracked record at all → ORIGIN
                            ->whereNotExists(fn ($t) =>
                                $t->from('shipment_tracks')
                                  ->whereColumn('shipment_id', 'shipments.id')
                                  ->whereNotNull('tracked_at')
                            )
                            // Latest effective status is origin-phase
                            ->orWhereIn(DB::raw($effectiveSql), $originVals);
                    });
            })

            // ── B. Destination gate ─────────────────────────────────────────
            ->orWhere(function ($dest) use ($portId, $destVals, $effectiveSql) {
                if (! $portId) {
                    return;
                }

                $dest
                    ->where(function ($pod) use ($portId) {
                        // Direct: shipment.pod_id = depot.port_id
                        $pod->where('pod_id', $portId)
                            // Fallback: voyage.pod_id = depot.port_id
                            ->orWhereExists(fn ($v) =>
                                $v->from('voyages')
                                  ->whereColumn('voyages.id', 'shipments.voyage_id')
                                  ->where('voyages.pod_id', $portId)
                            );
                    })
                    ->whereIn(DB::raw($effectiveSql), $destVals);
            })

            // ── C. Legacy coordinator fallback ──────────────────────────────
            ->orWhere('coordinator_id', $userId);
        });
    }

    // ─── Status lists ─────────────────────────────────────────────────────────

    public static function originStatusValues(): array
    {
        return array_map(fn ($s) => $s->value, self::originStatuses());
    }

    public static function destinationStatusValues(): array
    {
        return array_map(fn ($s) => $s->value, self::destinationStatuses());
    }

    private static function originStatuses(): array
    {
        return [
            TrackStatus::Pickup,
            TrackStatus::Handover,
            TrackStatus::Stuffing,
            TrackStatus::DeliveryToPort,
            TrackStatus::Stacking,
            TrackStatus::UnitLoading,
            TrackStatus::OnShip,
            TrackStatus::VesselDepart,
        ];
    }

    private static function destinationStatuses(): array
    {
        return [
            TrackStatus::VesselArrival,
            TrackStatus::Unloading,
            TrackStatus::HandoverTrucking,
            TrackStatus::DeliveryToCustomer,
            TrackStatus::Delivered,
        ];
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * Latest TrackStatus excluding Hold and Cancelled.
     * Returns null when no real tracked record exists (pending shipment).
     */
    private static function effectiveGateStatus(Shipment $shipment): ?TrackStatus
    {
        $ambig = [TrackStatus::Hold->value, TrackStatus::Cancelled->value];

        $track = $shipment->tracks()
            ->whereNotNull('tracked_at')
            ->whereNotIn('status', $ambig)
            ->orderByDesc('tracked_at')
            ->first(['status']);

        $status = $track?->status;

        return $status instanceof TrackStatus ? $status : null;
    }
}

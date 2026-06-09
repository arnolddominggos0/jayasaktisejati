<?php

namespace App\Services;

use App\Enums\TrackStatus;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;

/**
 * Single source of truth for FC ownership resolution.
 *
 * Answers three questions for any (User, Shipment) pair:
 *   - Is this user the Origin FC?   (assigned_depot_id)
 *   - Is this user the Destination FC?  (pod_id → depots.port_id)
 *   - What ownership phase is the shipment in?  (pre_transfer / post_transfer)
 *
 * Transfer point: VesselArrival.
 *   - pre_transfer  : latest tracked status normalized value < 90  (VesselArrival = 90)
 *   - post_transfer : latest tracked status normalized value >= 90
 *
 * No UI code. No migrations. No schema changes.
 * Used by ShipmentPolicy, and later by ShipmentResource and widgets in Phase 2B+.
 */
class ShipmentOwnership
{
    // -------------------------------------------------------------------------
    // Depot resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve the depot ID that represents this user's operational scope.
     *
     * Three-step fallback (matches ScopeByBranchAndDepot middleware logic):
     *   1. IoC binding set by middleware during web request
     *   2. Canonical scope fields on the User model
     *   3. Live coordinator_user_id assignment on depots table
     */
    public static function resolveUserDepotId(User $user): ?int
    {
        // 1. IoC binding (ScopeByBranchAndDepot middleware sets this)
        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        // 2. Canonical scope fields
        if ($user->scope_unit_type === 'depot' && $user->scope_unit_id) {
            return (int) $user->scope_unit_id;
        }

        // 3. Live depot assignment
        $id = Depot::where('coordinator_user_id', $user->id)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Resolve the depot that serves as the destination for a shipment.
     *
     * Chain: shipment.pod_id → depots.port_id → depot.id
     *
     * This is FK-complete — depots.port_id is a constrained FK to ports.id,
     * and shipments.pod_id is also a FK to ports.id. No migration needed.
     *
     * Returns null if pod_id is unset or no depot is configured for that port.
     */
    public static function resolveDestinationDepotId(Shipment $shipment): ?int
    {
        if (! $shipment->pod_id) {
            return null;
        }

        $id = Depot::where('port_id', $shipment->pod_id)->value('id');

        return $id ? (int) $id : null;
    }

    // -------------------------------------------------------------------------
    // Ownership checks
    // -------------------------------------------------------------------------

    /**
     * True if the user is the Origin FC for this shipment.
     *
     * Two paths:
     *   - User's depot matches shipment.assigned_depot_id
     *   - User is the direct coordinator (legacy fallback)
     *
     * Note: no branch_id check. The assigned_depot_id already implies branch scope.
     */
    public static function isOriginFC(User $user, Shipment $shipment): bool
    {
        // Direct coordinator assignment (legacy path — coordinator without depot scope)
        if ($shipment->coordinator_id === $user->id) {
            return true;
        }

        $userDepotId = self::resolveUserDepotId($user);

        if (! $userDepotId) {
            return false;
        }

        return (int) $shipment->assigned_depot_id === $userDepotId;
    }

    /**
     * True if the user is the Destination FC for this shipment.
     *
     * Resolution: user's depot must match the depot at the shipment's POD port.
     * Chain: shipment.pod_id → depots.port_id = user's depot
     */
    public static function isDestinationFC(User $user, Shipment $shipment): bool
    {
        $userDepotId = self::resolveUserDepotId($user);

        if (! $userDepotId) {
            return false;
        }

        $destinationDepotId = self::resolveDestinationDepotId($shipment);

        if (! $destinationDepotId) {
            return false;
        }

        return $userDepotId === $destinationDepotId;
    }

    // -------------------------------------------------------------------------
    // Phase determination
    // -------------------------------------------------------------------------

    /**
     * Return which ownership phase the shipment is currently in.
     *
     * 'pre_transfer'  — shipment is at Origin FC (Pickup through VesselDepart)
     * 'post_transfer' — shipment is at Destination FC (VesselArrival through Delivered)
     *
     * Transfer trigger: VesselArrival (normalized order value = 90).
     * VesselArrival itself is a Destination FC action — Destination FC records it.
     */
    public static function phase(Shipment $shipment): string
    {
        $status = $shipment->currentTrackStatus();

        if (! $status) {
            return 'pre_transfer';
        }

        return $status->toNormalizedValue() >= TrackStatus::VesselArrival->toNormalizedValue()
            ? 'post_transfer'
            : 'pre_transfer';
    }

    // -------------------------------------------------------------------------
    // Composite checks
    // -------------------------------------------------------------------------

    /**
     * True if the user can view this shipment as an FC.
     *
     * Either Origin FC or Destination FC may view at any phase.
     * Enforces mode = sea (FC panel is sea-only).
     */
    public static function canView(User $user, Shipment $shipment): bool
    {
        $mode = $shipment->mode?->value ?? (string) $shipment->mode;

        if ($mode !== 'sea') {
            return false;
        }

        return self::isOriginFC($user, $shipment)
            || self::isDestinationFC($user, $shipment);
    }

    /**
     * True if the user can edit (track, hold, cancel) this shipment right now.
     *
     * Phase-gated:
     *   pre_transfer  → only Origin FC can edit
     *   post_transfer → only Destination FC can edit
     *
     * A user who is both Origin and Destination FC (same depot, same port)
     * can edit in both phases.
     */
    public static function canEdit(User $user, Shipment $shipment): bool
    {
        if (! self::canView($user, $shipment)) {
            return false;
        }

        $phase = self::phase($shipment);

        if ($phase === 'pre_transfer') {
            return self::isOriginFC($user, $shipment);
        }

        // post_transfer
        return self::isDestinationFC($user, $shipment);
    }
}

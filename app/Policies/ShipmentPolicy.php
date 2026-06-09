<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentOwnership;

class ShipmentPolicy
{
    /**
     * super_admin bypasses all checks.
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Any user who can list shipments at all.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['office_admin', 'field_coordinator']);
    }

    /**
     * View a single shipment.
     *
     * office_admin : same branch (unchanged)
     * field_coordinator : Origin FC OR Destination FC, any phase, mode = sea
     *
     * Decoupled from update() — read-only access is now independent.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('office_admin')) {
            return is_null($shipment->branch_id)
                || $shipment->branch_id === $user->effectiveBranchId();
        }

        if ($user->hasRole('field_coordinator')) {
            return ShipmentOwnership::canView($user, $shipment);
        }

        return false;
    }

    /**
     * Edit / track a shipment.
     *
     * office_admin : same branch (unchanged)
     * field_coordinator : phase-gated ownership
     *   pre_transfer  → Origin FC only  (Pickup … VesselDepart)
     *   post_transfer → Destination FC only  (VesselArrival … Delivered)
     */
    public function update(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('office_admin')) {
            return is_null($shipment->branch_id)
                || $shipment->branch_id === $user->effectiveBranchId();
        }

        if ($user->hasRole('field_coordinator')) {
            return ShipmentOwnership::canEdit($user, $shipment);
        }

        return false;
    }

    /**
     * Print documents (waybill, packing list, resi).
     *
     * Delegates to view() — any FC who can see the shipment can print its documents.
     * Previously delegated through view() → update(), which blocked read-only observers.
     */
    public function print(User $user, Shipment $shipment): bool
    {
        return $this->view($user, $shipment);
    }

    /**
     * Create is admin-only.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'office_admin']);
    }

    /**
     * Delete follows update rules.
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        return $this->update($user, $shipment);
    }
}

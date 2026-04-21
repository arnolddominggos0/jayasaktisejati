<?php

namespace App\Policies;

use App\Models\ShipmentTrack;
use App\Models\User;

class ShipmentTrackPolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['office_admin', 'field_coordinator']);
    }

    public function view(User $user, ShipmentTrack $track): bool
    {
        $shipment = $track->shipment;

        if (!$shipment) {
            return false;
        }

        // Check branch ownership
        if ($user->hasRole('office_admin') && $user->branch_id) {
            return $shipment->branch_id === null || $shipment->branch_id === $user->branch_id;
        }

        // Field coordinator can view if assigned
        if ($user->hasRole('field_coordinator')) {
            return $shipment->coordinator_id === $user->id || $shipment->coordinator_id === null;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'office_admin']);
    }

    public function update(User $user, ShipmentTrack $track): bool
    {
        $shipment = $track->shipment;

        if (!$shipment) {
            return false;
        }

        // Only super admin can update (enforced by before())
        // This method should not be reached by non-super-admin due to before()
        // But we add branch check for defense-in-depth
        if ($user->branch_id && $shipment->branch_id !== null) {
            return $shipment->branch_id === $user->branch_id;
        }

        return true;
    }

    public function delete(User $user, ShipmentTrack $track): bool
    {
        // Only super admin can delete (enforced by before())
        $shipment = $track->shipment;

        if (!$shipment) {
            return false;
        }

        // Defense-in-depth branch check
        if ($user->branch_id && $shipment->branch_id !== null) {
            return $shipment->branch_id === $user->branch_id;
        }

        return true;
    }
}

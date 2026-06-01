<?php

namespace App\Policies;

use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{

    public function before(User $user, $ability)
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

    public function view(User $user, Shipment $shipment): bool
    {
        return $this->update($user, $shipment);
    }

    /**
     * Print policy: FC may only print sea shipment documents.
     * Other roles follow view/update scoping.
     */
    public function print(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('field_coordinator')) {
            $mode = $shipment->mode?->value ?? (string) $shipment->mode;
            if ($mode !== 'sea') {
                return false;
            }
        }

        return $this->view($user, $shipment);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'office_admin']);
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('office_admin')) {
            return is_null($shipment->branch_id) || $shipment->branch_id === $user->effectiveBranchId();
        }

        if ($user->hasRole('field_coordinator')) {
            $mode = $shipment->mode?->value ?? (string) $shipment->mode;
            if ($mode !== 'sea') {
                return false;
            }

            if ($shipment->branch_id !== $user->effectiveBranchId()) {
                return false;
            }

            // Canonical scope check first
            if ($user->scope_unit_type === 'depot' && $user->scope_unit_id && $shipment->assigned_depot_id === $user->scope_unit_id) {
                return true;
            }

            // Fallback to legacy depot lookup or direct coordinator_id
            $depotId = app()->bound('scope.depot_id')
                ? app('scope.depot_id')
                : Depot::where('coordinator_user_id', $user->id)->value('id');

            if ($depotId && $shipment->assigned_depot_id === $depotId) {
                return true;
            }

            return $shipment->coordinator_id === $user->id;
        }

        return false;
    }


    public function delete(User $user, Shipment $shipment): bool
    {
        return $this->update($user, $shipment);
    }
}

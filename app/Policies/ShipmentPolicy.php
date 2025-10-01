<?php

namespace App\Policies;

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

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'office_admin']);
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('office_admin')) {
            return is_null($shipment->branch_id) || $shipment->branch_id === $user->branch_id;
        }

        if ($user->hasRole('field_coordinator')) {
            return $shipment->coordinator_id === $user->id || is_null($shipment->coordinator_id);
        }

        return false;
    }


    public function delete(User $user, Shipment $shipment): bool
    {
        return $this->update($user, $shipment);
    }
}

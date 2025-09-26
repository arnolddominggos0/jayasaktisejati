<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin','office_admin','field_coordinator']);
    }

    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->hasAnyRole(['super_admin','office_admin'])) return true;
        if ($user->hasRole('field_coordinator')) {
            return ($user->branch_id && $shipment->branch_id === $user->branch_id)
                && ($shipment->coordinator_id === null || $shipment->coordinator_id === $user->id);
        }
        return false;
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if ($user->hasAnyRole(['super_admin','office_admin'])) return true;
        if ($user->hasRole('field_coordinator')) {
            return ($shipment->coordinator_id === $user->id);
        }
        return false;
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->hasAnyRole(['super_admin','office_admin']);
    }
}

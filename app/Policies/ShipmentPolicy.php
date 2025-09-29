<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'office_admin', 'field_coordinator']);
    }

    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->hasAnyRole(['super_admin', 'office_admin'])) return true;
        if ($user->hasRole('field_coordinator')) {
            return ($user->branch_id && $shipment->branch_id === $user->branch_id)
                && ($shipment->coordinator_id === null || $shipment->coordinator_id === $user->id);
        }
        return false;
    }

    public function update(User $user, Shipment $s): bool
    {
        if (! $user->hasRole('field_coordinator')) return false;

        $sameBranch = !$user->branch_id || $s->branch_id === $user->branch_id || $s->branch_id === null;
        $sameOffice = !$user->office_id
            || in_array($user->office_id, [$s->origin_office_id, $s->destination_office_id, null], true);

        $editable = !in_array($s->status?->value ?? (string)$s->status, ['delivered', 'cancelled'], true);

        return $sameBranch && $sameOffice && $editable;
    }


    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->hasAnyRole(['super_admin', 'office_admin']);
    }
}

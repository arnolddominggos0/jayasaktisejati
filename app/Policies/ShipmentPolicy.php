<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['super_admin', 'office_admin']);
    }
    public function view(User $u, Shipment $s): bool
    {
        return $this->viewAny($u);
    }

    public function update(User $u, Shipment $s): bool
    {
        return $u->hasRole('super_admin');
    }
}

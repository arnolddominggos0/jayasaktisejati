<?php

namespace App\Policies;

use App\Models\ShipmentTrack;
use App\Models\User;

class ShipmentTrackPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['super_admin', 'office_admin']);
    }
    public function view(User $u, ShipmentTrack $t): bool
    {
        return $this->viewAny($u);
    }
    public function create(User $u): bool
    {
        return $u->hasRole('super_admin');
    }
    public function update(User $u, ShipmentTrack $t): bool
    {
        return $u->hasRole('super_admin');
    }
    public function delete(User $u, ShipmentTrack $t): bool
    {
        return $u->hasRole('super_admin');
    }
}

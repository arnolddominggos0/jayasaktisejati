<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Shipment;

class ShipmentPolicy
{
    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('admin-office') || $user->hasRole('koordinator-lapangan')) {
            return $user->office_id && $user->office_id === $shipment->office_id;
        }
        if ($user->hasRole('customer')) {
            return $user->customer_id && $user->customer_id === $shipment->customer_id;
        }
        return false;
    }


    public function update(User $user, Shipment $shipment): bool
    {
        if ($user->hasRole('admin-office') || $user->hasRole('koordinator-lapangan')) {
            return $user->office_id === $shipment->office_id;
        }
        return false;
    }
}

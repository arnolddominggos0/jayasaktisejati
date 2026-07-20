<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentOperationalGateResolver;

class ShipmentOwnership
{

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

    public static function resolveDestinationDepotId(Shipment $shipment): ?int
    {
        return ShipmentOperationalGateResolver::resolveDestinationDepotId($shipment);
    }

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

    public static function phase(Shipment $shipment): string
    {
        return ShipmentOperationalGateResolver::resolve($shipment) === ShipmentOperationalGateResolver::DESTINATION
            ? 'post_transfer'
            : 'pre_transfer';
    }

    public static function canView(User $user, Shipment $shipment): bool
    {
        $mode = $shipment->mode?->value ?? (string) $shipment->mode;

        if ($mode !== 'sea') {
            return false;
        }

        return self::isOriginFC($user, $shipment)
            || self::isDestinationFC($user, $shipment);
    }


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

<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class MonitoringWorkspacePolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isOfficeUser()) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOfficeUser();
    }

    public function viewDetail(User $user, Shipment $shipment): bool
    {
        if ($user->isOfficeAdmin()) {
            $branchId = $user->effectiveBranchId();

            if ($branchId && $shipment->branch_id !== null) {
                return (int) $shipment->branch_id === (int) $branchId;
            }
        }

        return true;
    }
}
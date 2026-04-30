<?php

namespace App\Http\Middleware;

use App\Models\Depot;
use App\Models\Pool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeByBranchAndDepot
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (method_exists($user, 'hasRole') && ! $user->hasRole('field_coordinator')) {
            return $next($request);
        }

        $depots = Depot::query()
            ->select('id', 'branch_id', 'mode', 'coordinator_user_id')
            ->where('coordinator_user_id', $user->id)
            ->get();

        $pools = Pool::query()
            ->select('id', 'branch_id', 'mode', 'coordinator_user_id')
            ->where('coordinator_user_id', $user->id)
            ->get();

        $totalAssignments = $depots->count() + $pools->count();

        if ($totalAssignments === 0) {
            abort(403, 'Anda belum ditetapkan sebagai Koordinator pada depo atau pool mana pun. Mohon minta admin menetapkan Anda di Master Depo/Pool.');
        }

        if ($totalAssignments > 1) {
            abort(409, 'Konfigurasi dobel: pengguna ini ditetapkan sebagai koordinator di lebih dari satu unit (depo/pool). Harap benahi Master Depo/Pool.');
        }

        // Determine live assignment
        if ($depots->isNotEmpty()) {
            $liveUnit = $depots->first();
            $liveUnitType = 'depot';
            $liveUnitId = $liveUnit->id;
            $liveMode = $liveUnit->mode;
            $liveBranchId = $liveUnit->branch_id;
        } else {
            $liveUnit = $pools->first();
            $liveUnitType = 'pool';
            $liveUnitId = $liveUnit->id;
            $liveMode = $liveUnit->mode ?: 'land';
            $liveBranchId = $liveUnit->branch_id;
        }

        // Canonical-scope guard: if user already has scope_* fields populated,
        // they must match the live depots/pools assignment exactly.
        if (
            $user->scope_branch_id !== null
            || $user->scope_unit_id !== null
            || $user->scope_unit_type !== null
        ) {
            $canonicalMismatch = [];

            if ($user->scope_branch_id !== $liveBranchId) {
                $canonicalMismatch[] = 'scope_branch_id';
            }
            if ($user->scope_unit_id !== $liveUnitId) {
                $canonicalMismatch[] = 'scope_unit_id';
            }
            if ($user->scope_unit_type !== $liveUnitType) {
                $canonicalMismatch[] = 'scope_unit_type';
            }

            if (! empty($canonicalMismatch)) {
                abort(409, 'Konfigurasi scope kanonik tidak cocok dengan Master Depo/Pool. Field: ' . implode(', ', $canonicalMismatch) . '.');
            }
        }

        // Bind scope from canonical user fields when populated; otherwise fallback to live assignment.
        $unitType = $user->scope_unit_type ?? $liveUnitType;
        $unitId   = $user->scope_unit_id   ?? $liveUnitId;
        $branchId = $user->scope_branch_id ?? $liveBranchId;
        $mode     = $liveMode;

        $request->session()->put('fc.active_branch_id', $branchId);
        $request->session()->put('fc.active_mode', $mode);
        $request->session()->put('fc.active_unit_type', $unitType);

        if ($unitType === 'depot') {
            $request->session()->put('fc.active_depot_id', $unitId);
            $request->session()->forget('fc.active_pool_id');
        } else {
            $request->session()->put('fc.active_pool_id', $unitId);
            $request->session()->forget('fc.active_depot_id');
        }

        app()->instance('scope.branch_id', $branchId);
        app()->instance('scope.mode', $mode);
        app()->instance('scope.unit_type', $unitType);

        if ($unitType === 'depot') {
            app()->instance('scope.depot_id', $unitId);
            app()->forgetInstance('scope.pool_id');
        } else {
            app()->instance('scope.pool_id', $unitId);
            app()->forgetInstance('scope.depot_id');
        }

        return $next($request);
    }
}

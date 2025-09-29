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

        if ($depots->isNotEmpty()) {
            $unit = $depots->first();
            $unitType = 'depot';
            $unitId = $unit->id;
            $mode = $unit->mode;
            $branchId = $unit->branch_id;

            $request->session()->put('fc.active_depot_id', $unitId);
            $request->session()->forget('fc.active_pool_id');
        } else {
            $unit = $pools->first();
            $unitType = 'pool';
            $unitId = $unit->id;
            $mode = $unit->mode ?: 'land';
            $branchId = $unit->branch_id;

            $request->session()->put('fc.active_pool_id', $unitId);
            $request->session()->forget('fc.active_depot_id');
        }

        $request->session()->put('fc.active_branch_id', $branchId);
        $request->session()->put('fc.active_mode', $mode);
        $request->session()->put('fc.active_unit_type', $unitType);

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

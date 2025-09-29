<?php

namespace App\Http\Middleware;

use App\Models\Depot;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeByBranchAndDepot
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) abort(401);

        $depots = Depot::query()
            ->select('id', 'branch_id', 'mode', 'coordinator_user_id')
            ->where('coordinator_user_id', $user->id)
            ->get();

        if ($depots->isEmpty()) {
            abort(403, 'Anda belum ditetapkan sebagai Koordinator pada depo mana pun. Mohon minta admin menetapkan Anda di Master Depo.');
        }

        if ($depots->count() > 1) {
            abort(409, 'Konfigurasi dobel: pengguna ini ditetapkan sebagai koordinator di lebih dari satu depo. Harap benahi Master Depo.');
        }

        $depot = $depots->first();

        $request->session()->put('fc.active_depot_id', $depot->id);
        $request->session()->put('fc.active_branch_id', $depot->branch_id);
        $request->session()->put('fc.active_depot_mode', $depot->mode);

        app()->instance('scope.branch_id', $depot->branch_id);
        app()->instance('scope.depot_id',  $depot->id);
        app()->instance('scope.depot_mode',$depot->mode);

        return $next($request);
    }
}

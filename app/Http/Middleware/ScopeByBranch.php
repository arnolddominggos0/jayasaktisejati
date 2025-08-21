<?php


namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;


class ScopeByBranch
{
    public function handle(Request $request, Closure $next)
    {
        if ($u = $request->user()) {
            $currentBranchId = $u->hasRole('super_admin') ? null : $u->branch_id;


            app()->instance('currentBranchId', $currentBranchId);
            $request->attributes->set('currentBranchId', $currentBranchId);
        }


        return $next($request);
    }
}
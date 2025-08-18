<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ScopeByBranch
{
    public function handle(Request $request, Closure $next)
    {
        if ($u = $request->user()) {
            app()->instance('currentBranchId', $u->hasRol('super_admin') ? null : $u->branch_id);
        }
        return $next($request);
    }
}

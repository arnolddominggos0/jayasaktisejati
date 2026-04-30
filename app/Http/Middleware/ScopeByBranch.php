<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ScopeByBranch
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $currentBranchId = $user->hasRole('super_admin')
                ? null
                : $user->effectiveBranchId();

            app()->instance('currentBranchId', $currentBranchId);

            $request->attributes->set('currentBranchId', $currentBranchId);
        }

        return $next($request);
    }
}

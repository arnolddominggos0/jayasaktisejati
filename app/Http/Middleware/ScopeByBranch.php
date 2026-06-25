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
            // Only super_admin has global (unscoped) branch view.
            // office_admin is intentionally branch-scoped via effectiveBranchId().
            $currentBranchId = $user->isSuperAdmin()
                ? null
                : $user->effectiveBranchId();

            app()->instance('currentBranchId', $currentBranchId);

            $request->attributes->set('currentBranchId', $currentBranchId);
        }

        return $next($request);
    }
}

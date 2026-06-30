<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelRole
{
    public function handle($request, Closure $next): Response
    {
        $user  = Filament::auth()->user() ?? auth()->user();
        $panel = Filament::getCurrentPanel();

        if (! $user) {
            return $next($request);
        }

        if ($panel) {
            if ($panel->getId() === 'fc') {
                if (! $user->hasAnyRole(['field_coordinator', 'super_admin'])) {
                    abort(403, 'Panel ini khusus Koordinator Lapangan.');
                }
            }

            if ($panel->getId() === 'customer') {
                if (! $user->hasRole('customer')) {
                    abort(403, 'Panel ini khusus untuk Customer.');
                }
            }

            if ($panel->getId() === 'cms') {
                if (! $user->hasAnyRole(['cms', 'super_admin'])) {
                    abort(403, 'Panel ini khusus untuk CMS Editor.');
                }
            }

            return $next($request);
        }

        $path = $request->path();

        if (str_starts_with($path, 'fc')) {
            if (! $user->hasAnyRole(['field_coordinator', 'super_admin'])) {
                abort(403, 'Panel ini khusus Koordinator Lapangan.');
            }
        }

        if (str_starts_with($path, 'customer')) {
            if (! $user->hasRole('customer')) {
                abort(403, 'Panel ini khusus untuk Customer.');
            }
        }

        if (str_starts_with($path, 'cms')) {
            if (! $user->hasAnyRole(['cms', 'super_admin'])) {
                abort(403, 'Panel ini khusus untuk CMS Editor.');
            }
        }

        return $next($request);
    }
}
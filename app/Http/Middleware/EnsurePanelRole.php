<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelRole
{
    public function handle($request, Closure $next): Response
    {
        $user  = Filament::auth()->user();
        $panel = Filament::getCurrentPanel();

        if (! $panel || ! $user || ! $user instanceof FilamentUser) {
            return $next($request);
        }

        if (! $user->canAccessPanel($panel)) {
            Filament::auth()->logout();
            return redirect()->to($panel->getLoginUrl());
        }

        if ($panel->getId() === 'fc') {
            if (! (method_exists($user, 'hasRole') && ($user->hasRole('field_coordinator') || $user->hasRole('super_admin')))) {
                abort(403, 'Panel ini khusus Koordinator Lapangan.');
            }
        }

        return $next($request);
    }
}

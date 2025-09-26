<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelRole
{
    public function handle($request, Closure $next): Response
    {
        /** @var \App\Models\User|\Filament\Models\Contracts\FilamentUser|null $user */
        $user  = Filament::auth()->user();

        /** @var \Filament\Panel|null $panel */
        $panel = Filament::getCurrentPanel();

        if (! $panel || ! $user || ! $user instanceof \Filament\Models\Contracts\FilamentUser) {
            return $next($request);
        }

        if (! $user->canAccessPanel($panel)) {
            Filament::auth()->logout();
            return redirect()->to($panel->getLoginUrl());
        }

        return $next($request);
    }
}

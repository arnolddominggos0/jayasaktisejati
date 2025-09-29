<?php

namespace App\Providers\Filament;

use App\Filament\FC\Pages\Dashboard\Dashboard;
use App\Http\Middleware\EnsurePanelRole;
use App\Http\Middleware\ScopeByBranchAndDepot;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class FieldCoordinatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('fc')
            ->path('fc')
            ->login()
            ->authGuard('web')

            ->brandName('Jaya Sakti Sejati')
            ->brandLogo(fn () => view('filament.logo'))
            ->favicon(asset('images/favicon/favicon.ico'))

            ->sidebarCollapsibleOnDesktop()
            // ->globalSearchKeyBindings(['ctrl+k','cmd+k']) 

            ->colors(['primary' => '#0137A1'])
            ->viteTheme('resources/css/filament/fc/theme.css')

            ->pages([ Dashboard::class ])
            ->homeUrl(fn () => Dashboard::getUrl())

            ->discoverResources(in: app_path('Filament/FC/Resources'), for: 'App\\Filament\\FC\\Resources')
            ->discoverPages(in: app_path('Filament/FC/Pages'),       for: 'App\\Filament\\FC\\Pages')
            ->discoverWidgets(in: app_path('Filament/FC/Widgets'),   for: 'App\\Filament\\FC\\Widgets')

            ->navigationGroups([
                'Operasional Lapangan',
                'Armada & K3',
                'Laporan & Notifikasi',
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,

                EnsurePanelRole::class,
                ScopeByBranchAndDepot::class,
            ])
            ->authMiddleware([ Authenticate::class ]);
    }
}

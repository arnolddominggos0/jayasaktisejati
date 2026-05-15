<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsurePanelRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Customer Portal Panel Provider
 * 
 * Provides a simplified interface for customers to:
 * - View their shipments
 * - Track shipment status
 * - Download documents
 * - Manage their profile
 */
class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('portal')
            ->login()
            ->authGuard('web')
            ->brandName('JSS Customer Portal')
            ->brandLogo(fn() => view('filament.logo'))
            ->favicon(asset('images/favicon/favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::hex('#2563EB'), // Biru yang lebih friendly
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(false)
            // Discover resources, pages, and widgets in Customer namespace
            ->discoverResources(in: app_path('Filament/Customer/Resources'), for: 'App\\Filament\\Customer\\Resources')
            ->discoverPages(in: app_path('Filament/Customer/Pages'), for: 'App\\Filament\\Customer\\Pages')
            ->discoverWidgets(in: app_path('Filament/Customer/Widgets'), for: 'App\\Filament\\Customer\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            // Simplified navigation
            ->navigationGroups([
                'Pengiriman',
                'Akun',
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
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsurePanelRole::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn(): string => '<meta name="customer-portal" content="true">',
            );
    }
}

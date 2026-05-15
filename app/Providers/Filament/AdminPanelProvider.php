<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminDashboard;
use App\Http\Middleware\EnsurePanelRole;
use App\Http\Middleware\ScopeByBranch;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Widgets;
use Filament\Support\Assets\Css;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default(AdminDashboard::class)
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->brandName('Jaya Sakti Sejati')
            ->brandLogo(fn() => view('filament.logo'))
            ->favicon(asset('images/favicon/favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->colors(['primary' => Color::hex('#0137A1')])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(false)
            ->assets([
                Css::make('filament', 'css/filament/filament/app.css'),
                Css::make('forms', 'css/filament/forms/forms.css'),
                Css::make('support', 'css/filament/support/support.css'),
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn() => Blade::render(view('filament.topbar.page-title')->render()),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn() => Blade::render(view('filament.topbar.actions')->render()),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                // NavigationGroup::make('Manajemen Armada & MP')->collapsible(),
                NavigationGroup::make('Pelayaran & Kapal')->collapsible(),
                NavigationGroup::make('Pengiriman')->collapsible(),
                NavigationGroup::make('Manajemen Pengguna')->collapsible(),
            ])
            ->middleware([
                EnsurePanelRole::class,
                ScopeByBranch::class,
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
            ]);
    }
}

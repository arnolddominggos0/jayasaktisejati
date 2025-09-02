<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()

            //Brand
            ->brandName('Jaya Sakti Sejati')
            // ->databaseNotifications()
            ->globalSearchKeyBindings(['ctrl+k', 'cmd+k'])
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(fn() => view('filament.logo'))
            ->favicon(asset('images/favicon/favicon.ico'))
            ->colors([
                'primary' => Color::hex('#0137A1'),
            ])

            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn() => view('filament.topbar.page-title')->render()
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn() => view('filament.topbar.search')->render()
        );

        // Kanan: bell + tombol Export
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn() => view('filament.topbar.actions')->render()
        );

        // Sembunyikan page header putih hanya di Dashboard
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn() => view('filament.topbar.hide-dashboard-header')->render()
        );
    }
}

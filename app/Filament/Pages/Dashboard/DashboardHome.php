<?php

namespace App\Filament\Pages\Dashboard;

use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentIcon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class DashboardHome extends Page
{
    protected static ?string $slug = 'dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;
    protected static string $view = 'filament.pages.dashboard-home';

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ??
            static::$title ??
            __('filament-panels::pages/dashboard.title');
    }

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-home' : 'heroicon-o-home');
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Pages\Dashboard\Widgets\KpiOverview::class,
            \App\Filament\Pages\Dashboard\Widgets\ShipmentsByStatusChart::class,
            \App\Filament\Pages\Dashboard\Widgets\ShipmentsTrendChart::class,
            \App\Filament\Pages\Dashboard\Widgets\TrackingActivityTable::class,
            \App\Filament\Pages\Dashboard\Widgets\TodayManpowerWidget::class,
            \App\Filament\Pages\Dashboard\Widgets\ActiveArmadaWidget::class,
            // \App\Filament\Pages\Dashboard\Widgets\LeadTimeCustomerWidget::class,
        ];
    }

    /**
     * @return array<string, int|string|null>|int|string
     */
    public function getColumns(): int | string | array
    {
        // 12-grid:  -> kita atur di blade via CSS grid responsive
        return 3;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }
}
<?php

namespace App\Filament\Customer\Pages;

use App\Models\Shipment;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

/**
 * Customer Dashboard Page
 * 
 * Display overview of customer's shipments and activities
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationLabel = 'Beranda';
    
    protected static ?string $title = 'Dashboard Customer';
    
    protected static ?int $navigationSort = 1;

    /**
     * Get the widgets for this page
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Customer\Widgets\CustomerStatsOverview::class,
            \App\Filament\Customer\Widgets\RecentShipments::class,
            \App\Filament\Customer\Widgets\ShipmentStatusChart::class,
        ];
    }

    /**
     * Get the columns layout for widgets
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }

    /**
     * Get header widgets (full width)
     */
    public function getHeaderWidgets(): array
    {
        return [];
    }

    /**
     * Get footer widgets (full width)
     */
    public function getFooterWidgets(): array
    {
        return [];
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

// Observers
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Observers\ShipmentObserver;
use App\Observers\ShipmentTrackObserver;
use Livewire\Livewire;
use App\Filament\Pages\Dashboard\Widgets\{
    KpiOverview,
    ShipmentsTrendChart,
    ShipmentsByStatusChart,
    TrackingActivityTable,
    TodayManpowerWidget,
    ActiveArmadaWidget
};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        Date::setLocale('id');

        $tr = Carbon::getTranslator();
        if (method_exists($tr, 'setTranslations')) {
            $tr->setTranslations([
                'ago'      => ':time lalu',
                'from_now' => 'dalam :time',
            ]);
        }

        // Observers
        Shipment::observe(ShipmentObserver::class);
        ShipmentTrack::observe(ShipmentTrackObserver::class);

        $aliases = [
            'app.filament.pages.dashboard.widgets.kpi-overview'           => KpiOverview::class,
            'app.filament.pages.dashboard.widgets.shipments-trend-chart'  => ShipmentsTrendChart::class,
            'app.filament.pages.dashboard.widgets.shipments-by-status-chart' => ShipmentsByStatusChart::class,
            'app.filament.pages.dashboard.widgets.tracking-activity-table' => TrackingActivityTable::class,
            'app.filament.pages.dashboard.widgets.today-manpower-widget'   => TodayManpowerWidget::class,
            'app.filament.pages.dashboard.widgets.active-armada-widget'    => ActiveArmadaWidget::class,
        ];

        foreach ($aliases as $alias => $componentClass) {
            if (class_exists($componentClass)) {
                Livewire::component($alias, $componentClass);
            }
        }
    }
}

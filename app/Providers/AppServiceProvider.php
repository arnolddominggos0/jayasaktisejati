<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Lang;

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
    ActiveArmadaWidget,
    ShippingScheduleCalendar
};
use App\Filament\Widgets\ShippingScheduleCalendar as WidgetsShippingScheduleCalendar;
use App\Models\Customer;
use App\Observers\CustomerObserver;

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

        // Observers
        Shipment::observe(ShipmentObserver::class);
        ShipmentTrack::observe(ShipmentTrackObserver::class);
        // ShipmentTrackHistory::observe(ShipmentTrackHistoryObserver::class);
        Customer::observe(CustomerObserver::class);

        $aliases = [
            'app.filament.pages.dashboard.widgets.kpi-overview'             => KpiOverview::class,
            'app.filament.pages.dashboard.widgets.shipments-trend-chart'    => ShipmentsTrendChart::class,
            'app.filament.pages.dashboard.widgets.shipments-by-status-chart' => ShipmentsByStatusChart::class,
            'app.filament.pages.dashboard.widgets.tracking-activity-table'  => TrackingActivityTable::class,
            'app.filament.pages.dashboard.widgets.today-manpower-widget'    => TodayManpowerWidget::class,
            'app.filament.pages.dashboard.widgets.active-armada-widget'     => ActiveArmadaWidget::class,
            'app.filament.pages.dashboard.widgets.shipping-schedule-calendar' => WidgetsShippingScheduleCalendar::class,
            'app.filament.widgets.shipping-schedule-calendar' => WidgetsShippingScheduleCalendar::class,
            'app.filament.widgets.schedule-kpi-placeholder' => \App\Filament\Widgets\ScheduleKpiPlaceholder::class,
            'app.filament.widgets.schedule-gantt-placeholder' => \App\Filament\Widgets\ScheduleGanttPlaceholder::class,
            'app.filament.pages.shipping-schedule-resource.pages.overview-shipping-schedules' => \App\Filament\Resources\ShippingScheduleResource\Pages\OverviewShippingSchedules::class,
        ];

        foreach ($aliases as $alias => $componentClass) {
            if (class_exists($componentClass)) {
                Livewire::component($alias, $componentClass);
            }
        }
    }
}

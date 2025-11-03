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
use App\Filament\Widgets\ScheduleGanttPlaceholder;
use App\Filament\Widgets\ScheduleKpiPlaceholder;
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

        Shipment::observe(ShipmentObserver::class);
        ShipmentTrack::observe(ShipmentTrackObserver::class);
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
            'app.filament.widgets.schedule-kpi-placeholder' => ScheduleKpiPlaceholder::class,
            'app.filament.widgets.schedule-gantt-placeholder' => ScheduleGanttPlaceholder::class,
        ];

        foreach ($aliases as $alias => $componentClass) {
            if (class_exists($componentClass)) {
                Livewire::component($alias, $componentClass);
            }
        }
    }
}

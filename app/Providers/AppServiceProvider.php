<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Customer;
use App\Models\ShippingSchedule;

use App\Observers\ShipmentObserver;
use App\Observers\ShipmentTrackObserver;
use App\Observers\CustomerObserver;
use App\Observers\ShippingScheduleObserver;

use Livewire\Livewire;

use App\Filament\Pages\Dashboard\Widgets\{
    KpiOverview,
    ShipmentsTrendChart,
    ShipmentsByStatusChart,
    TrackingActivityTable,
    TodayManpowerWidget,
    ActiveArmadaWidget,
};
use App\Filament\Resources\ShippingScheduleResource\Widgets\ScheduleKpiPlaceholder;
use App\Filament\Resources\ShippingScheduleResource\Widgets\ScheduleGanttPlaceholder;
use App\Filament\Widgets\ShippingScheduleCalendar;
use App\Filament\Widgets\ShippingScheduleTable;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        Carbon::setLocale('id');
        Date::setLocale('id');

        Shipment::observe(ShipmentObserver::class);
        ShipmentTrack::observe(ShipmentTrackObserver::class);
        Customer::observe(CustomerObserver::class);
        ShippingSchedule::observe(ShippingScheduleObserver::class);

        $aliases = [
            'app.filament.pages.dashboard.widgets.kpi-overview'              => KpiOverview::class,
            'app.filament.pages.dashboard.widgets.shipments-trend-chart'     => ShipmentsTrendChart::class,
            'app.filament.pages.dashboard.widgets.shipments-by-status-chart' => ShipmentsByStatusChart::class,
            'app.filament.pages.dashboard.widgets.tracking-activity-table'   => TrackingActivityTable::class,
            'app.filament.pages.dashboard.widgets.today-manpower-widget'     => TodayManpowerWidget::class,
            'app.filament.pages.dashboard.widgets.active-armada-widget'      => ActiveArmadaWidget::class,
            'app.filament.widgets.schedule-kpi-placeholder'   => ScheduleKpiPlaceholder::class,
            'app.filament.widgets.schedule-gantt-placeholder' => ScheduleGanttPlaceholder::class,
            'app.filament.widgets.shipping-schedule-calendar' => ShippingScheduleCalendar::class,
            'app.filament.widgets.shipping-schedule-table'    => ShippingScheduleTable::class,
        ];

        foreach ($aliases as $alias => $componentClass) {
            if (class_exists($componentClass)) {
                Livewire::component($alias, $componentClass);
            }
        }
    }
}

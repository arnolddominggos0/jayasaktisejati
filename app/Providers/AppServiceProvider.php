<?php

namespace App\Providers;

use App\Filament\Pages\Dashboard\Widgets\ActiveArmadaWidget;
use App\Filament\Pages\Dashboard\Widgets\KpiOverview;
use App\Filament\Pages\Dashboard\Widgets\ShipmentsByStatusChart;
use App\Filament\Pages\Dashboard\Widgets\ShipmentsTrendChart;
use App\Filament\Pages\Dashboard\Widgets\TodayManpowerWidget;
use App\Filament\Pages\Dashboard\Widgets\TrackingActivityTable;
use App\Filament\Resources\ShippingScheduleResource\Widgets\ScheduleGanttPlaceholder;
use App\Filament\Resources\ShippingScheduleResource\Widgets\ScheduleKpiPlaceholder;
use App\Filament\Widgets\ShippingScheduleCalendar;
use App\Filament\Widgets\ShippingScheduleTable;
use App\Models\Customer;
use App\Models\LoadingSession;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\ShippingSchedule;
use App\Models\Unit;
use App\Observers\CustomerObserver;
use App\Observers\LoadingSessionObserver;
use App\Observers\ShipmentObserver;
use App\Observers\ShipmentTrackObserver;
use App\Observers\ShippingScheduleObserver;
use App\Observers\UnitObserver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        LoadingSession::observe(LoadingSessionObserver::class);
        Unit::observe(UnitObserver::class);

        $aliases = [
            'app.filament.pages.dashboard.widgets.kpi-overview' => KpiOverview::class,
            'app.filament.pages.dashboard.widgets.shipments-trend-chart' => ShipmentsTrendChart::class,
            'app.filament.pages.dashboard.widgets.shipments-by-status-chart' => ShipmentsByStatusChart::class,
            'app.filament.pages.dashboard.widgets.tracking-activity-table' => TrackingActivityTable::class,
            'app.filament.pages.dashboard.widgets.today-manpower-widget' => TodayManpowerWidget::class,
            'app.filament.pages.dashboard.widgets.active-armada-widget' => ActiveArmadaWidget::class,
            'app.filament.widgets.schedule-kpi-placeholder' => ScheduleKpiPlaceholder::class,
            'app.filament.widgets.schedule-gantt-placeholder' => ScheduleGanttPlaceholder::class,
            'app.filament.widgets.shipping-schedule-calendar' => ShippingScheduleCalendar::class,
            'app.filament.widgets.shipping-schedule-table' => ShippingScheduleTable::class,
        ];

        foreach ($aliases as $alias => $componentClass) {
            if (class_exists($componentClass)) {
                Livewire::component($alias, $componentClass);
            }
        }

        // ── TEMPORARY — OCR-01B Livewire payload investigation ─────────────
        // Tap SETIAP mutasi property Livewire yang datang dari browser.
        // Event 'update' dipicu di HandleComponents::updateProperty() SEBELUM
        // pemeriksaan "public property not found" — jadi path beracun yang
        // menyebabkan "Public property [$]" pasti terekam di sini, tepat pada
        // request yang crash. HAPUS blok ini setelah OCR-01B selesai.
        if (app()->environment('local')) {
            \Livewire\on('update', function ($component, $fullPath, $value) {
                $firstSegment = explode('.', (string) $fullPath)[0];

                \Illuminate\Support\Facades\Log::info('OCR-01B UPDATE TAP', [
                    'component'      => method_exists($component, 'getName') ? $component->getName() : get_class($component),
                    'field_name'     => $firstSegment,
                    'state_path'     => $fullPath,
                    'update_payload' => is_scalar($value) || is_null($value)
                        ? $value
                        : get_debug_type($value),
                    'SUSPICIOUS'     => $firstSegment === '' || str_contains((string) $fullPath, '$'),
                ]);
            });
        }
    }
}

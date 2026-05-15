<x-filament-panels::page>
 <div class="space-y-6">
 <x-filament-widgets::widgets
 :widgets="[
 \App\Filament\Pages\Dashboard\Widgets\KpiOverview::class,
 \App\Filament\Pages\Dashboard\Widgets\ShipmentsTrendChart::class,
 \App\Filament\Pages\Dashboard\Widgets\ShipmentsByStatusChart::class,
 // \App\Filament\Pages\Dashboard\Widgets\LeadTimeCustomerWidget::class,
 \App\Filament\Pages\Dashboard\Widgets\TrackingActivityTable::class,
 \App\Filament\Pages\Dashboard\Widgets\TodayManpowerWidget::class,
 \App\Filament\Pages\Dashboard\Widgets\ActiveArmadaWidget::class,
 ]"
 :columns="['sm'=>1,'xl'=>3]"
 />
 </div>
</x-filament-panels::page>

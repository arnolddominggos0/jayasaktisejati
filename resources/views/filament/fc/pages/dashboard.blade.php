<x-filament-panels::page>
    {{-- Taruh widget container standar --}}
    <x-filament-widgets::widgets
        :columns="3"
        :widgets="[
            \App\Filament\FC\Widgets\FcKpiStats::class,
            \App\Filament\FC\Widgets\FcStatusChart::class,
            \App\Filament\FC\Widgets\FcRecentActivities::class,
        ]" />
</x-filament-panels::page>
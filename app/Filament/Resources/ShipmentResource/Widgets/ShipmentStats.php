<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Carbon\CarbonPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShipmentStats extends BaseWidget
{
    protected int|string|array $columnSpan = 3;


    protected function getColumns(): int
    {
        return 3;
    }

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $draft      = Shipment::where('status', ShipmentStatus::Draft->value)->count();
        $inProgress = Shipment::whereIn('status', ShipmentStatus::inProgress())->count();
        $delivered  = Shipment::where('status', ShipmentStatus::Delivered)->count();

        $days = CarbonPeriod::create(now()->subDays(6)->startOfDay(), '1 day', now()->endOfDay());
        $dailyDelivered = [];
        $dailyProgress  = [];
        $dailyDraft     = [];

        foreach ($days as $day) {
            $start = $day->copy()->startOfDay();
            $end   = $day->copy()->endOfDay();

            $dailyDelivered[] = Shipment::where('status', ShipmentStatus::Delivered)
                ->whereBetween('updated_at', [$start, $end])->count();

            $dailyProgress[] = Shipment::whereIn('status', ShipmentStatus::inProgress())
                ->whereBetween('updated_at', [$start, $end])->count();

            $dailyDraft[] = Shipment::where('status', ShipmentStatus::Draft)
                ->whereBetween('updated_at', [$start, $end])->count();
        }

        $thisWeekDelivered = array_sum($dailyDelivered);
        $prevWeekDelivered = Shipment::where('status', ShipmentStatus::Delivered)
            ->whereBetween('updated_at', [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()])
            ->count();
        // $deltaDelivered = $this->formatDelta($thisWeekDelivered, $prevWeekDelivered);

        $thisWeekProgress = array_sum($dailyProgress);
        $prevWeekProgress = Shipment::whereIn('status', ShipmentStatus::inProgress())
            ->whereBetween('updated_at', [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()])
            ->count();
        // $deltaProgress = $this->formatDelta($thisWeekProgress, $prevWeekProgress);

        $thisWeekDraft = array_sum($dailyDraft);
        $prevWeekDraft = Shipment::where('status', ShipmentStatus::Draft)
            ->whereBetween('updated_at', [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()])
            ->count();
        // $deltaDraft = $this->formatDelta($thisWeekDraft, $prevWeekDraft);

        return [
            Stat::make('Draft', number_format($draft))
                ->icon('heroicon-m-document')
                ->color('gray')
                // ->description($deltaDraft['text'])
                // ->descriptionIcon($deltaDraft['icon'])
                // ->descriptionColor($deltaDraft['color'])
                ->chart($dailyDraft) // mini sparkline
                ->extraAttributes(['class' => 'rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 py-4 px-4']),

            Stat::make('In Progress', number_format($inProgress))
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                // ->description($deltaProgress['text'])
                // ->descriptionIcon($deltaProgress['icon'])
                // ->descriptionColor($deltaProgress['color'])
                ->chart($dailyProgress)
                ->extraAttributes(['class' => 'rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 py-4 px-4']),

            Stat::make('Delivered', number_format($delivered))
                ->icon('heroicon-m-check-badge')
                ->color('success')
                // ->description($deltaDelivered['text'])
                // ->descriptionIcon($deltaDelivered['icon'])
                // ->descriptionColor($deltaDelivered['color'])
                ->chart($dailyDelivered)
                ->extraAttributes(['class' => 'rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 py-4 px-4']),
        ];
    }

    // private function formatDelta(int $now, int $prev): array
    // {
    //     if ($prev <= 0 && $now <= 0) {
    //         return ['text' => '0% vs last 7d', 'icon' => 'heroicon-m-minus', 'color' => 'gray'];
    //     }
    //     if ($prev <= 0) {
    //         return ['text' => '+100% vs last 7d', 'icon' => 'heroicon-m-arrow-trending-up', 'color' => 'success'];
    //     }
    //     $diff = $now - $prev;
    //     $pct  = round(($diff / max($prev, 1)) * 100);
    //     if ($pct > 0) {
    //         return ['text' => "+{$pct}% vs last 7d", 'icon' => 'heroicon-m-arrow-trending-up', 'color' => 'success'];
    //     } elseif ($pct < 0) {
    //         return ['text' => "{$pct}% vs last 7d", 'icon' => 'heroicon-m-arrow-trending-down', 'color' => 'danger'];
    //     }
    //     return ['text' => '0% vs last 7d', 'icon' => 'heroicon-m-minus', 'color' => 'gray'];
    // }
}

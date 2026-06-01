<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Supports\ShippingCalendar\MonthlyCalendarBuilder;

class ShippingScheduleCalendar extends Widget
{
    protected static string $view = 'filament.widgets.shipping-schedule-calendar';
    protected int|string|array $columnSpan = 'full';

    public string $period;
    public array $calendar = [];

    public function mount(): void
    {
        $this->period = $this->period ?? now()->format('Y-m');

        [$year, $month] = array_map('intval', explode('-', $this->period));

        $this->calendar = app(MonthlyCalendarBuilder::class)
            ->forMonth($year, $month);
    }

    protected function getViewData(): array
    {
        return [
            'calendar' => $this->calendar,
        ];
    }
}

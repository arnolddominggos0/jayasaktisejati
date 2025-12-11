<?php

namespace App\Filament\Resources\ShippingScheduleResource\Widgets;

use App\Supports\ShippingCalendar\MonthlyCalendarBuilder;
use Filament\Widgets\Widget;

class ShippingScheduleCalendar extends Widget
{
    protected static string $view = 'filament.widgets.shipping-schedule-calendar';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxContentWidth = 'full';

    public string $month;
    public int $year;
    public int $monthNum;
    public string $polCode;
    public string $podCode;

    public function mount(): void
    {
        $this->month = request('month', now()->format('Y-m'));
        [$y, $m] = array_map('intval', explode('-', $this->month));
        $this->year = $y ?: (int) now()->year;
        $this->monthNum = $m ?: (int) now()->month;

        $this->polCode = strtoupper(request('tam_pol', config('tam.route.pol_code', 'JKT')));
        $this->podCode = strtoupper(request('tam_pod', config('tam.route.pod_code', 'BTG')));

        $this->syncMonthString();
    }

    public function updatedYear(): void { $this->syncMonthString(); }
    public function updatedMonthNum(): void { $this->syncMonthString(); }
    protected function syncMonthString(): void { $this->month = sprintf('%04d-%02d', $this->year, $this->monthNum); }

    protected function getData(): array
    {
        /** @var MonthlyCalendarBuilder $builder */
        $builder = app(MonthlyCalendarBuilder::class);
        return $builder->forMonth($this->year, $this->monthNum, $this->polCode, $this->podCode);
    }
}

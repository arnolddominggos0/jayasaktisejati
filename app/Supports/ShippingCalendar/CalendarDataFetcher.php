<?php

namespace App\Supports\ShippingCalendar;

use App\Models\ShippingSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalendarDataFetcher
{
    public function fetchByPeriod(Carbon $period): Collection
    {
        return ShippingSchedule::query()
            ->with(['voyage.vessel', 'voyage.pol', 'voyage.pod'])
            ->whereYear('period_month', $period->year)
            ->whereMonth('period_month', $period->month)
            ->get();
    }
}

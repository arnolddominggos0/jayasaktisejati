<?php

namespace App\Observers;

use App\Models\ShippingSchedule;
use Illuminate\Support\Facades\Cache;

class ShippingScheduleObserver
{
    public function saved(ShippingSchedule $schedule): void
    {
        $this->flushFor($schedule);
    }

    public function deleted(ShippingSchedule $schedule): void
    {
        $this->flushFor($schedule);
    }

    protected function flushFor(ShippingSchedule $schedule): void
    {
        foreach (['etd', 'eta'] as $col) {
            $dt = $schedule->{$col};
            if ($dt) {
                $key = 'schedule:calendar:' . $dt->format('Y-m');
                Cache::forget($key . ':rows');
                Cache::forget($key . ':total');
            }
        }
    }
}

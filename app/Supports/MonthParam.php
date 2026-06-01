<?php

namespace App\Supports;

use Illuminate\Support\Carbon;

class MonthParam
{
    public static function resolve(?string $month, string $tz = 'Asia/Jakarta'): array
    {
        $val = ($month && preg_match('/^\d{4}-\d{2}$/', $month)) ? $month : now($tz)->format('Y-m');
        [$y, $m] = array_map('intval', explode('-', $val));

        $start = Carbon::createFromDate($y, $m, 1, $tz)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        return [
            'value'      => $val,
            'year'       => $y,
            'month'      => $m,
            'start'      => $start,
            'end'        => $end,
            'prev_value' => $start->copy()->subMonth()->format('Y-m'),
            'next_value' => $start->copy()->addMonth()->format('Y-m'),
            'label'      => $start->isoFormat('MMMM YYYY'),
            'today'      => now($tz)->toDateString(),
        ];
    }
}

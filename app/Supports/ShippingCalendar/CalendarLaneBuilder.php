<?php

namespace App\Supports\ShippingCalendar;

use Illuminate\Support\Carbon;

class CalendarLaneBuilder
{
    public function build(array $groups, Carbon $period): array
    {
        $days = $period->daysInMonth;

        $lanes = [
            'plan_etd' => 'ETD (Plan)',
            'plan_eta' => 'ETA (Plan)',
            'act_atd'  => 'ATD (Aktual)',
            'act_ata'  => 'ATA (Aktual)',
        ];

        $bucket = [];
        foreach ($lanes as $k => $_) {
            $bucket[$k] = array_fill(1, $days, []);
        }

        foreach ($groups as $g) {
            foreach ([
                'plan_etd' => $g['etd'],
                'plan_eta' => $g['eta'],
                'act_atd'  => $g['atd'],
                'act_ata'  => $g['ata'],
            ] as $lane => $date) {
                if ($date && $date->month === $period->month) {
                    $bucket[$lane][$date->day][] = [
                        'short' => $g['vessel'],
                        'voyage_no' => $g['voyage_no'],
                        'plan' => $g['cargo_plan'],
                    ];
                }
            }
        }

        return [
            'lanes' => $lanes,
            'bucket'=> $bucket,
        ];
    }
}

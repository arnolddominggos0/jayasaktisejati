<?php

namespace App\Actions;

use App\Models\ShippingSchedule;
use App\Models\VesselCheck;
use Illuminate\Support\Carbon;

class GenerateVesselChecks
{
    public static function run(ShippingSchedule $schedule): void
    {
        $voyage = $schedule->voyage;

        if (! $voyage || ! $voyage->etd) {
            return;
        }

        $etd = Carbon::parse($voyage->etd)->startOfDay();

        $map = [
            'H-3' => $etd->copy()->subDays(3),
            'H-2' => $etd->copy()->subDays(2),
            'H-1' => $etd->copy()->subDays(1),
        ];

        foreach ($map as $code => $date) {
            VesselCheck::firstOrCreate(
                [
                    'shipping_schedule_id' => $schedule->id,
                    'check_date'           => $date->toDateString(),
                ],
                [
                    'day_code'    => $code,
                    'etd_plan'    => $voyage->etd,
                    'etd_current' => $voyage->etd,
                    'status'      => 'on_schedule',
                    'source'      => 'system',
                ]
            );
        }
    }
}

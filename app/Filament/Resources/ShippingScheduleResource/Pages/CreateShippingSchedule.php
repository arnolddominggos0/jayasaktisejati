<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Models\Voyage;
use App\Models\ShippingSchedule;
use Carbon\Carbon;

class CreateShippingSchedule
{
    public static function run(Voyage $voyage): ShippingSchedule
    {
        return ShippingSchedule::firstOrCreate(
            [
                'voyage_id' => $voyage->id,
            ],
            [
                'period_month' => $voyage->period_month,
                'jss'          => 'JSS-' . $voyage->voyage_no,
                'cargo_plan'   => 0,
                'state'        => 'draft',
            ]
        );
    }


    protected static function generateJss(Voyage $voyage): string
    {
        return sprintf(
            'JSS-%s-%s',
            $voyage->pol?->code ?? 'XX',
            Carbon::parse($voyage->etd)->format('Ym')
        );
    }
}

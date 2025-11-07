<?php

namespace App\Actions\Schedule;

use App\Models\{Voyage, ShippingSchedule};
use App\Enums\ScheduleState;

class CreateFromVoyage
{
    public static function run(Voyage $voyage): ShippingSchedule
    {
        return ShippingSchedule::firstOrCreate(
            ['voyage_id' => $voyage->id],
            [
                'state'        => ScheduleState::Draft,
                'etd'          => $voyage->etd,
                'eta'          => $voyage->eta,
                'vessel_id'    => $voyage->vessel_id,
                'voyage_no'    => $voyage->voyage_no,
                'shipping_line_id' => $voyage->vessel?->shipping_line_id,
            ]
        );
    }
}

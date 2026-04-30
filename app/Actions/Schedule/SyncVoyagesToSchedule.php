<?php

namespace App\Actions\Schedule;

use App\Enums\VoyagePlanState;
use App\Models\Port;
use App\Models\ShippingSchedule;
use App\Models\Voyage;

class SyncVoyagesToSchedule
{
    public static function run(ShippingSchedule|int $schedule): void
    {
        $schedule = $schedule instanceof ShippingSchedule ? $schedule : ShippingSchedule::findOrFail($schedule);

        $polId = Port::query()->where('code', 'IDJKT')->orWhere('name', 'ilike', '%Jakarta%')->value('id');
        $podId = Port::query()->where('code', 'IDBIT')->orWhere('name', 'ilike', '%Bitung%')->orWhere('name', 'ilike', '%Manado%')->value('id');

        foreach ($schedule->items as $it) {
            $voy = Voyage::firstOrCreate(
                [
                    'voyage_no'       => $it->voyage_no,
                    'port_from_id'    => $polId,
                    'port_to_id'      => $podId,
                ],
                [
                    'vessel_id'        => null,
                    'shipping_line_id' => null,
                    'service'          => null,
                ]
            );

            $voy->upsertPlan(
                VoyagePlanState::Final,
                ['etd' => optional($it->etd)?->toDateTimeString(), 'eta' => optional($it->eta)?->toDateTimeString()],
                'sync_from_tam_schedule',
                'tam_email',
                null
            );
        }
    }
}

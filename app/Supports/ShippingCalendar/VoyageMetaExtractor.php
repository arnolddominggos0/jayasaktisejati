<?php

namespace App\Supports\ShippingCalendar;

use App\Supports\ShippingCalendar\DTO\VoyageMeta;

class VoyageMetaExtractor
{
    public function extract($schedule): VoyageMeta
    {
        $v = $schedule->voyage;
        $vs = $v?->vessel;

        return new VoyageMeta([
            'voyage_id' => $v?->id,
            'voyage_no' => $v?->voyage_no,
            'vessel_code' => $vs?->code,
            'vessel_name' => $vs?->name,
            'pol_code' => $v?->pol?->code,
            'pod_code' => $v?->pod?->code,
            'etd' => $v?->etd?->toDateTimeString(),
            'eta' => $v?->eta?->toDateTimeString(),
            'atd' => $v?->atd_at?->toDateTimeString(),
            'ata' => $v?->ata_at?->toDateTimeString(),
            'cargo_plan' => $schedule->cargo_plan ?? 0,
            'is_urgent' => $schedule->is_urgent ?? false,
        ]);
    }
}

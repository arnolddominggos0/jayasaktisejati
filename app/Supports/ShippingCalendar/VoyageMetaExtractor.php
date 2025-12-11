<?php

namespace App\Supports\ShippingCalendar;

use App\Supports\ShippingCalendar\DTO\VoyageMeta;
use Illuminate\Support\Carbon;

class VoyageMetaExtractor
{
    public function extract($schedule): VoyageMeta
    {
        $v = $schedule->voyage;
        $vs = $v?->vessel;
        $data = [
            'voyage_id' => $v?->id ?? null,
            'vessel_id' => $vs?->id ?? null,
            'vessel_name' => $vs?->name ?? null,
            'vessel_code' => $vs?->code ?? null,
            'vessel_imo' => $vs?->imo ?? null,
            'line_code' => $vs?->shippingLine?->code ?? null,
            'line_name' => $vs?->shippingLine?->name ?? null,
            'voyage_no' => $v?->voyage_no ?? null,
            'pol_code' => $v?->pol?->code ?? null,
            'pod_code' => $v?->pod?->code ?? null,
            'etd' => $v?->etd?->toDateTimeString() ?? null,
            'eta' => $v?->eta?->toDateTimeString() ?? null,
            'atd' => $v?->atd_at?->toDateTimeString() ?? null,
            'ata' => $v?->ata_at?->toDateTimeString() ?? null,
            'cargo_plan' => $schedule->cargo_plan ?? 0,
            'is_urgent' => $schedule->is_urgent ?? false,
        ];
        return new VoyageMeta($data);
    }
}

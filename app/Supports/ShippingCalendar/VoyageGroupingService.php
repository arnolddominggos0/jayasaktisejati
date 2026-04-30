<?php

namespace App\Supports\ShippingCalendar;

class VoyageGroupingService
{
    public function group($rows): array
    {
        return collect($rows)->map(function ($s) {
            $v = $s->voyage;

            return [
                'voyage_no'  => $v?->voyage_no,
                'vessel'     => $v?->vessel?->name,
                'etd'        => $v?->etd,
                'eta'        => $v?->eta,
                'atd'        => $v?->atd_at,
                'ata'        => $v?->ata_at,
                'cargo_plan'=> (int) $s->cargo_plan,
            ];
        })->values()->all();
    }
}

<?php

namespace App\Support;

use App\Models\Vessel;

class VesselCode
{
    public static function for(Vessel $vessel): string
    {
        $prefix = strtoupper(substr($vessel->name ?? 'UNK', 0, 4));
        $line   = strtoupper(substr($vessel->shippingLine->code ?? 'XX', 0, 3));
        $count  = Vessel::whereNotNull('code')->count() + 1;

        return sprintf('%s-%s-%03d', $line, $prefix, $count);
    }
}

<?php

namespace App\Services;

use App\Models\VesselPlan;

class WhatsappMessageBuilder
{
    public function buildFullMessage(VesselPlan $plan): string
    {
        $a = $plan->analyze();

        return "Halo TAM,\n\n"
            . "Rencana Jadwal Kapal {$plan->period_month->format('F Y')}\n\n"
            . "KPI:\n"
            . "- Dwelling: {$a['dwelling']} hari\n"
            . "- Sailing: {$a['sailing_avg']} hari\n"
            . "- Dooring: {$a['dooring']} hari\n"
            . "- Total: {$a['total']} hari\n"
            . "- Max Gap: {$a['max_gap']} hari\n\n"
            . "Mohon konfirmasi.\nTerima kasih.";
    }
}
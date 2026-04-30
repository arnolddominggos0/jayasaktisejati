<?php

namespace App\Supports\ShippingCalendar;

use App\Supports\ShippingCalendar\DTO\KpiResult;
use Illuminate\Support\Carbon;

class KpiCalculator
{
    public function calculate(array $schedules, Carbon $start, Carbon $end): KpiResult
    {
        $total = 0;
        $onTime = 0;
        $late = 0;
        $urgent = 0;
        $totalLead = 0;
        $countLead = 0;
        foreach ($schedules as $s) {
            $v = $s->voyage ?? null;
            $etd = $v?->etd;
            $ata = $v?->ata_at;
            if ($etd && $ata) {
                $total++;
                $lead = $etd->diffInDays($ata);
                $totalLead += $lead;
                $countLead++;
                if ($lead <= ($s->kpi_sailing_days ?? 11)) {
                    $onTime++;
                } else {
                    $late++;
                }
            }
            if (!empty($s->is_urgent)) $urgent++;
        }
        $avgLead = $countLead ? round($totalLead / $countLead, 1) : null;
        $completion = $total ? (int) round(($onTime / $total) * 100) : 0;
        return new KpiResult([
            'total' => $total,
            'on_time' => $onTime,
            'late' => $late,
            'urgent' => $urgent,
            'avg_lead_time' => $avgLead,
            'completion' => $completion,
        ]);
    }
}

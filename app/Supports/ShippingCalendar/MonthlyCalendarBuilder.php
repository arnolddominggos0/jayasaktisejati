<?php

namespace App\Supports\ShippingCalendar;

use App\Models\ShippingSchedule;
use Carbon\Carbon;

class MonthlyCalendarBuilder
{
    public function forMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $days = [];
        for ($d = 1; $d <= $start->daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            $days[] = [
                'n' => $d,
                'dow' => strtoupper($date->translatedFormat('D')),
                'isWeekend' => $date->isWeekend(),
            ];
        }

        $lanes = [
            'etd_plan' => 'ETD (Plan)',
            'eta_plan' => 'ETA (Plan)',
            'atd' => 'ATD (Aktual)',
            'ata' => 'ATA (Aktual)',
        ];

        $bucket = [];

        $rows = ShippingSchedule::query()
            ->with('voyage')
            ->whereYear('period_month', $year)
            ->whereMonth('period_month', $month)
            ->get();

        foreach ($rows as $row) {
            $voyage = $row->voyage;

            $map = [
                'etd_plan' => $voyage?->etd,
                'eta_plan' => $voyage?->eta,
                'atd' => $voyage?->atd_at,
                'ata' => $voyage?->ata_at,
            ];

            foreach ($map as $lane => $date) {
                if (! $date) {
                    continue;
                }

                $day = Carbon::parse($date)->day;

                $bucket[$lane][$day][] = [
                    'short' => $voyage?->vessel?->name,
                    'voyage_no' => $voyage?->voyage_no,
                ];
            }
        }

        return [
            'month_label' => $start->translatedFormat('F Y'),
            'days' => $days,
            'days_count' => $start->daysInMonth,
            'lanes' => $lanes,
            'bucket' => $bucket,
        ];
    }
}

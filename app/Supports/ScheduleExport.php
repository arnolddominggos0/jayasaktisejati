<?php

namespace App\Supports;

use App\Models\ShippingSchedule;

class ScheduleExport
{
    public static function rows(ShippingSchedule $schedule): array
    {
        $items = $schedule->items()
            ->with(['vessel'])
            ->orderBy('etd')
            ->get();

        $rows = [];

        foreach ($items as $it) {
            $extra = is_array($it->extra) ? $it->extra : [];

            if (!empty($extra['lts'] ?? null) && empty($extra['jss'] ?? null)) {
                continue;
            }

            $rows[] = [
                'etd'             => $it->etd?->format('d/m/Y H:i'),
                'eta'             => $it->eta?->format('d/m/Y H:i'),
                'cargo_plan'      => $extra['cargo_plan']       ?? '',
                'vessel'          => $it->vessel?->name         ?? '',
                'vessel_capacity' => $extra['vessel_capacity']  ?? ($extra['capacity'] ?? ''),
                'voyage_no'       => $it->voyage_no             ?? '',
                'jss'             => $extra['jss']              ?? '',
                'dwelling'        => $extra['dwelling']         ?? '',
            ];
        }

        return $rows;
    }

    public static function csv(ShippingSchedule $schedule): string
    {
        $rows = self::rows($schedule);

        $headers = [
            'No',
            'ETD',
            'ETA',
            'Cargo Plan',
            'Vessel',
            'Vessel Capacity',
            'Voyage No',
            'JSS',
            'Dwelling',
        ];

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);

        $i = 1;
        foreach ($rows as $r) {
            fputcsv($out, [
                $i++,
                $r['etd'],
                $r['eta'],
                $r['cargo_plan'],
                $r['vessel'],
                $r['vessel_capacity'],
                $r['voyage_no'],
                $r['jss'],
                $r['dwelling'],
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}

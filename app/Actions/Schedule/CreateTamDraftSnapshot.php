<?php

namespace App\Actions\Schedule;

use App\Exports\TamScheduleExport;
use App\Models\ShippingSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CreateTamDraftSnapshot
{
    public static function run(ShippingSchedule $schedule): ShippingSchedule
    {
        $voyage = $schedule->voyage()->with(['vessel.shippingLine', 'pol', 'pod'])->first();

        $row = [
            'Shipping Line' => $voyage?->vessel?->shippingLine?->name,
            'Vessel'        => $voyage?->vessel?->name ?? $schedule->vessel_name,
            'Voyage'        => $voyage?->voyage_no ?? $schedule->voyage_no,
            'POL'           => $voyage?->pol?->code,
            'POD'           => $voyage?->pod?->code,
            'ETD (Plan)'    => ($schedule->etd ?? $voyage?->etd)?->format('Y-m-d H:i'),
            'ETA (Plan)'    => ($schedule->eta ?? $voyage?->eta)?->format('Y-m-d H:i'),
        ];

        $payload = [$row];

        DB::transaction(function () use ($schedule, $payload) {
            $schedule->update([
                'tam_payload'      => $payload,
                'tam_version'      => 'v1.0',
                'tam_generated_at' => now(),
            ]);
        });

        $headings = [
            'Shipping Line',
            'Vessel',
            'Voyage',
            'POL',
            'POD',
            'ETD (Plan)',
            'ETA (Plan)',
        ];

        $filename = 'tam-draft-'
            . ($schedule->voyage?->voyage_no ? Str::slug($schedule->voyage->voyage_no) . '-' : '')
            . now()->format('YmdHis')
            . '.xlsx';

        $path = "exports/schedules/{$filename}";

        Excel::store(
            new TamScheduleExport($payload, $headings, 'Draft ' . $schedule->voyage?->voyage_no ?? 'Schedule'),
            $path,
            'public'
        );

        $schedule->forceFill(['tam_draft_path' => $path])->saveQuietly();

        return $schedule->fresh();
    }
}

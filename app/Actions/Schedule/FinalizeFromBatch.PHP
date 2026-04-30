<?php

namespace App\Actions\Schedule;

use App\Enums\ScheduleState;
use App\Exports\TamScheduleExport;
use App\Models\ShippingSchedule;
use App\Supports\Kpi\TamMonthlyKpi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class FinalizeFromBatch
{
    public static function run(TamMonthlyKpi $batch, string $approvedBy = 'System'): TamMonthlyKpi
    {
        $ids = $batch->schedule_ids ?? [];
        if (empty($ids)) {
            return $batch;
        }

        DB::transaction(function () use ($ids, $approvedBy) {
            $schedules = ShippingSchedule::whereIn('id', $ids)->get();

            foreach ($schedules as $s) {
                if (($s->cargo_plan ?? 0) <= 0) {
                    continue;
                }

                $s->forceFill([
                    'state' => ScheduleState::Final,
                    'finalized_at' => now(),
                    'approved_by_name' => $approvedBy,
                    'final_source' => $s->final_source ?: 'TAM Monthly Meeting',
                ])->save();
            }
        });

        $schedules = ShippingSchedule::whereIn('id', $ids)
            ->where('state', ScheduleState::Final)
            ->with(['voyage.vessel.shippingLine', 'voyage.pol', 'voyage.pod'])
            ->orderBy('etd')
            ->get();

        $rows = [];
        $totalPlan = 0;

        foreach ($schedules as $s) {
            $v = $s->voyage;
            $line = $v?->vessel?->shippingLine?->name;
            $vessel = $v?->vessel?->name ?? $s->vessel_name;
            $voyage = $v?->voyage_no ?? $s->voyage_no;
            $pol = $v?->pol?->code;
            $pod = $v?->pod?->code;
            $etdPlan = ($s->etd ?? $v?->etd)?->format('Y-m-d H:i');
            $etaPlan = ($s->eta ?? $v?->eta)?->format('Y-m-d H:i');

            $rows[] = [
                'Shipping Line' => $line,
                'Vessel' => $vessel,
                'Voyage' => $voyage,
                'POL' => $pol,
                'POD' => $pod,
                'ETD (Plan)' => $etdPlan,
                'ETA (Plan)' => $etaPlan,
                'Cargo Plan' => (int) ($s->cargo_plan ?? 0),
                'Remarks' => $s->final_note ?? '',
            ];

            $totalPlan += (int) ($s->cargo_plan ?? 0);
        }

        $headings = [
            'Shipping Line',
            'Vessel',
            'Voyage',
            'POL',
            'POD',
            'ETD (Plan)',
            'ETA (Plan)',
            'Cargo Plan',
            'Remarks',
        ];

        if (! empty($rows)) {
            $file = 'tam-final-' . $batch->period_month->format('Ym') . '-' . Str::random(6) . '.xlsx';
            $path = "exports/tam-monthly/{$file}";

            Excel::store(
                new TamScheduleExport($rows, $headings, 'Final ' . $batch->period_month->format('F Y')),
                $path,
                'public'
            );

            $batch->forceFill([
                'final_path' => $path,
                'total_plan' => $totalPlan,
                'status' => 'final',
                'finalized_at' => now(),
                'approved_by_name' => $approvedBy,
                'payload' => $rows,
            ])->save();
        }

        return $batch->fresh();
    }
}

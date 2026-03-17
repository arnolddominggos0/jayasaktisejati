<?php

namespace App\Actions\Schedule;

use App\Exports\TamScheduleExport;
use App\Models\ShippingSchedule;
use App\Supports\Kpi\TamMonthlyKpi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BuildMonthlyDraft
{
    public static function run(int $year, int $month, string $version = 'v1.0'): TamMonthlyKpi
    {
        $period = Carbon::create($year, $month, 1)->startOfDay();

        $batch = TamMonthlyKpi::firstOrNew(['period_month' => $period]);
        $batch->version = $version;

        $rows = [];
        $ids = [];

        $schedules = ShippingSchedule::query()
            ->where('state', 'draft')
            ->overlapsMonth($year, $month)
            ->with(['voyage.vessel.shippingLine', 'voyage.pol', 'voyage.pod'])
            ->orderBy('etd')
            ->get();

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
            ];

            $ids[] = $s->id;
        }

        $headings = [
            'Shipping Line',
            'Vessel',
            'Voyage',
            'POL',
            'POD',
            'ETD (Plan)',
            'ETA (Plan)',
        ];

        DB::transaction(function () use ($batch, $rows, $ids, $version) {
            $batch->fill([
                'payload' => $rows,
                'schedule_ids' => $ids,
                'total_plan' => 0,
                'status' => 'draft',
                'generated_at' => now(),
                'generated_by_name' => auth_user()->name ?? 'System',
                'version' => $version,
            ])->save();
        });

        if (! empty($rows)) {
            $file = 'tam-draft-' . $period->format('Ym') . '-' . Str::random(6) . '.xlsx';
            $path = "exports/tam-monthly/{$file}";

            Excel::store(
                new TamScheduleExport($rows, $headings, 'Draft ' . $period->format('F Y')),
                $path,
                'public'
            );

            $batch->forceFill(['draft_path' => $path])->saveQuietly();
        }

        $lines = [];
        foreach ($rows as $r) {
            $parts = array_filter([
                $r['Vessel'],
                $r['Voyage'] ? 'V.' . $r['Voyage'] : null,
                $r['ETD (Plan)'] ? 'ETD ' . Carbon::parse($r['ETD (Plan)'])->format('d-m-Y') : null,
                ($r['POL'] && $r['POD']) ? $r['POL'] . ' → ' . $r['POD'] : null,
            ]);
            $lines[] = '• ' . implode(' ', $parts);
        }

        $monthLabel = $period->translatedFormat('F Y');
        $message = "Selamat pagi Pak,\n" .
            "Terlampir jadwal kapal tentatif bulan {$monthLabel}:\n\n" .
            implode("\n", $lines) .
            "\n\nJadwal dapat berubah sewaktu-waktu.\nTerima kasih.";

        $batch->update(['draft_message' => $message]);

        return $batch->fresh();
    }
}

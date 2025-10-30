<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingLine;
use App\Models\Vessel;
use App\Models\ShippingSchedule;
use App\Enums\ScheduleState;

class ShippingScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Buat Shipping Line dasar
        $tanto = ShippingLine::firstOrCreate(
            ['name' => 'Tanto Line'],
            ['code' => 'TANTO', 'email' => 'info@tanto.co.id']
        );

        $meratus = ShippingLine::firstOrCreate(
            ['name' => 'Meratus Line'],
            ['code' => 'MERATUS', 'email' => 'info@meratus.co.id']
        );

        // 2) Buat Vessel berdasarkan Shipping Line
        $vessels = [
            'KM Tanto Salam'     => $tanto->id,
            'KM Tanto Jaya'      => $tanto->id,
            'KM Tanto Sejahtera' => $tanto->id,
            'KM Meratus Malino'  => $meratus->id,
        ];

        $vesselMap = collect($vessels)->mapWithKeys(function ($lineId, $name) {
            $vessel = Vessel::firstOrCreate([
                'shipping_line_id' => $lineId,
                'name' => $name,
            ]);
            return [$name => $vessel->id];
        });

        // 3) Generate jadwal per bulan (past & future)
        $months = collect([-2, -1, 0, 1, 2])
            ->map(fn($o) => now()->startOfMonth()->addMonths($o));

        foreach ($months as $m) {
            $code = 'JSS-SCH-' . $m->format('Ym') . '-MND';

            // Ambil vessel pertama dari list buat pengisian ETD/ETA awal
            $firstVesselName = 'KM Tanto Salam';
            $firstVesselId   = $vesselMap[$firstVesselName] ?? null;
            $firstLineId     = $tanto->id;

            $schedule = ShippingSchedule::updateOrCreate(
                ['code' => $code],
                [
                    'shipping_line_id' => $firstLineId,
                    'vessel_id'        => $firstVesselId,
                    'state'            => ScheduleState::Final->value,
                    'final_source'     => 'email',
                    'approved_by_name' => 'TAM Logistics',
                    'approved_at'      => $m->copy()->subDays(5),
                    'final_email_from' => 'tam-logistics@toyota.co.id',
                    'final_email_subject' => 'Final Schedule ' . $m->translatedFormat('F Y'),
                    'final_email_received_at' => $m->copy()->subDays(5)->setTime(9, 0),
                ]
            );

            $schedule->items()->delete();

            $schedule->items()->createMany([
                [
                    'etd' => $m->copy()->addDays(2),
                    'eta' => $m->copy()->addDays(10),
                    'cargo_plan' => 7,
                    'vessel_name' => 'KM Tanto Salam',
                    'vessel_capacity' => 932,
                    'voyage_no' => '151',
                    'jss' => 'JSS',
                    'dwelling' => 5,
                ],
                [
                    'etd' => $m->copy()->addDays(8),
                    'eta' => $m->copy()->addDays(18),
                    'cargo_plan' => 12,
                    'vessel_name' => 'KM Meratus Malino',
                    'vessel_capacity' => 800,
                    'voyage_no' => '182',
                    'jss' => 'JSS',
                    'dwelling' => 1,
                ],
                [
                    'etd' => $m->copy()->addDays(14),
                    'eta' => $m->copy()->addDays(22),
                    'cargo_plan' => 6,
                    'vessel_name' => 'KM Tanto Jaya',
                    'vessel_capacity' => 1060,
                    'voyage_no' => '301',
                    'jss' => 'JSS',
                    'dwelling' => 3,
                ],
            ]);

            $first = $schedule->items()->orderBy('etd')->first();
            $schedule->update([
                'etd' => $first?->etd,
                'eta' => $first?->eta,
                'vessel_id' => $vesselMap[$first?->vessel_name] ?? $firstVesselId,
                'shipping_line_id' => $firstVesselId
                    ? Vessel::find($firstVesselId)?->shipping_line_id
                    : $firstLineId,
                'voyage_no' => $first?->voyage_no,
                'cargo_plan_total' => (int) $schedule->items()->sum('cargo_plan'),
            ]);
        }

        // 4) Draft bulan depan
        $draftMonth = now()->addMonthNoOverflow()->startOfMonth();
        $draftVessel = 'KM Tanto Sejahtera';
        $draftVesselId = $vesselMap[$draftVessel] ?? null;

        $draft = ShippingSchedule::updateOrCreate(
            ['code' => 'JSS-SCH-' . $draftMonth->format('Ym') . '-DRAFT'],
            [
                'shipping_line_id' => $tanto->id,
                'vessel_id' => $draftVesselId,
                'state' => ScheduleState::Draft->value,
                'etd' => $draftMonth->copy()->addDays(3)->setTime(8, 0),
                'eta' => $draftMonth->copy()->addDays(12)->setTime(15, 0),
                'cargo_plan_total' => 10,
            ]
        );

        $draft->items()->delete();
        $draft->items()->create([
            'etd' => $draftMonth->copy()->addDays(3),
            'eta' => $draftMonth->copy()->addDays(12),
            'cargo_plan' => 10,
            'vessel_name' => $draftVessel,
            'vessel_capacity' => 932,
            'voyage_no' => '146',
            'jss' => 'JSS',
            'dwelling' => 5,
        ]);
    }
}

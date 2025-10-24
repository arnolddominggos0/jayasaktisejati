<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingSchedule;
use App\Enums\ScheduleState;

class ShippingScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $months = collect([-2, -1, 0, 1, 2])->map(fn($o) => now()->startOfMonth()->addMonths($o));

        foreach ($months as $m) {
            $code = 'JSS-SCH-' . $m->format('Ym') . '-MND';

            $schedule = ShippingSchedule::updateOrCreate(
                ['code' => $code],
                [
                    'state' => ScheduleState::Final->value,
                    'final_source' => 'email',
                    'approved_by_name' => 'TAM Logistics',
                    'approved_at' => $m->copy()->subDays(5),
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
                    'service' => 'FCL',
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
                    'service' => 'FCL',
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
                    'service' => 'FCL',
                ],
            ]);

            $first = $schedule->items()->orderBy('etd')->first();
            $schedule->update([
                'etd' => $first?->etd,
                'eta' => $first?->eta,
                'vessel_name' => $first?->vessel_name,
                'voyage_no' => $first?->voyage_no,
                'cargo_plan_total' => (int) $schedule->items()->sum('cargo_plan'),
            ]);
        }

        $draftMonth = now()->addMonthNoOverflow()->startOfMonth();

        $draft = ShippingSchedule::updateOrCreate(
            ['code' => 'JSS-SCH-' . $draftMonth->format('Ym') . '-DRAFT'],
            [
                'state' => ScheduleState::Draft->value,
                'vessel_name' => 'KM Tanto Sejahtera',
                'voyage_no' => '146',
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
            'vessel_name' => 'KM Tanto Sejahtera',
            'vessel_capacity' => 932,
            'voyage_no' => '146',
            'jss' => 'JSS',
            'dwelling' => 5,
            'service' => 'FCL',
        ]);
    }
}

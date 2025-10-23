<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\ShippingSchedule;
use App\Enums\ScheduleState;

class ShippingScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 5 bulan arsip: -2,-1,0,+1,+2
        $months = collect([-2, -1, 0, 1, 2])->map(fn($o) => now()->startOfMonth()->addMonths($o));

        foreach ($months as $m) {
            $code = 'JSS-SCH-' . $m->format('Ym') . '-MND';

            $sch = ShippingSchedule::updateOrCreate(
                ['code' => $code],
                [
                    'state' => ScheduleState::Final->value,
                    'period_month' => $m->toDateString(),
                    'final_source' => 'email',
                    'approved_by_name' => 'TAM Logistics',
                    'approved_at' => $m->copy()->subDays(5),
                    'final_email_from' => 'tam-logistics@toyota.co.id',
                    'final_email_subject' => 'Final Schedule ' . $m->translatedFormat('F Y'),
                    'final_email_received_at' => $m->copy()->subDays(5)->setTime(9, 0),
                ]
            );

            // bersihkan items lama lalu isi contoh item
            $sch->items()->delete();

            $sch->items()->createMany([
                [
                    'etd' => $m->copy()->addDays(2),
                    'eta' => $m->copy()->addDays(10),
                    'cargo_plan' => 7,
                    'vessel_name' => 'KM Tanto Salam',
                    'vessel_capacity' => 932,
                    'voyage_no' => '151',
                    'jss' => 'VOY151TSLMNDJSS',
                    'lts' => 'VOY151TSLMNDLTS',
                    'dwelling' => 5,
                ],
                [
                    'etd' => $m->copy()->addDays(8),
                    'eta' => $m->copy()->addDays(18),
                    'cargo_plan' => 12,
                    'vessel_name' => 'KM Meratus Malino',
                    'vessel_capacity' => 800,
                    'voyage_no' => '182',
                    'jss' => 'VOY182MMLMNDJSS',
                    'lts' => 'VOY182MMLMNDLTS',
                    'dwelling' => 1,
                ],
                [
                    'etd' => $m->copy()->addDays(14),
                    'eta' => $m->copy()->addDays(22),
                    'cargo_plan' => 6,
                    'vessel_name' => 'KM Tanto Jaya',
                    'vessel_capacity' => 1060,
                    'voyage_no' => '301',
                    'jss' => 'VOY301TTJMNDJSS',
                    'lts' => 'VOY301TTJMNDLTS',
                    'dwelling' => 3,
                ],
            ]);

            // ringkasan jadwal dari baris pertama
            $first = $sch->items()->orderBy('etd')->first();
            $sch->update([
                'etd' => $first?->etd,
                'eta' => $first?->eta,
                'vessel_name' => $first?->vessel_name,
                'voyage_no' => $first?->voyage_no,
                'cargo_plan_total' => (int) $sch->items()->sum('cargo_plan'),
            ]);
        }

        // satu draft buat uji finalisasi manual
        $draftMonth = now()->addMonthNoOverflow()->startOfMonth();
        $draft = ShippingSchedule::updateOrCreate(
            ['code' => 'JSS-SCH-' . $draftMonth->format('Ym') . '-DRAFT'],
            [
                'state' => ScheduleState::Draft->value,
                'period_month' => $draftMonth->toDateString(),
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
            'jss' => 'VOY146TSAMNDJSS',
            'lts' => 'VOY146TSAMNDLTS',
            'dwelling' => 5,
        ]);

        $this->command?->info('✅ ShippingScheduleSeeder: 5 jadwal final + 1 draft dibuat.');
    }
}

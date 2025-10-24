<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< HEAD
use Illuminate\Support\Carbon;
use App\Models\ShippingSchedule;
use App\Enums\ScheduleState;
=======
use Illuminate\Support\Facades\Schema;
use App\Models\{
    ShippingSchedule,
    Customer,
    Port,
    ShippingLine,
    Vessel,
    ShippingScheduleItem
};
use App\Enums\ScheduleState;
use Carbon\Carbon;
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47

class ShippingScheduleSeeder extends Seeder
{
    public function run(): void
    {
<<<<<<< HEAD
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
=======
        // 1) Master minimal: Customer TAM, POL Priok, POD Bitung/Manado
        $customer = Customer::where('code', 'CTM-0236')->first()
            ?? Customer::factory()->create([
                'code' => 'CTM-0236',
                'name' => 'Toyota Astra Motor',
            ]);

        $pol = Port::where('code', 'IDTPP')
            ->orWhere('name', 'ilike', '%Tanjung Priok%')
            ->first()
            ?? Port::create(['code' => 'IDTPP', 'name' => 'Pelabuhan Tanjung Priok']);

        // Catatan: umumnya Bitung = IDBIT. Ganti ke IDMDC kalau DB kamu memang pake itu.
        $pod = Port::where('code', 'IDBIT')
            ->orWhere('name', 'ilike', '%Bitung%')
            ->orWhere('name', 'ilike', '%Manado%')
            ->first()
            ?? Port::create(['code' => 'IDBIT', 'name' => 'Pelabuhan Bitung']);

        // 2) Dua pelayaran: Tanto & Meratus
        $tanto = ShippingLine::where('code', 'TL')->first()
            ?? ShippingLine::create(['code' => 'TL', 'name' => 'Tanto Line']);

        $meratus = ShippingLine::where('code', 'MR')->first()
            ?? ShippingLine::create(['code' => 'MR', 'name' => 'Meratus Line']);

        // 3) Kapal per line
        $tantoVesselAttrs = ['name' => 'KM Tanto Sejahtera'];
        if (Schema::hasColumn('vessels', 'shipping_line_id')) {
            $tantoVesselAttrs['shipping_line_id'] = $tanto->id;
        }
        $tantoVessel = Vessel::where('name', $tantoVesselAttrs['name'])->first()
            ?? Vessel::create($tantoVesselAttrs);

        $meratusVesselAttrs = ['name' => 'KM Meratus Malino'];
        if (Schema::hasColumn('vessels', 'shipping_line_id')) {
            $meratusVesselAttrs['shipping_line_id'] = $meratus->id;
        }
        $meratusVessel = Vessel::where('name', $meratusVesselAttrs['name'])->first()
            ?? Vessel::create($meratusVesselAttrs);

        // 4) Buat paket Draft untuk periode bulan ini (judul jangan ngaku "Final")
        $period = now()->format('Y-m');

        $schedule = ShippingSchedule::firstOrCreate(
            [
                'period_ym'  => $period,
                'customer_id' => $customer->id,
                'pol_id'     => $pol->id,
                'pod_id'     => $pod->id,
            ],
            [
                'title'  => 'Draft Jadwal Kapal ' . $period . ' (TAM)',
                'notes'  => 'Seed draft untuk KPI TAM (JKT → MDO/Bitung)',
                'state'  => ScheduleState::Draft->value,
            ]
        );

        // Pastikan tetap Draft (kalau seeder sebelumnya pernah bikin Final, kita balikin ke Draft)
        if ($schedule->state !== ScheduleState::Draft->value) {
            $schedule->forceFill([
                'state' => ScheduleState::Draft->value,
                'finalized_at' => null,
                'final_source' => null,
                'approved_by_name' => null,
                'approved_at' => null,
                'final_note' => null,
            ])->save();
        }

        // 5) Baris Draft: mix Tanto & Meratus, ETD sepanjang bulan ini
        //    Draft itu wajar belum punya JSS/VOY fix; kita isi perkiraan.
        $start = Carbon::now()->startOfMonth();

        $draftRows = [
            // Tanto
            [
                'line' => $tanto,
                'vessel' => $tantoVessel,
                'offset' => 2,
                'sail' => 8,
                'voy' => '179',
                'service' => 'Direct',
                'cargo' => 7,
                'cap' => 800
            ],
            [
                'line' => $tanto,
                'vessel' => $tantoVessel,
                'offset' => 10,
                'sail' => 9,
                'voy' => '182',
                'service' => 'Direct',
                'cargo' => 6,
                'cap' => 800
            ],

            // Meratus
            [
                'line' => $meratus,
                'vessel' => $meratusVessel,
                'offset' => 5,
                'sail' => 8,
                'voy' => '234',
                'service' => 'Via Bitung',
                'cargo' => 5,
                'cap' => 820
            ],
            [
                'line' => $meratus,
                'vessel' => $meratusVessel,
                'offset' => 18,
                'sail' => 7,
                'voy' => '241',
                'service' => 'Via Bitung',
                'cargo' => 9,
                'cap' => 820
            ],
        ];

        foreach ($draftRows as $r) {
            $etd = (clone $start)->addDays($r['offset'])->setTime(12, 0, 0);
            $eta = (clone $etd)->addDays($r['sail'])->setTime(9, 0, 0);

            // Kunci lookup draf:
            // Jangan pakai detik supaya idempotent saat seeder diulang
            $lookup = [
                'schedule_id' => $schedule->id,
                'vessel_id'   => $r['vessel']->id,
                'voyage_no'   => (string) $r['voy'],
                'pol_id'      => $pol->id,
                'pod_id'      => $pod->id,
                'etd'         => $etd->copy()->seconds(0), // lock ke menit
            ];

            $payload = [
                'shipping_line_id' => $r['line']->id,
                'service'          => $r['service'],
                'eta'              => $eta->copy()->seconds(0),
                // Draft: biarkan kolom 'jss' kosong. Itu baru muncul saat final.
                'jss'              => null,
                'extra'            => [
                    'vessel_capacity' => $r['cap'],
                    'cargo_plan'      => $r['cargo'],
                    'voyage_no'       => (string) $r['voy'],
                    'dwelling'        => $r['sail'] . ' days (est)',
                    'direct'          => str_contains(strtolower($r['service']), 'direct'),
                ],
            ];

            $item = ShippingScheduleItem::where($lookup)->first();
            if ($item) {
                $item->fill($payload)->save();
            } else {
                // merge lookup + payload untuk create
                $schedule->items()->create($lookup + $payload);
            }
        }
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
    }
}

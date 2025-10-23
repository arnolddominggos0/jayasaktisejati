<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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

class ShippingScheduleSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}

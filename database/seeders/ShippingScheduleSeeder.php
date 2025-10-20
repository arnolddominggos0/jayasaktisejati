<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\{
    ShippingSchedule,
    Customer,
    Port,
    ShippingLine,
    Vessel
};
use App\Enums\ScheduleState;
use Carbon\Carbon;

class ShippingScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::first() ?? Customer::factory()->create(['name' => 'Toyota Astra Motor']);

        $pol = Port::where('code', 'IDTPP')->orWhere('name', 'Pelabuhan Tanjung Priok')->first()
            ?? Port::create(['code' => 'IDTPP', 'name' => 'Pelabuhan Tanjung Priok']);

        $pod = Port::where('code', 'IDMDC')->orWhere('name', 'Pelabuhan Bitung')->first()
            ?? Port::create(['code' => 'IDMDC', 'name' => 'Pelabuhan Bitung']);

        $line = ShippingLine::first() ?? ShippingLine::create(['code' => 'TL', 'name' => 'Tanto Line']);

        $vesselAttrs = ['name' => 'KM Tanto Sejahtera'];
        if (Schema::hasColumn('vessels', 'shipping_line_id')) {
            $vesselAttrs['shipping_line_id'] = $line->id;
        }
        $vessel = Vessel::where('name', $vesselAttrs['name'])->first() ?? Vessel::create($vesselAttrs);

        $period = now()->format('Y-m');

        $schedule = ShippingSchedule::firstOrCreate(
            ['period_ym' => $period, 'customer_id' => $customer->id],
            [
                'title'     => 'Draft Jadwal Kapal ' . $period,
                'pol_id'    => $pol->id,
                'pod_id'    => $pod->id,
                'notes'     => 'Seeded data jadwal bulan ' . $period,
                'state'     => ScheduleState::Draft->value,
            ]
        );

        if ($schedule->items()->exists()) {
            return;
        }

        $start = Carbon::now()->startOfMonth();
        $rows = [
            ['offset' => 2,  'sail' => 8, 'voy' => 'VOY' . rand(100, 199), 'service' => 'REG'],
            ['offset' => 10, 'sail' => 9, 'voy' => 'VOY' . rand(200, 299), 'service' => 'REG'],
            ['offset' => 18, 'sail' => 7, 'voy' => 'VOY' . rand(300, 399), 'service' => 'EXP'],
        ];

        foreach ($rows as $r) {
            $etd = (clone $start)->addDays($r['offset'])->setTime(12, 0, 0);
            $eta = (clone $etd)->addDays($r['sail'])->setTime(9, 0, 0);

            $schedule->items()->create([
                'shipping_line_id' => $line->id,
                'vessel_id'        => $vessel->id,
                'voyage_no'        => $r['voy'],
                'service'          => $r['service'],
                'etd'              => $etd,
                'eta'              => $eta,
                'pol_id'           => $pol->id,
                'pod_id'           => $pod->id,
                'extra'            => [
                    'vessel_capacity' => rand(880, 1000),
                    'cargo_plan'      => rand(5, 12),
                    'voyage_no'       => $r['voy'],
                    'dwelling'        => '' . $r['sail'] . ' days',
                ],
            ]);
        }
    }
}

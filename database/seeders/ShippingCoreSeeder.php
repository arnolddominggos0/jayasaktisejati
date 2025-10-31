<?php

namespace Database\Seeders;

use App\Enums\ScheduleState;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\ShippingSchedule;
use App\Models\Vessel;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShippingCoreSeeder extends Seeder
{
    public function run(): void
    {
        $meratus = ShippingLine::firstOrCreate(
            ['code' => 'MTS'],
            ['name' => 'Meratus Line']
        );

        $tanto = ShippingLine::firstOrCreate(
            ['code' => 'TNT'],
            ['name' => 'Tanto Intim Line']
        );

        $jakarta = Port::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Pelabuhan Tanjung Priok', 'city' => 'Jakarta']
        );

        $manado = Port::firstOrCreate(
            ['code' => 'MND'],
            ['name' => 'Pelabuhan Bitung', 'city' => 'Manado']
        );

        $surabaya = Port::firstOrCreate(
            ['code' => 'SBY'],
            ['name' => 'Pelabuhan Tanjung Perak', 'city' => 'Surabaya']
        );

        $meratusVessel = Vessel::firstOrCreate(
            ['shipping_line_id' => $meratus->id, 'name' => 'KM Meratus Gorontalo'],
            ['code' => 'MTS-GRT', 'capacity' => 800]
        );

        $tantoVessel = Vessel::firstOrCreate(
            ['shipping_line_id' => $tanto->id, 'name' => 'KM Tanto Salam'],
            ['code' => 'TNT-SLM', 'capacity' => 900]
        );



        $voyages = [
            [
                'vessel' => $meratusVessel,
                'pol' => $surabaya,
                'pod' => $manado,
                'voyage_no' => '179',
                'etd' => Carbon::now()->startOfMonth()->addDays(3)->setTime(8, 0),
                'eta' => Carbon::now()->startOfMonth()->addDays(11)->setTime(14, 0),
                'service' => 'REG'
            ],
            [
                'vessel' => $tantoVessel,
                'pol' => $jakarta,
                'pod' => $manado,
                'voyage_no' => '151',
                'etd' => Carbon::now()->startOfMonth()->addDays(8)->setTime(9, 0),
                'eta' => Carbon::now()->startOfMonth()->addDays(19)->setTime(15, 0),
                'service' => 'TAM'
            ],
            [
                'vessel' => $meratusVessel,
                'pol' => $manado,
                'pod' => $surabaya,
                'voyage_no' => '180',
                'etd' => Carbon::now()->startOfMonth()->addDays(20)->setTime(7, 0),
                'eta' => Carbon::now()->startOfMonth()->addDays(28)->setTime(12, 0),
                'service' => 'RETURN'
            ],
        ];

        foreach ($voyages as $v) {
            Voyage::firstOrCreate(
                [
                    'vessel_id' => $v['vessel']->id,
                    'voyage_no' => $v['voyage_no'],
                    'pol_id' => $v['pol']->id,
                    'pod_id' => $v['pod']->id,
                ],
                [
                    'service' => $v['service'],
                    'etd' => $v['etd'],
                    'eta' => $v['eta'],
                ]
            );
        }


        $voyageMeratus = Voyage::where('voyage_no', '179')->first();
        $voyageTanto = Voyage::where('voyage_no', '151')->first();
        $voyageReturn = Voyage::where('voyage_no', '180')->first();

        $schedules = [
            [
                'voyage' => $voyageMeratus,
                'cargo_plan' => 7,
                'approved_by_name' => 'TAM Logistics',
                'final_note' => 'Schedule sesuai rencana awal.',
                'state' => ScheduleState::Final,
            ],
            [
                'voyage' => $voyageTanto,
                'cargo_plan' => 9,
                'approved_by_name' => 'TAM Logistics',
                'final_note' => 'Menunggu konfirmasi slot tambahan.',
                'state' => ScheduleState::Final,
            ],
            [
                'voyage' => $voyageReturn,
                'cargo_plan' => 6,
                'approved_by_name' => 'JSS Operation',
                'final_note' => 'Trip balik muatan kosong sebagian.',
                'state' => ScheduleState::Final,
            ],
        ];

        foreach ($schedules as $s) {
            ShippingSchedule::firstOrCreate(
                ['voyage_id' => $s['voyage']->id],
                [
                    'cargo_plan' => $s['cargo_plan'],
                    'state' => $s['state'],
                    'approved_by_name' => $s['approved_by_name'],
                    'final_note' => $s['final_note'],
                    'finalized_at' => Carbon::now(),
                ]
            );
        }
    }
}

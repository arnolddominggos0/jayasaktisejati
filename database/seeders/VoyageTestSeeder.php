<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\Voyage;
use App\Models\Vessel;
use App\Models\ShippingLine;
use App\Models\Port;
use App\Enums\VoyagePlanState;

class VoyageTestSeeder extends Seeder
{
    public function run(): void
    {
        $ttsa = ShippingLine::updateOrCreate(
            ['code' => 'TTSA'],
            ['name' => 'Tanto Shipping Line']
        );

        $mrma = ShippingLine::updateOrCreate(
            ['code' => 'MRMA'],
            ['name' => 'Meratus Line']
        );

        $pol = Port::updateOrCreate(
            ['code' => 'IDJKT'],
            ['name' => 'Jakarta Port']
        );

        $pod = Port::updateOrCreate(
            ['code' => 'IDBIT'],
            ['name' => 'Bitung Port']
        );

        $v1 = Vessel::updateOrCreate(
            ['name' => 'KM Tanto Salam'],
            ['shipping_line_id' => $ttsa->id]
        );

        $v2 = Vessel::updateOrCreate(
            ['name' => 'KM Meratus Malino'],
            ['shipping_line_id' => $mrma->id]
        );

        $v3 = Vessel::updateOrCreate(
            ['name' => 'KM Tanto Jaya'],
            ['shipping_line_id' => $ttsa->id]
        );

        $voyages = [
            [
                'vessel_id'        => $v1->id,
                'shipping_line_id' => $ttsa->id,
                'voyage_no'        => '151',
                'port_from_id'     => $pol->id,
                'port_to_id'       => $pod->id,
                'service'          => 'Regular',
                'etd'              => Carbon::create(2025, 10, 3, 0, 0),
                'eta'              => Carbon::create(2025, 10, 11, 0, 0),
                'notes'            => 'Voyage regular TAM',
            ],
            [
                'vessel_id'        => $v2->id,
                'shipping_line_id' => $mrma->id,
                'voyage_no'        => '182',
                'port_from_id'     => $pol->id,
                'port_to_id'       => $pod->id,
                'service'          => 'Regular',
                'etd'              => Carbon::create(2025, 10, 9, 0, 0),
                'eta'              => Carbon::create(2025, 10, 19, 0, 0),
                'notes'            => 'Voyage tambahan load full',
            ],
            [
                'vessel_id'        => $v3->id,
                'shipping_line_id' => $ttsa->id,
                'voyage_no'        => '301',
                'port_from_id'     => $pol->id,
                'port_to_id'       => $pod->id,
                'service'          => 'Regular',
                'etd'              => Carbon::create(2025, 10, 15, 0, 0),
                'eta'              => Carbon::create(2025, 10, 23, 0, 0),
                'notes'            => 'Voyage pengganti kapal sebelumnya',
            ],
        ];

        foreach ($voyages as $d) {
            $voy = Voyage::updateOrCreate(
                [
                    'vessel_id'     => $d['vessel_id'],
                    'voyage_no'     => $d['voyage_no'],
                    'port_from_id'  => $d['port_from_id'],
                    'port_to_id'    => $d['port_to_id'],
                ],
                [
                    'shipping_line_id' => $d['shipping_line_id'],
                    'service'          => $d['service'],
                    'etd'              => $d['etd'],
                    'eta'              => $d['eta'],
                ]
            );

            $voy->upsertPlan(
                VoyagePlanState::Final,
                [
                    'etd' => $d['etd']->toDateTimeString(),
                    'eta' => $d['eta']->toDateTimeString(),
                ],
                $d['notes'],
                'manual',
                null
            );
        }
    }
}

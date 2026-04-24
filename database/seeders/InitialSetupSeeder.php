<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\MPDomain;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Manpower;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdn = Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        $fcJkt = User::where('email', 'koor.jkt@jss.local')->firstOrFail();
        $fcMdo = User::where('email', 'koor.mdo@jss.local')->firstOrFail();

        $cityNames = ['Jakarta', 'Manado', 'Surabaya', 'Makassar'];
        foreach ($cityNames as $name) {
            City::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'country' => 'Indonesia']
            );
        }

        $cityIds = City::pluck('id')->toArray();

        $ports = [
            ['code' => 'IDJKT', 'name' => 'Tanjung Priok', 'city' => 'Jakarta'],
            ['code' => 'IDMDO', 'name' => 'Bitung Port', 'city' => 'Manado'],
            ['code' => 'IDSUB', 'name' => 'Tanjung Perak', 'city' => 'Surabaya'],
        ];

        foreach ($ports as $p) {
            Port::updateOrCreate(['code' => $p['code']], $p);
        }

        $portIds = Port::pluck('id')->toArray();

        $depots = [
            [
                'code' => 'DEPOTJKT',
                'name' => 'Depo PDI Jakarta',
                'mode' => 'sea',
                'branch_id' => $jkt->id,
                'coordinator_user_id' => $fcJkt->id,
            ],
            [
                'code' => 'DEPOTBTG',
                'name' => 'Depo Bitung',
                'mode' => 'sea',
                'branch_id' => $mdn->id,
                'coordinator_user_id' => $fcMdo->id,
            ],
        ];

        foreach ($depots as $d) {
            Depot::updateOrCreate(
                ['code' => $d['code']],
                [
                    'name' => $d['name'],
                    'mode' => $d['mode'],
                    'branch_id' => $d['branch_id'],
                    'port_id' => $faker->randomElement($portIds),
                    'service_types' => ['stevedoring', 'trucking'],
                    'address' => $faker->address(),
                    'coordinator_user_id' => $d['coordinator_user_id'],
                ]
            );
        }

        $depotIds = Depot::pluck('id')->toArray();

        for ($i = 1; $i <= 10; $i++) {
            Customer::firstOrCreate(
                ['code' => "CUST-{$i}"],
                [
                    'type' => CustomerType::Company,
                    'name' => "PT Dummy Company {$i}",
                    'email' => "company{$i}@demo.local",
                    'phone' => $faker->phoneNumber(),
                    'npwp' => $faker->numerify('##.###.###.#-###.###'),
                    'nik' => null,
                    'pic_name' => $faker->name(),
                    'pic_phone' => $faker->phoneNumber(),
                    'pic_email' => "pic{$i}@demo.local",
                    'city_id' => $faker->randomElement($cityIds),
                    'address' => $faker->address(),
                    'postal_code' => $faker->postcode(),
                ]
            );
        }

        foreach (range(1, 15) as $i) {
            Manpower::create([
                'name' => $faker->name(),
                'domain' => MPDomain::SeaFreight,
                'skills' => ['lashing', 'loading'],
                'certs' => ['BOSIET'],
                'phone' => $faker->phoneNumber(),
                'license_number' => strtoupper(Str::random(8)),
                'branch_id' => $faker->randomElement([$jkt->id, $mdn->id]),
                'depot_id' => $faker->randomElement($depotIds),
                'active' => true,
            ]);
        }

        $line = ShippingLine::firstOrCreate(
            ['code' => 'SML'],
            ['name' => 'Samudera Line']
        );

        $vessel = Vessel::firstOrCreate(
            ['name' => 'MV Nusantara'],
            [
                'shipping_line_id' => $line->id,
                'imo' => 'IMO' . $faker->numerify('#######'),
                'capacity' => 1200,
            ]
        );

        Voyage::firstOrCreate(
            ['voyage_no' => 'VY-DEFAULT'],
            [
                'shipping_line_id' => $line->id,
                'vessel_id' => $vessel->id,
                'pol_id' => $faker->randomElement($portIds),
                'pod_id' => $faker->randomElement($portIds),
                'etd' => now()->addDays(2),
                'eta' => now()->addDays(7),
                'etb' => now()->addDays(6),
                'period_month' => now()->startOfMonth(),
                'cargo_plan' => 1000,
            ]
        );
    }
}
<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Models\{
    Branch,
    City,
    Customer,
    Port,
    Depot,
    Manpower,
    ShippingLine,
    Vessel,
    Voyage
};
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
            Port::firstOrCreate(['code' => $p['code']], $p);
        }

        $portIds = Port::pluck('id')->toArray();

        $depots = [
            [
                'code' => 'DPT-JKT-SEA',
                'name' => 'Jakarta Sea Depot',
                'mode' => 'sea',
                'branch_id' => $jkt->id,
            ],
            [
                'code' => 'DPT-MDO-LAND',
                'name' => 'Manado Land Depot',
                'mode' => 'land',
                'branch_id' => $mdn->id,
            ],
        ];

        foreach ($depots as $d) {
            Depot::firstOrCreate(
                ['code' => $d['code']],
                [
                    'name' => $d['name'],
                    'mode' => $d['mode'],
                    'branch_id' => $d['branch_id'],
                    'port_id' => $faker->randomElement($portIds),
                    'service_types' => ['stevedoring', 'trucking'],
                    'address' => $faker->address(),
                    'coordinator_user_id' => null,
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
                'domain' => collect(\App\Enums\MPDomain::cases())->random(),
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

        Voyage::create([
            'shipping_line_id' => $line->id,
            'vessel_id'        => $vessel->id,
            'pol_id'           => $faker->randomElement($portIds),
            'pod_id'           => $faker->randomElement($portIds),
            'voyage_no'        => 'VY-' . strtoupper(Str::random(5)),
            'etd'              => now()->addDays(2),
            'eta'              => now()->addDays(7),
            'etb'              => now()->addDays(6),
            'period_month'     => now()->startOfMonth(),
            'cargo_plan'       => 1000,
        ]);
    }
}

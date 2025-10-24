<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Models\{Branch, City, Customer, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        $cityNames = ['Jakarta', 'Manado', 'Surabaya', 'Makassar', 'Tobelo', 'Bitung', 'Ternate', 'Ambon'];
        foreach ($cityNames as $name) {
            City::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'country' => 'Indonesia']
            );
        }
        $cityIds = City::pluck('id')->all();

        if (! User::where('email', 'admin@jss.local')->exists()) {
            $admin = User::create([
                'name'      => 'Super Admin',
                'email'     => 'admin@jss.local',
                'password'  => Hash::make('Admin#12345'),
                'branch_id' => $jkt->id,
            ]);
            $admin->syncRoles(['super_admin']);
        }

        $faker   = \Faker\Factory::create();     
        $fakerId = \Faker\Factory::create('id_ID');

        $companies = [
            ['code' => 'CUST-IND-A01', 'name' => 'PT Indo Auto Prima'],
            ['code' => 'CUST-SML-A02', 'name' => 'PT Samudera Logistic'],
            ['code' => 'CUST-TRK-A03', 'name' => 'CV Truk Mandiri'],
            ['code' => 'CUST-RET-A04', 'name' => 'PT Retail Nusantara'],
            ['code' => 'CUST-MOT-A05', 'name' => 'PT Motor Jaya'],
            ['code' => 'CUST-ELE-A06', 'name' => 'CV Elektronik Sentosa'],
            ['code' => 'CUST-FMC-A07', 'name' => 'PT FMCG Makmur'],
            ['code' => 'CUST-CON-A08', 'name' => 'PT Consteel Indonesia'],
            ['code' => 'CUST-BHN-A09', 'name' => 'PT Bahan Bangunan Abadi'],
            ['code' => 'CUST-PTA-A10', 'name' => 'PT Putra Transport'],
        ];

        foreach ($companies as $c) {
            $cityId  = $cityIds[array_rand($cityIds)];
            $picName = $fakerId->name();

            Customer::firstOrCreate(
                ['code' => $c['code']],
                [
                    'code'        => $c['code'],
                    'type'        => CustomerType::Company->value,
                    'name'        => $c['name'],
                    'email'       => Str::slug($c['name'], '.') . '@demo.local',
                    'phone'       => '08' . $faker->numerify('##########'),

                    'nik'         => null,
                    'npwp'        => $faker->bothify('##.###.###.#-###.###'),

                    'city_id'     => $cityId,
                    'address'     => $fakerId->address(),
                    'postal_code' => $faker->numerify('#####'),

                    'pic_name'    => $picName,
                    'pic_phone'   => '08' . $faker->numerify('##########'),
                    'pic_email'   => Str::slug($picName, '.') . '@demo.local',
                ]
            );
        }

        Customer::factory()->count(20)->create();
    }
}

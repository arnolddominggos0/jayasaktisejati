<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\{Branch, City, Customer, User};
use Illuminate\Support\Str;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdo = Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        $cityNames = ['Jakarta', 'Manado', 'Surabaya', 'Makassar', 'Tobelo', 'Bitung', 'Ternate', 'Ambon'];
        foreach ($cityNames as $name) {
            City::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'country' => 'Indonesia']);
        }

        // Super admin
        if (!User::where('email', 'admin@jss.local')->exists()) {
            $admin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@jss.local',
                'password' => Hash::make('Admin#12345'),
                'branch_id' => $jkt->id,
            ]);
            $admin->syncRoles(['super_admin']);
        }

        $cityIds = City::pluck('id')->all();
        $customers = [
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

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['code' => $c['code']],
                [
                    'code'         =>$c['code'],
                    'name'         => $c['name'],
                    'email'        => Str::slug($c['name'], '.') . '@demo.local',
                    'nik'          => fake()->numerify('################'),
                    'npwp'         => fake()->numerify('##.###.###.#-###.###'),
                    'city_id'      => $cityIds[array_rand($cityIds)], 
                    'address'      =>fake()->address(),
                    'pic_name'     => fake()->name(),
                    'pic_phone'    => '08' . fake()->numerify('##########'),
                    'postal_code'  => fake()->numerify('####')
                ]
            );
        }
    }
}

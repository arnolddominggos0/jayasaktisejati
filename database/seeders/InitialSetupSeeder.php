<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\{Branch, Office, Customer, User};
use Illuminate\Support\Str;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::findOrCreate($r, 'web');
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
        // === Branches ===
        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdo = Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        Office::firstOrCreate(
            ['code' => 'OF-JAK-PRI'],
            ['name' => 'Depo Tanjung Priok', 'city' => 'Jakarta', 'address' => 'Pelabuhan Tanjung Priok', 'branch_id' => $jkt->id]
        );
        Office::firstOrCreate(
            ['code' => 'OF-JAK-CIK'],
            ['name' => 'Depo Cakung', 'city' => 'Jakarta', 'address' => 'KBN Cakung', 'branch_id' => $jkt->id]
        );

        // Manado cluster
        Office::firstOrCreate(
            ['code' => 'OF-MDO-BIT'],
            ['name' => 'Depo Bitung', 'city' => 'Manado', 'address' => 'Pelabuhan Bitung', 'branch_id' => $mdo->id]
        );
        Office::firstOrCreate(
            ['code' => 'OF-MDO-KAI'],
            ['name' => 'Depo Kairagi', 'city' => 'Manado', 'address' => 'Kairagi', 'branch_id' => $mdo->id]
        );

        Office::firstOrCreate(
            ['code' => 'OF-SBY-PEK'],
            ['name' => 'Depo Tanjung Perak', 'city' => 'Surabaya', 'address' => 'Pelabuhan Tanjung Perak', 'branch_id' => $jkt->id] // boleh taruh di JKT dulu
        );
        Office::firstOrCreate(
            ['code' => 'OF-MKS-SOA'],
            ['name' => 'Depo Soekarno-Hatta MKS', 'city' => 'Makassar', 'address' => 'Soekarno-Hatta', 'branch_id' => $mdo->id] // taruh di MDO
        );

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
                    'name'         => $c['name'],
                    'email'        => Str::slug($c['name'], '.') . '@demo.local',
                    'phone_number' => '08' . fake()->numerify('##########'),
                    'nik'          => fake()->numerify('################'),
                    'npwp'         => fake()->numerify('##.###.###.#-###.###'),
                    'office_id'    => $jkt->offices()->inRandomOrder()->value('id') ?? null,
                ]
            );
        }
    }
}

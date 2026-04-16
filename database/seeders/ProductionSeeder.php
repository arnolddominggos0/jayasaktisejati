<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Manpower;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('==========================================');
        $this->command->info('PRODUCTION SEEDER - JAYA SAKTI SEJATI');
        $this->command->info('==========================================');

        $this->createRoles();
        $this->createMasterData();
        $this->createUsers();
        $this->createManpower();
        $this->createCustomers();
    }

    private function createRoles(): void
    {
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->command->info('✓ Roles created');
    }

    private function createMasterData(): void
    {
        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdo = Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        $cityJkt = City::firstOrCreate(['slug' => 'jakarta'], ['name' => 'Jakarta', 'country' => 'Indonesia']);
        $cityMdo = City::firstOrCreate(['slug' => 'manado'], ['name' => 'Manado', 'country' => 'Indonesia']);
        City::firstOrCreate(['slug' => 'surabaya'], ['name' => 'Surabaya', 'country' => 'Indonesia']);
        City::firstOrCreate(['slug' => 'bitung'], ['name' => 'Bitung', 'country' => 'Indonesia']);

        $portTpri = Port::firstOrCreate(['code' => 'TPRI'], ['name' => 'Tanjung Priok', 'city' => 'Jakarta']);
        $portBtg = Port::firstOrCreate(['code' => 'BTG'], ['name' => 'Bitung Port', 'city' => 'Manado']);

        $this->command->info('✓ Branches, Cities, Ports created');

        $depotJkt = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            [
                'name' => 'Depo Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $portTpri->id,
                'branch_id' => $jkt->id,
                'coordinator_user_id' => null,
            ]
        );

        $depotMdo = Depot::firstOrCreate(
            ['code' => 'DEP-MDO-01'],
            [
                'name' => 'Depo Bitung Manado',
                'mode' => 'sea',
                'port_id' => $portBtg->id,
                'branch_id' => $mdo->id,
                'coordinator_user_id' => null,
            ]
        );

        $this->command->info('✓ Depots created');
    }

    private function createUsers(): void
    {
        $jkt = Branch::where('code', 'JKT')->first();
        $mdo = Branch::where('code', 'MDO')->first();
        $depotJkt = Depot::where('code', 'DEP-TPRI-01')->first();
        $depotMdo = Depot::where('code', 'DEP-MDO-01')->first();

        $users = [
            // ── Super Admin ──
            [
                'name' => 'Admin JSS',
                'email' => 'admin@jayasaktisejati.com',
                'password' => 'JSS@Admin2026!',
                'role' => 'super_admin',
                'branch_id' => $jkt->id,
            ],

            // ── Office Admin ──
            [
                'name' => 'Office Admin Jakarta',
                'email' => 'office.jkt@jayasaktisejati.com',
                'password' => 'JSS@Office2026!',
                'role' => 'office_admin',
                'branch_id' => $jkt->id,
            ],

            // ── FC Jakarta (Depot Tanjung Priok) ──
            [
                'name' => 'Tri Mulya',
                'email' => 'fc.jkt@jayasaktisejati.com',
                'password' => 'JSS@FcJkt2026!',
                'role' => 'field_coordinator',
                'branch_id' => $jkt->id,
            ],

            // ── FC Manado (Depot Bitung) ──
            [
                'name' => 'Suryadi',
                'email' => 'fc.mdo@jayasaktisejati.com',
                'password' => 'JSS@FcMdo2026!',
                'role' => 'field_coordinator',
                'branch_id' => $mdo->id,
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'branch_id' => $data['branch_id'],
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$role]);

            if ($role === 'field_coordinator' && str_contains($data['email'], 'jkt') && $depotJkt) {
                $depotJkt->update(['coordinator_user_id' => $user->id]);
                $this->command->info("  ✓ {$data['name']} → FC Depot Tanjung Priok");
            }
            if ($role === 'field_coordinator' && str_contains($data['email'], 'mdo') && $depotMdo) {
                $depotMdo->update(['coordinator_user_id' => $user->id]);
                $this->command->info("  ✓ {$data['name']} → FC Depot Bitung Manado");
            }
        }

        $this->command->info('✓ Users created');
        $this->command->info('');
        $this->command->info('┌────────────────────────────────────────────────────────────────────┐');
        $this->command->info('│                    PRODUCTION ACCOUNTS                              │');
        $this->command->info('├──────────────────────┬─────────────────────────────────────────────────┤');
        $this->command->info('│ ROLE                 │ EMAIL / PASSWORD                              │');
        $this->command->info('├──────────────────────┼─────────────────────────────────────────────────┤');
        $this->command->info('│ Super Admin          │ admin@jayasaktisejati.com / JSS@Admin2026!     │');
        $this->command->info('│ Office Admin JKT     │ office.jkt@jayasaktisejati.com / JSS@Office2026!│');
        $this->command->info('│ FC Jakarta (Tri Mulya)│ fc.jkt@jayasaktisejati.com / JSS@FcJkt2026!     │');
        $this->command->info('│ FC Manado (Suryadi)   │ fc.mdo@jayasaktisejati.com / JSS@FcMdo2026!     │');
        $this->command->info('└──────────────────────┴─────────────────────────────────────────────────┘');
    }

    private function createManpower(): void
    {
        $jkt = Branch::where('code', 'JKT')->first();
        $mdo = Branch::where('code', 'MDO')->first();
        $depotJkt = Depot::where('code', 'DEP-TPRI-01')->first();
        $depotMdo = Depot::where('code', 'DEP-MDO-01')->first();

        // MP Jakarta - 8 orang sesuai permintaan
        $mpJakarta = [
            ['name' => 'Tri Mulya', 'phone' => '08129876001', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Suryadi', 'phone' => '081212345678', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Odih', 'phone' => '081312345678', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Rustam', 'phone' => '081412345678', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Markus', 'phone' => '081512345678', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Soleh Wahidin', 'phone' => '081612345678', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift']],
            ['name' => 'Habi', 'phone' => '081712345678', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Cemen', 'phone' => '081812345678', 'skills' => ['unloading', 'loading'], 'certs' => ['K3']],
        ];

        // MP Manado - 6 orang
        $mpManado = [
            ['name' => 'Ahmad Fauzi', 'phone' => '08219876001', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Budi Santoso', 'phone' => '082212345001', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Darmawan', 'phone' => '082312345001', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Eko Prasetyo', 'phone' => '082412345001', 'skills' => ['forklift'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Faisal Rahman', 'phone' => '082512345001', 'skills' => ['unloading', 'loading'], 'certs' => ['K3']],
            ['name' => 'Gunawan Wibowo', 'phone' => '082612345001', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift']],
        ];

        foreach ($mpJakarta as $mp) {
            Manpower::firstOrCreate(
                ['name' => $mp['name']],
                [
                    'domain' => 'internal',
                    'skills' => $mp['skills'],
                    'certs' => $mp['certs'],
                    'phone' => $mp['phone'],
                    'branch_id' => $jkt->id,
                    'depot_id' => $depotJkt->id,
                    'active' => true,
                ]
            );
        }

        foreach ($mpManado as $mp) {
            Manpower::firstOrCreate(
                ['name' => $mp['name']],
                [
                    'domain' => 'internal',
                    'skills' => $mp['skills'],
                    'certs' => $mp['certs'],
                    'phone' => $mp['phone'],
                    'branch_id' => $mdo->id,
                    'depot_id' => $depotMdo->id,
                    'active' => true,
                ]
            );
        }

        $this->command->info('✓ Manpower created (8 Jakarta + 6 Manado)');
    }

    private function createCustomers(): void
    {
        $jkt = Branch::where('code', 'JKT')->first();

        $customers = [
            ['code' => 'TAM-001', 'name' => 'PT Toyota Astra Motor', 'email' => 'logistik@toyota.astra.co.id', 'phone' => '021-8195001'],
            ['code' => 'IND-001', 'name' => 'PT Indocement Tunggal Prakarsa', 'email' => 'shipping@indocement.co.id', 'phone' => '021-6591234'],
            ['code' => 'HOL-001', 'name' => 'PT Holcim Indonesia', 'email' => 'logistics@holcim.co.id', 'phone' => '021-5578900'],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['code' => $c['code']],
                [
                    'name' => $c['name'],
                    'email' => $c['email'],
                    'phone' => $c['phone'],
                    'type' => CustomerType::Company,
                    'branch_id' => $jkt->id,
                    'pic_name' => 'Pic '.$c['name'],
                    'pic_phone' => $c['phone'],
                ]
            );
        }

        $customerTAM = Customer::where('code', 'TAM-001')->first();

        $custUser = User::firstOrCreate(
            ['email' => 'customer@jayasaktisejati.com'],
            [
                'name' => 'Logistik TAM',
                'password' => Hash::make('JSS@Cust2026!'),
                'branch_id' => $jkt->id,
                'customer_id' => $customerTAM?->id,
                'email_verified_at' => now(),
            ]
        );
        $custUser->syncRoles(['customer']);

        $this->command->info('✓ Customers & Customer Portal user created');
        $this->command->info('  Customer Portal: customer@jayasaktisejati.com / JSS@Cust2026!');
    }
}

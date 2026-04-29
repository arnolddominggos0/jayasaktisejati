<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Master Data Setup for SPPB Shipment System
 * Creates essential data for Jakarta to Ternate route
 */
class MasterDataSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('SETTING UP MASTER DATA');
        $this->command->info('========================================');

        // 1. Create Roles
        $this->createRoles();

        // 2. Create Branch
        $branch = $this->createBranch();

        // 3. Create Cities
        $cities = $this->createCities();

        // 4. Create Ports
        $ports = $this->createPorts($cities);

        // 5. Create Shipping Line & Vessel
        $shippingLine = $this->createShippingLine();
        $vessel = $this->createVessel($shippingLine);

        // 6. Create Voyage (Jakarta to Ternate)
        $voyage = $this->createVoyage($vessel, $ports);

        // 7. Create Depot
        $depot = $this->createDepot($branch, $ports['tpri']);

        // 8. Create Customer (PT HA TERNATE)
        $customer = $this->createCustomer($cities['ternate']);

        // 9. Create Users (Admin & FC)
        $users = $this->createUsers($branch, $depot);

        // 10. Create Manpower
        $this->createManpower($branch, $depot);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('MASTER DATA READY');
        $this->command->info('========================================');
        $this->printSummary($voyage, $customer, $users);
    }

    private function createRoles(): void
    {
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->command->info('✓ Roles created');
    }

    private function createBranch(): Branch
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta']
        );
        $this->command->info('✓ Branch: Jakarta');

        return $branch;
    }

    private function createCities(): array
    {
        $cities = [
            'Jakarta' => ['province' => 'DKI Jakarta', 'country' => 'Indonesia'],
            'Ternate' => ['province' => 'Maluku Utara', 'country' => 'Indonesia'],
            'Surabaya' => ['province' => 'Jawa Timur', 'country' => 'Indonesia'],
            'Makassar' => ['province' => 'Sulawesi Selatan', 'country' => 'Indonesia'],
        ];

        $result = [];
        foreach ($cities as $name => $data) {
            $result[strtolower($name)] = City::firstOrCreate(
                ['name' => $name],
                [
                    'province' => $data['province'],
                    'country' => $data['country'],
                    'slug' => str()->slug($name),
                    'is_active' => true,
                ]
            );
        }
        $this->command->info('✓ Cities created: '.implode(', ', array_keys($cities)));

        return $result;
    }

    private function createPorts(array $cities): array
    {
        $ports = [
            'tpri' => ['code' => 'TPRI', 'name' => 'Tanjung Priok', 'city' => 'Jakarta'],
            'ternate' => ['code' => 'TRT', 'name' => 'Ternate', 'city' => 'Ternate'],
            'tpk' => ['code' => 'TPK', 'name' => 'Tanjung Perak', 'city' => 'Surabaya'],
            'mak' => ['code' => 'MAK', 'name' => 'Makassar', 'city' => 'Makassar'],
        ];

        $result = [];
        foreach ($ports as $key => $data) {
            $result[$key] = Port::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'city' => $data['city'],
                ]
            );
        }
        $this->command->info('✓ Ports created: '.implode(', ', array_column($ports, 'name')));

        return $result;
    }

    private function createShippingLine(): ShippingLine
    {
        $shippingLine = ShippingLine::firstOrCreate(
            ['code' => 'TAM'],
            ['name' => 'TAM Shipping']
        );
        $this->command->info('✓ Shipping Line: TAM Shipping');

        return $shippingLine;
    }

    private function createVessel(ShippingLine $shippingLine): Vessel
    {
        $vessel = Vessel::firstOrCreate(
            ['code' => 'MV-DHARMAWAN'],
            [
                'name' => 'KM Dharmawan',
                'shipping_line_id' => $shippingLine->id,
                'imo' => 'IMO9212345',
                'capacity' => 1500,
            ]
        );
        $this->command->info('✓ Vessel: KM Dharmawan');

        return $vessel;
    }

    private function createVoyage(Vessel $vessel, array $ports): Voyage
    {
        $voyage = Voyage::firstOrCreate(
            [
                'vessel_id' => $vessel->id,
                'voyage_no' => 'V-TERNATE-0426',
            ],
            [
                'pol_id' => $ports['tpri']->id,
                'pod_id' => $ports['ternate']->id,
                'etd' => Carbon::parse('2026-04-21'),
                'eta' => Carbon::parse('2026-04-28'),
                'shipping_line_id' => $vessel->shipping_line_id,
            ]
        );
        $this->command->info('✓ Voyage: V-TERNATE-0426 (Jakarta → Ternate)');

        return $voyage;
    }

    private function createDepot(Branch $branch, Port $port): Depot
    {
        $depot = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            [
                'name' => 'Depo Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $port->id,
                'branch_id' => $branch->id,
                'service_types' => ['stevedoring', 'container_handling'],
            ]
        );
        $this->command->info('✓ Depot: Depo Tanjung Priok');

        return $depot;
    }

    private function createCustomer(City $ternate): Customer
    {
        $customer = Customer::firstOrCreate(
            ['code' => 'CUST-HA-TERNATE'],
            [
                'type' => CustomerType::Company,
                'name' => 'PT. HA TERNATE',
                'email' => 'ops@haternate.co.id',
                'phone' => '0921-123456',
                'pic_name' => 'P. SONNY',
                'pic_phone' => '081234567890',
                'city_id' => $ternate->id,
                'address' => 'Jl. Pahlawan No. 1, Ternate',
            ]
        );
        $this->command->info('✓ Customer: PT HA TERNATE');

        return $customer;
    }

    private function createUsers(Branch $branch, Depot $depot): array
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@jss.local'],
            [
                'name' => 'Super Admin JSS',
                'password' => Hash::make('Admin#12345'),
                'branch_id' => $branch->id,
            ]
        );
        $admin->syncRoles(['super_admin']);

        // Field Coordinator
        $fc = User::firstOrCreate(
            ['email' => 'fc.jkt@jss.local'],
            [
                'name' => 'FC Jakarta - Tanjung Priok',
                'password' => Hash::make('Fc#12345'),
                'branch_id' => $branch->id,
            ]
        );
        $fc->syncRoles(['field_coordinator']);

        // Link FC to depot
        $depot->update(['coordinator_user_id' => $fc->id]);

        $this->command->info('✓ Users created');

        return ['admin' => $admin, 'fc' => $fc];
    }

    private function createManpower(Branch $branch, Depot $depot): void
    {
        $skills = ['lashing', 'loading', 'unloading'];
        $certs = ['BOSIET', 'SIGTTOW'];

        for ($i = 1; $i <= 8; $i++) {
            Manpower::firstOrCreate(
                ['name' => "Manpower JKT {$i}"],
                [
                    'domain' => 'sea_freight',
                    'skills' => $skills,
                    'certs' => $certs,
                    'phone' => "0812-345-{$i}00",
                    'license_number' => "LICENSE-JKT-{$i}",
                    'branch_id' => $branch->id,
                    'depot_id' => $depot->id,
                    'active' => true,
                ]
            );
        }
        $this->command->info('✓ Manpower created: 8 workers');
    }

    private function printSummary(Voyage $voyage, Customer $customer, array $users): void
    {
        $this->command->info('');
        $this->command->info('VOYAGE:');
        $this->command->info("  Route: {$voyage->pol->name} → {$voyage->pod->name}");
        $this->command->info("  ETD: {$voyage->etd->format('d M Y')}");
        $this->command->info("  ETA: {$voyage->eta->format('d M Y')}");
        $this->command->info("  Vessel: {$voyage->vessel->name}");
        $this->command->info('');
        $this->command->info('LOGIN CREDENTIALS:');
        $this->command->info('  Admin: admin@jss.local / Admin#12345');
        $this->command->info('  FC: fc.jkt@jss.local / Fc#12345');
    }
}

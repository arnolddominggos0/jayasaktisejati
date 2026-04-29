<?php

namespace Database\Seeders;

use App\Enums\CargoType;
use App\Enums\CustomerType;
use App\Enums\DropFloorCondition;
use App\Enums\DropFloorStrength;
use App\Enums\FinalDecisionStatus;
use App\Enums\IronHookStatus;
use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Enums\RackPillarCondition;
use App\Enums\RackPulleyHookStatus;
use App\Enums\RackTieStatus;
use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\LoadingFinalDecision;
use App\Models\LoadingSession;
use App\Models\Manpower;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OneMonthRealDataSeeder extends Seeder
{
    private array $branches = [];

    private array $ports = [];

    private array $cities = [];

    private array $depots = [];

    private array $customers = [];

    private array $vessels = [];

    private array $shippingLines = [];

    private array $manpowers = [];

    private array $fcs = [];

    private User $admin;

    private Carbon $periodStart;

    private Carbon $periodEnd;

    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('ONE MONTH REAL DATA SEEDER');
        $this->command->info('Field Coordinator Module - Audit Test');
        $this->command->info('========================================');
        $this->command->info('');

        $this->periodStart = Carbon::parse('2026-03-01');
        $this->periodEnd = Carbon::parse('2026-03-31');

        $this->createRoles();
        $this->createMasterData();
        $this->createUsers();
        $this->createManpower();

        $this->command->info('');
        $this->command->info('Creating shipments for March 2026...');
        $this->command->info('');

        $this->createShipmentsByStatus();

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('SEEDING COMPLETED!');
        $this->command->info('========================================');
        $this->printSummary();
    }

    private function createRoles(): void
    {
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->command->info('Roles ready.');
    }

    private function createMasterData(): void
    {
        $this->branches['jkt'] = Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta', 'address' => 'Jl. Tanjung Priok No. 1', 'phone' => '021-12345678']
        );
        $this->branches['mdn'] = Branch::firstOrCreate(
            ['code' => 'MDN'],
            ['name' => 'Manado', 'address' => 'Jl. Bitung No. 1', 'phone' => '0431-123456']
        );
        $this->branches['sby'] = Branch::firstOrCreate(
            ['code' => 'SBY'],
            ['name' => 'Surabaya', 'address' => 'Jl. Tanjung Perak No. 1', 'phone' => '031-123456']
        );

        foreach (['Jakarta', 'Manado', 'Surabaya', 'Makassar', 'Bitung', 'Bontang', 'Semarang'] as $name) {
            $province = match ($name) {
                'Jakarta' => 'DKI Jakarta',
                'Manado' => 'Sulawesi Utara',
                'Surabaya' => 'Jawa Timur',
                'Makassar' => 'Sulawesi Selatan',
                'Bitung' => 'Sulawesi Utara',
                'Bontang' => 'Kalimantan Timur',
                'Semarang' => 'Jawa Tengah',
                default => 'Indonesia',
            };
            $this->cities[$name] = City::firstOrCreate(
                ['name' => $name],
                ['province' => $province, 'country' => 'Indonesia', 'slug' => str()->slug($name), 'is_active' => true]
            );
        }

        $this->ports['tpri'] = Port::firstOrCreate(['code' => 'TPRI'], ['name' => 'Tanjung Priok', 'city' => 'Jakarta']);
        $this->ports['mdn'] = Port::firstOrCreate(['code' => 'MDN'], ['name' => 'Bitung', 'city' => 'Manado']);
        $this->ports['tpk'] = Port::firstOrCreate(['code' => 'TPK'], ['name' => 'Tanjung Perak', 'city' => 'Surabaya']);
        $this->ports['mak'] = Port::firstOrCreate(['code' => 'MAK'], ['name' => 'Makassar', 'city' => 'Makassar']);
        $this->ports['btg'] = Port::firstOrCreate(['code' => 'BTG'], ['name' => 'Bontang', 'city' => 'Bontang']);

        $this->shippingLines['tam'] = \App\Models\ShippingLine::firstOrCreate(['code' => 'TAM'], ['name' => 'TAM Shipping']);
        $this->shippingLines['sml'] = \App\Models\ShippingLine::firstOrCreate(['code' => 'SML'], ['name' => 'Samudera Line']);

        $this->vessels['mv1'] = \App\Models\Vessel::firstOrCreate(
            ['code' => 'MV-001'],
            ['name' => 'KM Dharmawan', 'shipping_line_id' => $this->shippingLines['sml']->id, 'imo' => 'IMO9212345', 'capacity' => 1500]
        );
        $this->vessels['mv2'] = \App\Models\Vessel::firstOrCreate(
            ['code' => 'MV-002'],
            ['name' => 'KM Bintang Laut', 'shipping_line_id' => $this->shippingLines['sml']->id, 'imo' => 'IMO9212346', 'capacity' => 1200]
        );
        $this->vessels['mv3'] = \App\Models\Vessel::firstOrCreate(
            ['code' => 'MV-003'],
            ['name' => 'MV Nusantara', 'shipping_line_id' => $this->shippingLines['tam']->id, 'imo' => 'IMO9212347', 'capacity' => 800]
        );

        $this->depots['tpri'] = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            [
                'name' => 'Depo Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $this->ports['tpri']->id,
                'branch_id' => $this->branches['jkt']->id,
                'address' => 'Jl. Pantai Indah Selatan, Jakarta',
                'service_types' => ['stevedoring', 'container_handling'],
            ]
        );
        $this->depots['mdn'] = Depot::firstOrCreate(
            ['code' => 'DEP-MDN-01'],
            [
                'name' => 'Depo Bitung',
                'mode' => 'sea',
                'port_id' => $this->ports['mdn']->id,
                'branch_id' => $this->branches['mdn']->id,
                'address' => 'Jl. Porto Bitung, Sulawesi Utara',
                'service_types' => ['stevedoring'],
            ]
        );

        $this->customers['cust1'] = Customer::firstOrCreate(
            ['code' => 'CUST-TAM'],
            [
                'type' => CustomerType::Company,
                'name' => 'PT Toyota Astra Motor',
                'email' => 'logistik@toyota.astra.co.id',
                'phone' => '021-8195001',
                'npwp' => '09.123.456.7-123.000',
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '081234567890',
                'city_id' => $this->cities['Jakarta']->id,
                'address' => 'Jl. Yos Sudarso Kav. 8, Sunter II',
            ]
        );
        $this->customers['cust2'] = Customer::firstOrCreate(
            ['code' => 'CUST-STEEL'],
            [
                'type' => CustomerType::Company,
                'name' => 'PT Steel Indonesia',
                'email' => 'ops@steelindo.co.id',
                'phone' => '021-5551234',
                'npwp' => '08.234.567.8-234.000',
                'pic_name' => 'Ahmad Rizki',
                'pic_phone' => '081298765432',
                'city_id' => $this->cities['Surabaya']->id,
                'address' => 'Jl. Pahlawan No. 45, Surabaya',
            ]
        );
        $this->customers['cust3'] = Customer::firstOrCreate(
            ['code' => 'CUST-KARYA'],
            [
                'type' => CustomerType::Individual,
                'name' => 'CV Karya Logistik',
                'email' => 'karya.log@gmail.com',
                'phone' => '0411-555123',
                'pic_name' => 'Sri Wahyuni',
                'pic_phone' => '081567890123',
                'city_id' => $this->cities['Makassar']->id,
                'address' => 'Jl. Pettarani No. 78, Makassar',
            ]
        );

        $this->command->info('Master data ready.');
    }

    private function createUsers(): void
    {
        $this->admin = User::firstOrCreate(
            ['email' => 'admin@jss.local'],
            ['name' => 'Super Admin JSS', 'password' => Hash::make('Admin#12345'), 'branch_id' => $this->branches['jkt']->id]
        );
        $this->admin->syncRoles(['super_admin']);

        $this->fcs['jkt'] = User::firstOrCreate(
            ['email' => 'fc.jkt@jss.local'],
            ['name' => 'FC Jakarta - Tanjung Priok', 'password' => Hash::make('Fc#12345'), 'branch_id' => $this->branches['jkt']->id]
        );
        $this->fcs['jkt']->syncRoles(['field_coordinator']);

        $this->fcs['mdn'] = User::firstOrCreate(
            ['email' => 'fc.mdn@jss.local'],
            ['name' => 'FC Manado - Bitung', 'password' => Hash::make('Fc#12345'), 'branch_id' => $this->branches['mdn']->id]
        );
        $this->fcs['mdn']->syncRoles(['field_coordinator']);

        $this->depots['tpri']->update(['coordinator_user_id' => $this->fcs['jkt']->id]);
        $this->depots['mdn']->update(['coordinator_user_id' => $this->fcs['mdn']->id]);

        $this->command->info('Users ready:');
        $this->command->info('  - Admin: admin@jss.local / Admin#12345');
        $this->command->info('  - FC Jakarta: fc.jkt@jss.local / Fc#12345');
        $this->command->info('  - FC Manado: fc.mdn@jss.local / Fc#12345');
    }

    private function createManpower(): void
    {
        $depotJkt = $this->depots['tpri'];
        $depotMdn = $this->depots['mdn'];

        for ($i = 1; $i <= 15; $i++) {
            $this->manpowers[] = Manpower::firstOrCreate(
                ['name' => "Manpower JKT {$i}"],
                [
                    'domain' => 'yard',
                    'skills' => ['lashing', 'loading', 'unloading'],
                    'certs' => ['BOSIET', 'SIGTTOW'],
                    'phone' => "0812-345-{$i}00",
                    'license_number' => "LICENSE-JKT-{$i}",
                    'branch_id' => $this->branches['jkt']->id,
                    'depot_id' => $depotJkt->id,
                    'active' => true,
                ]
            );
        }

        for ($i = 1; $i <= 10; $i++) {
            $this->manpowers[] = Manpower::firstOrCreate(
                ['name' => "Manpower MDN {$i}"],
                [
                    'domain' => 'yard',
                    'skills' => ['lashing', 'loading', 'unloading'],
                    'certs' => ['BOSIET'],
                    'phone' => "0813-456-{$i}00",
                    'license_number' => "LICENSE-MDN-{$i}",
                    'branch_id' => $this->branches['mdn']->id,
                    'depot_id' => $depotMdn->id,
                    'active' => true,
                ]
            );
        }

        $this->command->info('Manpower ready: '.count($this->manpowers).' total');
    }

    private function createShipmentsByStatus(): void
    {
        $sequence = 1;

        $this->command->info('');
        $this->command->info('--- Creating DELIVERED shipments (completed) ---');

        $deliveredData = [
            ['customer' => 'cust1', 'origin' => 'Jakarta', 'dest' => 'Makassar', 'pol' => 'tpri', 'pod' => 'mak', 'vessel' => 'mv1', 'container_size' => '40ft', 'qty' => 2, 'units' => 15],
            ['customer' => 'cust2', 'origin' => 'Surabaya', 'dest' => 'Jakarta', 'pol' => 'tpk', 'pod' => 'tpri', 'vessel' => 'mv2', 'container_size' => '20ft', 'qty' => 1, 'units' => 8],
            ['customer' => 'cust1', 'origin' => 'Jakarta', 'dest' => 'Manado', 'pol' => 'tpri', 'pod' => 'mdn', 'vessel' => 'mv3', 'container_size' => '40ft', 'qty' => 3, 'units' => 20],
            ['customer' => 'cust3', 'origin' => 'Makassar', 'dest' => 'Surabaya', 'pol' => 'mak', 'pod' => 'tpk', 'vessel' => 'mv1', 'container_size' => '20ft', 'qty' => 1, 'units' => 5],
        ];

        foreach ($deliveredData as $data) {
            $this->createDeliveredShipment($data, $sequence++);
        }

        $this->command->info('');
        $this->command->info('--- Creating IN-TRANSIT shipments (various stages) ---');

        $transitData = [
            ['status' => 'transit', 'origin' => 'Jakarta', 'dest' => 'Makassar', 'pol' => 'tpri', 'pod' => 'mak', 'vessel' => 'mv2', 'last_track' => 'vessel_depart', 'container_size' => '40ft', 'qty' => 2, 'units' => 18],
            ['status' => 'transit', 'origin' => 'Jakarta', 'dest' => 'Manado', 'pol' => 'tpri', 'pod' => 'mdn', 'vessel' => 'mv3', 'last_track' => 'vessel_arrival', 'container_size' => '40ft', 'qty' => 1, 'units' => 10],
            ['status' => 'transit', 'origin' => 'Surabaya', 'dest' => 'Makassar', 'pol' => 'tpk', 'pod' => 'mak', 'vessel' => 'mv1', 'last_track' => 'unloading', 'container_size' => '20ft', 'qty' => 1, 'units' => 6],
        ];

        foreach ($transitData as $data) {
            $this->createTransitShipment($data, $sequence++);
        }

        $this->command->info('');
        $this->command->info('--- Creating PENDING shipments (at various stages) ---');

        $pendingData = [
            ['status' => 'pending', 'origin' => 'Jakarta', 'dest' => 'Manado', 'pol' => 'tpri', 'pod' => 'mdn', 'last_track' => 'pickup', 'container_size' => '40ft', 'qty' => 2, 'units' => 12],
            ['status' => 'pending', 'origin' => 'Jakarta', 'dest' => 'Makassar', 'pol' => 'tpri', 'pod' => 'mak', 'last_track' => 'handover', 'container_size' => '40ft', 'qty' => 1, 'units' => 8],
            ['status' => 'pending', 'origin' => 'Surabaya', 'dest' => 'Jakarta', 'pol' => 'tpk', 'pod' => 'tpri', 'last_track' => 'stuffing', 'container_size' => '20ft', 'qty' => 1, 'units' => 6],
            ['status' => 'pending', 'origin' => 'Jakarta', 'dest' => 'Bitung', 'pol' => 'tpri', 'pod' => 'mdn', 'last_track' => 'delivery_to_port', 'container_size' => '40ft', 'qty' => 3, 'units' => 25],
        ];

        foreach ($pendingData as $data) {
            $this->createPendingShipment($data, $sequence++);
        }

        $this->command->info('');
        $this->command->info('--- Creating DRAFT shipments (not sent to FC) ---');

        for ($i = 1; $i <= 3; $i++) {
            $this->createDraftShipment($sequence++);
        }

        $this->command->info('');
        $this->command->info('--- Creating ON-HOLD shipments ---');

        $holdData = [
            ['origin' => 'Jakarta', 'dest' => 'Makassar', 'pol' => 'tpri', 'pod' => 'mak', 'last_track' => 'stacking', 'reason' => 'Weather delay'],
            ['origin' => 'Surabaya', 'dest' => 'Jakarta', 'pol' => 'tpk', 'pod' => 'tpri', 'last_track' => 'unit_loading', 'reason' => 'Document issue'],
        ];

        foreach ($holdData as $data) {
            $this->createHoldShipment($data, $sequence++);
        }
    }

    private function createDeliveredShipment(array $data, int $seq): void
    {
        $code = sprintf('JSS0326SH%04d', $seq);
        $depot = $this->depots['tpri'];
        $fc = $this->fcs['jkt'];

        $etd = $this->randomDateInPeriod($this->periodStart, $this->periodStart->copy()->addDays(5));
        $eta = $etd->copy()->addDays(rand(5, 8));
        $deliveredAt = $eta->copy()->addDays(rand(1, 3));

        $shipment = Shipment::firstOrCreate(
            ['code' => $code],
            [
                'customer_id' => $this->customers[$data['customer']]->id,
                'origin_city_id' => $this->cities[$data['origin']]->id,
                'destination_city_id' => $this->cities[$data['dest']]->id,
                'branch_id' => $this->branches['jkt']->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea,
                'status' => ShipmentStatus::Delivered,
                'service_type' => ServiceType::SeaFreight,
                'service_option' => 'fcl',
                'cargo_type' => CargoType::Vehicle,
                'container_size' => $data['container_size'],
                'container_qty' => $data['qty'],
                'packages_total' => $data['units'],
                'cbm_total' => $data['qty'] * 30,
                'weight_total' => $data['units'] * 1500,
                'pol_id' => $this->ports[$data['pol']]->id,
                'pod_id' => $this->ports[$data['pod']]->id,
                'vessel_name' => $this->vessels[$data['vessel']]->name,
                'voyage' => 'V-'.strtoupper(Str::random(4)),
                'etd' => $etd,
                'eta' => $eta,
                'delivered_at' => $deliveredAt,
                'pic_name' => 'PIC '.$data['customer'],
                'pic_phone' => '081234567890',
                'priority' => rand(0, 1) ? 'urgent' : 'normal',
            ]
        );

        $this->createUnitCheck($shipment, $data['units']);

        $tracks = $this->buildFullTrackTimeline($etd, $deliveredAt, $data['origin'], $data['dest']);
        foreach ($tracks as $track) {
            ShipmentTrack::firstOrCreate(
                ['shipment_id' => $shipment->id, 'status' => $track['status']],
                [
                    'tracked_at' => $track['at'],
                    'location' => $track['location'],
                    'note' => $track['note'],
                    'created_by' => $fc->id,
                    'updated_by' => $fc->id,
                ]
            );
        }

        $loadingSession = $this->createCompletedLoadingSession($shipment, $depot, $fc, $etd);

        $this->command->info("  ✓ {$code} - DELIVERED - {$data['origin']} → {$data['dest']}");
    }

    private function createTransitShipment(array $data, int $seq): void
    {
        $code = sprintf('JSS0326SH%04d', $seq);
        $depot = $this->depots['tpri'];
        $fc = $this->fcs['jkt'];

        $etd = $this->randomDateInPeriod($this->periodStart->copy()->addDays(5), $this->periodStart->copy()->addDays(10));
        $eta = $etd->copy()->addDays(rand(5, 8));

        $status = $data['last_track'] == 'vessel_depart' ? ShipmentStatus::Transit
            : ($data['last_track'] == 'vessel_arrival' ? ShipmentStatus::Transit : ShipmentStatus::Transit);

        $shipment = Shipment::firstOrCreate(
            ['code' => $code],
            [
                'customer_id' => $this->customers['cust1']->id,
                'origin_city_id' => $this->cities[$data['origin']]->id,
                'destination_city_id' => $this->cities[$data['dest']]->id,
                'branch_id' => $this->branches['jkt']->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea,
                'status' => $status,
                'service_type' => ServiceType::SeaFreight,
                'container_size' => $data['container_size'],
                'container_qty' => $data['qty'],
                'packages_total' => $data['units'],
                'pol_id' => $this->ports[$data['pol']]->id,
                'pod_id' => $this->ports[$data['pod']]->id,
                'vessel_name' => $this->vessels[$data['vessel']]->name,
                'etd' => $etd,
                'eta' => $eta,
                'pic_name' => 'PIC Customer',
                'priority' => 'normal',
            ]
        );

        $this->createUnitCheck($shipment, $data['units']);

        $loadingSession = $this->createCompletedLoadingSession($shipment, $depot, $fc, $etd);

        $tracks = $this->buildTransitTracks($etd, $data);
        foreach ($tracks as $track) {
            ShipmentTrack::firstOrCreate(
                ['shipment_id' => $shipment->id, 'status' => $track['status']],
                [
                    'tracked_at' => $track['at'],
                    'location' => $track['location'],
                    'note' => $track['note'],
                    'created_by' => $fc->id,
                    'updated_by' => $fc->id,
                ]
            );
        }

        $this->command->info("  ✓ {$code} - IN-TRANSIT ({$data['last_track']}) - {$data['origin']} → {$data['dest']}");
    }

    private function createPendingShipment(array $data, int $seq): void
    {
        $code = sprintf('JSS0326SH%04d', $seq);
        $depot = $this->depots['tpri'];
        $fc = $this->fcs['jkt'];

        $statusMap = [
            'pickup' => ShipmentStatus::Pending,
            'handover' => ShipmentStatus::Pending,
            'stuffing' => ShipmentStatus::Pending,
            'delivery_to_port' => ShipmentStatus::Pending,
        ];

        $shipment = Shipment::firstOrCreate(
            ['code' => $code],
            [
                'customer_id' => $this->customers['cust1']->id,
                'origin_city_id' => $this->cities[$data['origin']]->id,
                'destination_city_id' => $this->cities[$data['dest']]->id,
                'branch_id' => $this->branches['jkt']->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea,
                'status' => $statusMap[$data['last_track']] ?? ShipmentStatus::Pending,
                'service_type' => ServiceType::SeaFreight,
                'container_size' => $data['container_size'],
                'container_qty' => $data['qty'],
                'packages_total' => $data['units'],
                'pol_id' => $this->ports[$data['pol']]->id,
                'pod_id' => $this->ports[$data['pod']]->id,
                'etd' => now()->addDays(rand(3, 7)),
                'eta' => now()->addDays(rand(10, 15)),
                'pic_name' => 'PIC Customer',
                'priority' => 'normal',
            ]
        );

        $shipment->ensureTrackSkeleton();

        $lastStatus = TrackStatus::tryFrom($data['last_track']);
        if ($lastStatus) {
            $track = $shipment->tracks()->where('status', $lastStatus)->first();
            if ($track) {
                $track->update([
                    'tracked_at' => now()->subHours(rand(1, 48)),
                    'location' => $this->getLocationForStatus($data['last_track'], $data['origin']),
                    'note' => 'Auto-seeded track',
                    'created_by' => $fc->id,
                    'updated_by' => $fc->id,
                ]);
            }
        }

        if (in_array($data['last_track'], ['stuffing', 'delivery_to_port'])) {
            $this->createInProgressLoadingSession($shipment, $depot, $fc);
        }

        $this->command->info("  ✓ {$code} - PENDING ({$data['last_track']}) - {$data['origin']} → {$data['dest']}");
    }

    private function createDraftShipment(int $seq): void
    {
        $code = sprintf('JSS0326SH%04d', $seq);

        $origins = ['Jakarta', 'Surabaya', 'Makassar'];
        $destinations = ['Manado', 'Bitung', 'Jakarta'];

        $shipment = Shipment::firstOrCreate(
            ['code' => $code],
            [
                'customer_id' => $this->customers['cust1']->id,
                'origin_city_id' => $this->cities[$origins[array_rand($origins)]]->id,
                'destination_city_id' => $this->cities[$destinations[array_rand($destinations)]]->id,
                'branch_id' => $this->branches['jkt']->id,
                'mode' => ShipmentMode::Sea,
                'status' => ShipmentStatus::Draft,
                'service_type' => ServiceType::SeaFreight,
                'container_size' => '40ft',
                'container_qty' => rand(1, 3),
                'packages_total' => rand(5, 20),
                'pol_id' => $this->ports['tpri']->id,
                'pod_id' => $this->ports['mdn']->id,
                'pic_name' => 'PIC Customer',
            ]
        );

        $this->command->info("  ✓ {$code} - DRAFT");
    }

    private function createHoldShipment(array $data, int $seq): void
    {
        $code = sprintf('JSS0326SH%04d', $seq);
        $depot = $this->depots['tpri'];
        $fc = $this->fcs['jkt'];

        $shipment = Shipment::firstOrCreate(
            ['code' => $code],
            [
                'customer_id' => $this->customers['cust1']->id,
                'origin_city_id' => $this->cities[$data['origin']]->id,
                'destination_city_id' => $this->cities[$data['dest']]->id,
                'branch_id' => $this->branches['jkt']->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea,
                'status' => ShipmentStatus::Hold,
                'service_type' => ServiceType::SeaFreight,
                'container_size' => '40ft',
                'container_qty' => rand(1, 2),
                'packages_total' => rand(5, 15),
                'pol_id' => $this->ports[$data['pol']]->id,
                'pod_id' => $this->ports[$data['pod']]->id,
                'notes' => "Hold: {$data['reason']}",
                'pic_name' => 'PIC Customer',
            ]
        );

        $shipment->ensureTrackSkeleton();

        $lastStatus = TrackStatus::tryFrom($data['last_track']);
        if ($lastStatus) {
            $track = $shipment->tracks()->where('status', $lastStatus)->first();
            if ($track) {
                $track->update([
                    'tracked_at' => now()->subDays(rand(1, 3)),
                    'location' => $this->getLocationForStatus($data['last_track'], $data['origin']),
                    'note' => "On Hold - {$data['reason']}",
                    'created_by' => $fc->id,
                    'updated_by' => $fc->id,
                ]);
            }

            $holdTrack = $shipment->tracks()->where('status', TrackStatus::Hold)->first();
            if ($holdTrack) {
                $holdTrack->update([
                    'tracked_at' => now()->subDay(),
                    'location' => 'Office',
                    'note' => "Hold: {$data['reason']}",
                    'created_by' => $fc->id,
                    'updated_by' => $fc->id,
                ]);
            }
        }

        $this->command->info("  ✓ {$code} - ON HOLD ({$data['reason']})");
    }

    private function createCompletedLoadingSession(Shipment $shipment, Depot $depot, User $fc, Carbon $startedAt): LoadingSession
    {
        $mpRequired = rand(6, 12);
        $mpPresent = $mpRequired - rand(0, 2);
        $mpSick = rand(0, 2);

        $session = LoadingSession::firstOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'code' => 'LD-'.$shipment->code.'-'.rand(100, 999),
                'shipment_id' => $shipment->id,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'branch_id' => $depot->branch_id,
                'operation_type' => LoadingOperationType::Loading,
                'status' => LoadingStatus::Completed,
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addHours(3),
                'mp_required' => $mpRequired,
                'mp_present' => $mpPresent,
                'mp_absent' => $mpRequired - $mpPresent - $mpSick,
                'mp_sick' => $mpSick,
                'mp_sufficient' => true,
                'mp_fit_count' => $mpPresent - $mpSick,
                'mp_unfit_count' => $mpSick,
                'apd_complete' => true,
                'apd_clean' => true,
                'equipment_safe' => true,
                'rack_container_safe' => true,
                'rack_pillars_ok' => true,
                'drop_floor_ok' => true,
                'container_structure_ok' => true,
                'unit_measurements_ok' => true,
                'stock_apd_sufficient' => true,
                'final_decision_status' => FinalDecisionStatus::Go,
                'mp_attendance_completed' => true,
                'health_check_completed' => true,
                'apd_check_completed' => true,
                'equipment_check_completed' => true,
                'rack_container_check_completed' => true,
                'unit_check_completed' => true,
                'stock_apd_check_completed' => true,
                'manpower_availability_completed' => true,
                'final_decision_completed' => true,
                'gps_latitude' => -6.1256,
                'gps_longitude' => 106.8748,
                'critical_issues_count' => 0,
                'warning_issues_count' => 0,
            ]
        );

        $this->createRackContainerCheck($session, $fc);
        $this->createEquipmentCheck($session, $fc);
        $this->createFinalDecision($session, $fc);

        return $session;
    }

    private function createInProgressLoadingSession(Shipment $shipment, Depot $depot, User $fc): LoadingSession
    {
        $session = LoadingSession::firstOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'code' => 'LD-'.$shipment->code.'-'.rand(100, 999),
                'shipment_id' => $shipment->id,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'branch_id' => $depot->branch_id,
                'operation_type' => LoadingOperationType::Loading,
                'status' => LoadingStatus::RackContainerCheck,
                'started_at' => now()->subHours(rand(1, 4)),
                'mp_required' => rand(8, 12),
                'mp_present' => rand(6, 10),
                'mp_sufficient' => true,
                'mp_fit_count' => rand(6, 10),
                'apd_complete' => true,
                'mp_attendance_completed' => true,
                'health_check_completed' => true,
                'apd_check_completed' => true,
                'rack_container_check_completed' => false,
                'equipment_check_completed' => false,
                'unit_check_completed' => false,
                'final_decision_completed' => false,
                'gps_latitude' => -6.1256,
                'gps_longitude' => 106.8748,
            ]
        );

        $this->createRackContainerCheck($session, $fc);

        return $session;
    }

    private function createRackContainerCheck(LoadingSession $session, User $fc): void
    {
        \App\Models\RackContainerCheck::firstOrCreate(
            ['loading_session_id' => $session->id],
            [
                'pillar_a_condition' => RackPillarCondition::StrongAndStraight,
                'pillar_a_pulley_hook' => RackPulleyHookStatus::PresentAndStrong,
                'pillar_a_tie_status' => RackTieStatus::TiedStrong,
                'pillar_b_condition' => RackPillarCondition::StrongAndStraight,
                'pillar_b_pulley_hook' => RackPulleyHookStatus::PresentAndStrong,
                'pillar_b_tie_status' => RackTieStatus::TiedStrong,
                'pillar_c_condition' => RackPillarCondition::StrongAndStraight,
                'pillar_c_pulley_hook' => RackPulleyHookStatus::PresentAndStrong,
                'pillar_c_tie_status' => RackTieStatus::TiedStrong,
                'pillar_d_condition' => RackPillarCondition::StrongAndStraight,
                'pillar_d_pulley_hook' => RackPulleyHookStatus::PresentAndStrong,
                'pillar_d_tie_status' => RackTieStatus::TiedStrong,
                'drop_floor_front_condition' => DropFloorCondition::Straight,
                'drop_floor_front_strength' => DropFloorStrength::Strong,
                'drop_floor_front_iron_hook' => IronHookStatus::Present,
                'drop_floor_rear_condition' => DropFloorCondition::Straight,
                'drop_floor_rear_strength' => DropFloorStrength::Strong,
                'drop_floor_rear_iron_hook' => IronHookStatus::Present,
                'container_wall_status' => \App\Enums\ContainerStructureStatus::Good,
                'container_floor_status' => \App\Enums\ContainerStructureStatus::Good,
                'container_roof_status' => \App\Enums\ContainerStructureStatus::Good,
                'all_pillars_safe' => true,
                'all_drop_floors_safe' => true,
                'container_structure_safe' => true,
                'overall_safe' => true,
                'critical_issues_count' => 0,
                'warning_issues_count' => 0,
                'checked_by' => $fc->id,
                'checked_at' => $session->started_at ?? now(),
            ]
        );
    }

    private function createEquipmentCheck(LoadingSession $session, User $fc): void
    {
        \App\Models\EquipmentCheck::firstOrCreate(
            ['loading_session_id' => $session->id],
            [
                'pulley_top_status' => 'ok',
                'pulley_bottom_status' => 'ok',
                'mono_rope_condition' => 'new',
                'chain_strength' => 'strong',
                'bolt_nut_status' => 'tight',
                'bamboo_condition' => 'thick',
                'ladder_stability' => 'stable',
                'sponds_cleanliness' => 'clean',
                'pulley_safe' => true,
                'mono_rope_safe' => true,
                'chain_safe' => true,
                'bolt_nut_safe' => true,
                'bamboo_safe' => true,
                'ladder_safe' => true,
                'sponds_safe' => true,
                'overall_safe' => true,
                'critical_issues_count' => 0,
                'warning_issues_count' => 0,
                'checked_by' => $fc->id,
                'checked_at' => $session->started_at ?? now(),
            ]
        );
    }

    private function createFinalDecision(LoadingSession $session, User $fc): void
    {
        LoadingFinalDecision::firstOrCreate(
            ['loading_session_id' => $session->id],
            [
                'status' => FinalDecisionStatus::Go,
                'category' => 'automatic',
                'reason' => 'All checks passed',
                'notes' => 'Loading dapat dilanjutkan',
                'pillar_issues' => false,
                'drop_floor_issues' => false,
                'pulley_issues' => false,
                'apd_incomplete' => false,
                'mp_unhealthy' => false,
                'equipment_unsafe' => false,
                'unit_unsafe' => false,
                'stock_apd_insufficient' => false,
                'mp_insufficient' => false,
                'requested_by' => $fc->id,
                'requested_at' => $session->started_at ?? now(),
                'approved_by' => $fc->id,
                'approved_at' => $session->completed_at ?? now(),
            ]
        );
    }

    private function createUnitCheck(Shipment $shipment, int $unitCount): void
    {
        for ($i = 1; $i <= $unitCount; $i++) {
            $unit = Unit::firstOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'model_no' => 'MODEL-'.rand(100, 999),
                ],
                [
                    'reg_no' => 'B '.rand(1000, 9999).' '.chr(rand(65, 90)),
                    'chassis_no' => 'CH'.strtoupper(Str::random(10)),
                    'engine_no' => 'EN'.strtoupper(Str::random(10)),
                    'color' => ['Hitam', 'Putih', 'Silver', 'Merah'][rand(0, 3)],
                    'do_number' => 'DO-'.date('Ymd').'-'.$i,
                    'qty' => 1,
                ]
            );
        }
    }

    private function buildFullTrackTimeline(Carbon $etd, Carbon $deliveredAt, string $origin, string $dest): array
    {
        $tracks = [];
        $current = $etd->copy()->subDays(2);

        $sequence = [
            ['status' => TrackStatus::Pickup, 'location' => "Depo {$origin}", 'note' => 'Penjemputan dimulai', 'days_offset' => -2],
            ['status' => TrackStatus::Handover, 'location' => "Depo {$origin}", 'note' => 'Handover ke depo', 'days_offset' => -1.5],
            ['status' => TrackStatus::Stuffing, 'location' => "Depo {$origin}", 'note' => 'Stuffing & segel', 'days_offset' => -1],
            ['status' => TrackStatus::DeliveryToPort, 'location' => "Port {$origin}", 'note' => 'Antar ke pelabuhan', 'days_offset' => -0.5],
            ['status' => TrackStatus::Stacking, 'location' => "Port {$origin}", 'note' => 'Stacking di terminal', 'days_offset' => 0],
            ['status' => TrackStatus::UnitLoading, 'location' => "Port {$origin}", 'note' => 'Dimuat ke kapal', 'days_offset' => 0.5],
            ['status' => TrackStatus::OnShip, 'location' => 'On Board', 'note' => 'Barang sudah di kapal', 'days_offset' => 1],
            ['status' => TrackStatus::VesselDepart, 'location' => 'Sea', 'note' => 'Kapal berangkat', 'days_offset' => 1],
            ['status' => TrackStatus::VesselArrival, 'location' => "Port {$dest}", 'note' => 'Kapal tiba di tujuan', 'days_offset' => 6],
            ['status' => TrackStatus::Unloading, 'location' => "Port {$dest}", 'note' => 'Pembongkaran', 'days_offset' => 6.5],
            ['status' => TrackStatus::DeliveryToCustomer, 'location' => "Port {$dest}", 'note' => 'Antar ke customer', 'days_offset' => 7],
            ['status' => TrackStatus::Delivered, 'location' => 'Customer', 'note' => 'Telah diterima customer', 'days_offset' => 7.5],
        ];

        foreach ($sequence as $item) {
            $tracks[] = [
                'status' => $item['status'],
                'location' => $item['location'],
                'note' => $item['note'],
                'at' => $current->copy()->addDays($item['days_offset']),
            ];
        }

        return $tracks;
    }

    private function buildTransitTracks(Carbon $etd, array $data): array
    {
        $tracks = [];
        $current = $etd->copy()->subDays(2);

        $sequence = [
            ['status' => TrackStatus::Pickup, 'location' => "Depo {$data['origin']}", 'note' => 'Pickup', 'days_offset' => -2],
            ['status' => TrackStatus::Handover, 'location' => "Depo {$data['origin']}", 'note' => 'Handover', 'days_offset' => -1.5],
            ['status' => TrackStatus::Stuffing, 'location' => "Depo {$data['origin']}", 'note' => 'Stuffing', 'days_offset' => -1],
            ['status' => TrackStatus::DeliveryToPort, 'location' => "Port {$data['origin']}", 'note' => 'Delivery to Port', 'days_offset' => -0.5],
            ['status' => TrackStatus::Stacking, 'location' => "Port {$data['origin']}", 'note' => 'Stacking', 'days_offset' => 0],
            ['status' => TrackStatus::UnitLoading, 'location' => "Port {$data['origin']}", 'note' => 'Unit Loading', 'days_offset' => 0.5],
            ['status' => TrackStatus::OnShip, 'location' => 'On Board', 'note' => 'On Ship', 'days_offset' => 1],
            ['status' => TrackStatus::VesselDepart, 'location' => 'Sea', 'note' => 'Departed', 'days_offset' => 1],
        ];

        $statusOrder = array_map(fn ($s) => $s['status']->value, $sequence);
        $lastTrackIdx = array_search($data['last_track'], $statusOrder);

        foreach ($sequence as $idx => $item) {
            if ($idx <= $lastTrackIdx) {
                $tracks[] = [
                    'status' => $item['status'],
                    'location' => $item['location'],
                    'note' => $item['note'],
                    'at' => $current->copy()->addDays($item['days_offset']),
                ];
            }
        }

        return $tracks;
    }

    private function getLocationForStatus(string $status, string $origin): string
    {
        return match ($status) {
            'pickup' => "Depo {$origin}",
            'handover' => "Depo {$origin}",
            'stuffing' => "Depo {$origin}",
            'delivery_to_port' => "Port {$origin}",
            'stacking' => "Port {$origin}",
            'unit_loading' => "Port {$origin}",
            default => "{$origin}",
        };
    }

    private function randomDateInPeriod(Carbon $start, Carbon $end): Carbon
    {
        $diff = $start->diffInDays($end);

        return $start->copy()->addDays(rand(0, max(0, $diff)));
    }

    private function printSummary(): void
    {
        $this->command->info('');
        $this->command->info('DATA SUMMARY:');
        $this->command->info('  Branches: '.Branch::count());
        $this->command->info('  Ports: '.Port::count());
        $this->command->info('  Cities: '.City::count());
        $this->command->info('  Depots: '.Depot::count());
        $this->command->info('  Customers: '.Customer::count());
        $this->command->info('  Manpower: '.Manpower::count());
        $this->command->info('  Shipments: '.Shipment::count());
        $this->command->info('  Shipment Tracks: '.ShipmentTrack::count());
        $this->command->info('  Loading Sessions: '.LoadingSession::count());
        $this->command->info('');
        $this->command->info('SHIPMENT STATUS BREAKDOWN:');
        foreach (ShipmentStatus::cases() as $status) {
            $count = Shipment::where('status', $status)->count();
            if ($count > 0) {
                $this->command->info("  {$status->label()}: {$count}");
            }
        }
        $this->command->info('');
        $this->command->info('LOGIN CREDENTIALS:');
        $this->command->info('  Admin Panel: /admin');
        $this->command->info('    Email: admin@jss.local / Admin#12345');
        $this->command->info('  FC Panel: /fc');
        $this->command->info('    Email: fc.jkt@jss.local / Fc#12345 (Jakarta)');
        $this->command->info('    Email: fc.mdn@jss.local / Fc#12345 (Manado)');
    }
}

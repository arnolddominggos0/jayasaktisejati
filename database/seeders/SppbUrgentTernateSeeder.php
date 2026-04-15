<?php

namespace Database\Seeders;

use App\Enums\CargoType;
use App\Enums\CustomerType;
use App\Enums\FinalDecisionStatus;
use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\LoadingSession;
use App\Models\Manpower;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\ShippingLine;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SPPB URGENT - PT HA TERNATE
 * Door to Door shipment with 3 vehicle units
 *
 * Request: 2026-04-14
 * ETD: 2026-04-21
 * Origin: SEMPER, Jakarta Utara
 * Destination: Ternate
 */
class SppbUrgentTernateSeeder extends Seeder
{
    private array $data = [
        'request_by' => 'P. SONNY',
        'request_date' => '2026-04-14',
        'email' => 'p.sonny@toyota.astra.co.id',
        'notes' => 'URGENT! UNIT SPK PRIORITAS KIRIM',
        'service_type' => 'door_to_door',
        'etd' => '2026-04-21',
        'eta' => '2026-04-28', // Estimated 7 days sailing
        'origin' => 'SEMPER, Jakarta Utara',
        'origin_city' => 'Jakarta',
        'destination' => 'Ternate',
        'destination_city' => 'Ternate',
        'customer_name' => 'PT. HA TERNATE',
        'vessel_name' => 'KM Dharmawan',
        'units' => [
            [
                'model' => 'AVANZA 1.3 E M/T',
                'reg_no' => '2604-0133',
                'chassis_no' => 'MHKAA1BY8TJ014260',
                'engine_no' => '1NR-G323689',
                'color' => 'BLACK MICA',
                'do_no' => 'JKT/01/26/04/0113',
                'qty' => 1,
                'remarks' => '',
            ],
            [
                'model' => 'HILUX D-CAB 2.4 G (4x4) DSL M/T',
                'reg_no' => '2604-0143',
                'chassis_no' => 'MR0KB8CD3T1167940',
                'engine_no' => '2GD-D580477',
                'color' => 'SUPER WHITE II',
                'do_no' => 'JKT/01/26/04/0113',
                'qty' => 1,
                'remarks' => '20300-SPK260123',
            ],
            [
                'model' => 'HILUX D-CAB 2.4 G (4x4) DSL M/T',
                'reg_no' => '2604-0174',
                'chassis_no' => 'MR0KB8CD6T1167866',
                'engine_no' => '2GD-D580261',
                'color' => 'SUPER WHITE II',
                'do_no' => 'JKT/01/26/04/0113',
                'qty' => 1,
                'remarks' => '20300-SPK260131',
            ],
        ],
    ];

    private ?Shipment $shipment = null;

    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('SPPB URGENT - PT HA TERNATE');
        $this->command->info('Door to Door Shipment (3 Units)');
        $this->command->info('========================================');
        $this->command->info('');

        // Step 1: Create Master Data
        $this->createMasterData();

        // Step 2: Create Shipment Request
        $this->createShipmentRequest();

        // Step 3: Create Units
        $this->createUnits();

        // Step 4: Simulate Full Operational Flow
        $this->simulateOperationalFlow();

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('SEEDING COMPLETED!');
        $this->command->info('========================================');
        $this->printSummary();
    }

    private function createMasterData(): void
    {
        $this->command->info('Step 1: Creating Master Data...');

        // Create Branch Jakarta
        $branchJkt = Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta']
        );

        // Create City Jakarta
        $cityJkt = City::firstOrCreate(
            ['name' => 'Jakarta'],
            ['province' => 'DKI Jakarta', 'country' => 'Indonesia', 'slug' => 'jakarta', 'is_active' => true]
        );

        // Create City Ternate
        $cityTernate = City::firstOrCreate(
            ['name' => 'Ternate'],
            ['province' => 'Maluku Utara', 'country' => 'Indonesia', 'slug' => 'ternate', 'is_active' => true]
        );

        // Create Port Tanjung Priok (Origin)
        $portTpri = Port::firstOrCreate(
            ['code' => 'TPRI'],
            ['name' => 'Tanjung Priok', 'city' => 'Jakarta']
        );

        // Create Port Ternate (Destination)
        $portTernate = Port::firstOrCreate(
            ['code' => 'TRT'],
            ['name' => 'Ternate', 'city' => 'Ternate']
        );

        // Create Shipping Line
        $shippingLine = ShippingLine::firstOrCreate(
            ['code' => 'TAM'],
            ['name' => 'TAM Shipping']
        );

        // Create Vessel
        $vessel = Vessel::firstOrCreate(
            ['code' => 'MV-DHARMAWAN'],
            [
                'name' => 'KM Dharmawan',
                'shipping_line_id' => $shippingLine->id,
                'imo' => 'IMO9212345',
                'capacity' => 1500,
            ]
        );

        // Create Voyage
        $etd = Carbon::parse($this->data['etd']);
        $eta = Carbon::parse($this->data['eta']);

        $voyage = Voyage::firstOrCreate(
            [
                'vessel_id' => $vessel->id,
                'voyage_no' => 'V-TERNATE-0426',
            ],
            [
                'pol_id' => $portTpri->id,
                'pod_id' => $portTernate->id,
                'etd' => $etd,
                'eta' => $eta,
                'shipping_line_id' => $shippingLine->id,
            ]
        );

        // Create Depot
        $depot = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            [
                'name' => 'Depo Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $portTpri->id,
                'branch_id' => $branchJkt->id,
                'service_types' => ['stevedoring', 'container_handling'],
            ]
        );

        // Create Customer PT HA TERNATE
        $customer = Customer::firstOrCreate(
            ['code' => 'CUST-HA-TERNATE'],
            [
                'type' => CustomerType::Company,
                'name' => 'PT. HA TERNATE',
                'email' => 'ops@haternate.co.id',
                'phone' => '0921-123456',
                'pic_name' => $this->data['request_by'],
                'pic_phone' => '081234567890',
                'city_id' => $cityTernate->id,
                'address' => 'Jl. Pahlawan No. 1, Ternate',
            ]
        );

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@jss.local'],
            ['name' => 'Super Admin JSS', 'password' => Hash::make('Admin#12345'), 'branch_id' => $branchJkt->id]
        );
        $admin->syncRoles(['super_admin']);

        // Create FC User
        $fc = User::firstOrCreate(
            ['email' => 'fc.jkt@jss.local'],
            ['name' => 'FC Jakarta - Tanjung Priok', 'password' => Hash::make('Fc#12345'), 'branch_id' => $branchJkt->id]
        );
        $fc->syncRoles(['field_coordinator']);

        // Link FC to depot
        $depot->update(['coordinator_user_id' => $fc->id]);

        // Create Manpower
        for ($i = 1; $i <= 8; $i++) {
            Manpower::firstOrCreate(
                ['name' => "Manpower JKT {$i}"],
                [
                    'domain' => 'sea_freight',
                    'skills' => ['lashing', 'loading', 'unloading'],
                    'certs' => ['BOSIET', 'SIGTTOW'],
                    'phone' => "0812-345-{$i}00",
                    'license_number' => "LICENSE-JKT-{$i}",
                    'branch_id' => $branchJkt->id,
                    'depot_id' => $depot->id,
                    'active' => true,
                ]
            );
        }

        $this->command->info('  ✓ Master data created');
    }

    private function createShipmentRequest(): void
    {
        $this->command->info('');
        $this->command->info('Step 2: Creating Shipment Request...');

        $customer = Customer::where('code', 'CUST-HA-TERNATE')->first();
        $originCity = City::where('name', 'Jakarta')->first();
        $destCity = City::where('name', 'Ternate')->first();
        $branch = Branch::where('code', 'JKT')->first();
        $depot = Depot::where('code', 'DEP-TPRI-01')->first();
        $portTpri = Port::where('code', 'TPRI')->first();
        $portTernate = Port::where('code', 'TRT')->first();
        $vessel = Vessel::where('code', 'MV-DHARMAWAN')->first();
        $voyage = Voyage::where('voyage_no', 'V-TERNATE-0426')->first();

        // Generate Shipment Code
        $code = 'JSS0426SH'.rand(1000, 9999);

        $this->shipment = Shipment::firstOrCreate(
            ['code' => $code],
            [
                'customer_id' => $customer->id,
                'origin_city_id' => $originCity->id,
                'destination_city_id' => $destCity->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea,
                'status' => ShipmentStatus::Draft,
                'service_type' => ServiceType::SeaFreight,
                'service_option' => 'fcl',
                'cargo_type' => CargoType::Vehicle,
                'delivery_scope' => 'door_to_door',
                'container_size' => '40ft',
                'container_qty' => 1,
                'packages_total' => 3,
                'cbm_total' => 45.00,
                'weight_total' => 4500.00,
                'pol_id' => $portTpri->id,
                'pod_id' => $portTernate->id,
                'vessel_name' => $vessel->name,
                'voyage' => $voyage->voyage_no,
                'voyage_id' => $voyage->id,
                'etd' => Carbon::parse($this->data['etd']),
                'eta' => Carbon::parse($this->data['eta']),
                'pic_name' => $this->data['request_by'],
                'pic_phone' => '081234567890',
                'pickup_contact_name' => $this->data['request_by'],
                'pickup_contact_phone' => '021-70973056',
                'delivery_contact_name' => 'PT HA TERNATE',
                'delivery_contact_phone' => '0921-123456',
                'priority' => 'urgent',
                'notes' => $this->data['notes'],
                'requested_at' => Carbon::parse($this->data['request_date']),
            ]
        );

        // Create initial track skeleton
        $this->shipment->ensureTrackSkeleton();

        $this->command->info("  ✓ Shipment created: {$code}");
        $this->command->info("    - Customer: {$customer->name}");
        $this->command->info("    - Route: {$originCity->name} → {$destCity->name}");
        $this->command->info("    - ETD: {$this->data['etd']}");
        $this->command->info("    - ETA: {$this->data['eta']}");
        $this->command->info('    - Status: DRAFT (URGENT)');
    }

    private function createUnits(): void
    {
        $this->command->info('');
        $this->command->info('Step 3: Creating Vehicle Units...');

        if (! $this->shipment) {
            $this->command->error('Shipment not found!');

            return;
        }

        foreach ($this->data['units'] as $index => $unitData) {
            $unit = Unit::firstOrCreate(
                [
                    'shipment_id' => $this->shipment->id,
                    'chassis_no' => $unitData['chassis_no'],
                ],
                [
                    'model_no' => $unitData['model'],
                    'reg_no' => $unitData['reg_no'],
                    'engine_no' => $unitData['engine_no'],
                    'color' => $unitData['color'],
                    'do_number' => $unitData['do_no'],
                    'qty' => $unitData['qty'],
                    'notes' => $unitData['remarks'] ?: null,
                ]
            );

            $this->command->info('  ✓ Unit '.($index + 1).": {$unitData['model']}");
            $this->command->info("    - Chassis: {$unitData['chassis_no']}");
            $this->command->info("    - Engine: {$unitData['engine_no']}");
            $this->command->info("    - Color: {$unitData['color']}");
            if ($unitData['remarks']) {
                $this->command->info("    - Remarks: {$unitData['remarks']}");
            }
        }

        $this->command->info('  Total Units: '.count($this->data['units']));
    }

    private function simulateOperationalFlow(): void
    {
        $this->command->info('');
        $this->command->info('Step 4: Simulating Operational Flow...');
        $this->command->info('');

        $fc = User::where('email', 'fc.jkt@jss.local')->first();
        $admin = User::where('email', 'admin@jss.local')->first();

        // FLOW STEP BY STEP:

        // 1. SEND TO FC (Draft → Pending)
        $this->command->info('--- STEP 1: SEND TO FC ---');
        $this->shipment->sendToFc();
        $this->shipment->refresh();
        $this->command->info("  Status: {$this->shipment->status->label()}");

        // 2. PICKUP (Dooring Origin)
        $this->command->info('');
        $this->command->info('--- STEP 2: PICKUP (Dooring Origin) ---');
        $pickupTime = Carbon::parse('2026-04-20 08:00:00');

        // Update existing pickup track instead of creating new one
        $pickupTrack = $this->shipment->tracks()->where('status', TrackStatus::Pickup->value)->first();
        if ($pickupTrack) {
            $pickupTrack->update([
                'tracked_at' => $pickupTime,
                'note' => 'Unit dijemput dari SEMPER, Jakarta Utara',
                'location' => 'SEMPER, Jakarta Utara',
                'created_by' => $fc->id,
            ]);
        }

        $this->shipment->refresh();
        $this->command->info("  ✓ Pickup completed at: {$pickupTime->format('d M Y H:i')}");
        $this->command->info("  Status: {$this->shipment->status->label()}");

        // 3. HANDOVER TO DEPOT
        $this->command->info('');
        $this->command->info('--- STEP 3: HANDOVER TO DEPOT ---');
        $handoverTime = Carbon::parse('2026-04-20 10:30:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::Handover,
            'Handover ke Depo Tanjung Priok',
            'Depo Tanjung Priok',
            null,
            null
        );
        $track->update(['tracked_at' => $handoverTime, 'created_by' => $fc->id]);

        $this->shipment->refresh();
        $this->command->info("  ✓ Handover completed at: {$handoverTime->format('d M Y H:i')}");

        // 4. CREATE LOADING SESSION (Consolidation)
        $this->command->info('');
        $this->command->info('--- STEP 4: LOADING SESSION (Consolidation) ---');
        $depot = Depot::where('code', 'DEP-TPRI-01')->first();

        $loadingSession = LoadingSession::create([
            'code' => 'LD-'.$this->shipment->code,
            'shipment_id' => $this->shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'branch_id' => $depot->branch_id,
            'operation_type' => LoadingOperationType::Loading,
            'status' => LoadingStatus::Completed,
            'started_at' => Carbon::parse('2026-04-20 11:00:00'),
            'completed_at' => Carbon::parse('2026-04-20 14:00:00'),
            'mp_required' => 6,
            'mp_present' => 6,
            'mp_absent' => 0,
            'mp_sick' => 0,
            'mp_sufficient' => true,
            'mp_fit_count' => 6,
            'mp_unfit_count' => 0,
            'apd_complete' => true,
            'apd_clean' => true,
            'equipment_safe' => true,
            'rack_container_safe' => true,
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
        ]);

        $this->command->info("  ✓ Loading Session: {$loadingSession->code}");
        $this->command->info('  ✓ Units loaded and consolidated');
        $this->command->info('  ✓ Final Decision: GO');

        // 5. CREATE MP CHECK APPROVAL (Required for stuffing)
        $this->command->info('');
        $this->command->info('--- STEP 5: MP CHECK APPROVAL ---');
        \App\Models\BriefingSession::firstOrCreate(
            [
                'depot_id' => $depot->id,
                'date' => Carbon::parse('2026-04-20')->toDateString(),
            ],
            [
                'coordinator_user_id' => $fc->id,
                'notes' => 'MP Check approved for urgent shipment',
                'mp_check_status' => 'approved',
                'approved_at' => Carbon::parse('2026-04-20 07:00:00'),
                'approved_by' => $fc->id,
            ]
        );
        $this->command->info('  ✓ MP Check approved');

        // 6. STUFFING & SEALING
        $this->command->info('');
        $this->command->info('--- STEP 6: STUFFING & SEALING ---');
        $stuffingTime = Carbon::parse('2026-04-20 14:30:00');

        // Manually create track record (bypass MP Check for seeder)
        $stuffingTrack = ShipmentTrack::firstOrCreate(
            ['shipment_id' => $this->shipment->id, 'status' => TrackStatus::Stuffing->value],
            [
                'tracked_at' => $stuffingTime,
                'note' => 'Stuffing unit ke container dan pemasangan segel',
                'location' => 'Depo Tanjung Priok',
                'created_by' => $fc->id,
                'updated_by' => $fc->id,
            ]
        );

        $this->command->info("  ✓ Stuffing completed at: {$stuffingTime->format('d M Y H:i')}");

        // 7. DELIVERY TO PORT
        $this->command->info('');
        $this->command->info('--- STEP 7: DELIVERY TO PORT ---');
        $deliveryPortTime = Carbon::parse('2026-04-20 16:00:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::DeliveryToPort,
            'Container diantar ke Pelabuhan Tanjung Priok',
            'Pelabuhan Tanjung Priok',
            null,
            null
        );
        $track->update(['tracked_at' => $deliveryPortTime, 'created_by' => $fc->id]);

        $this->command->info("  ✓ Arrived at port: {$deliveryPortTime->format('d M Y H:i')}");

        // 8. STACKING (TERMINAL)
        $this->command->info('');
        $this->command->info('--- STEP 8: STACKING (TERMINAL) ---');
        $stackingTime = Carbon::parse('2026-04-20 18:00:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::Stacking,
            'Container stacking di terminal',
            'Terminal Tanjung Priok',
            null,
            null
        );
        $track->update(['tracked_at' => $stackingTime, 'created_by' => $fc->id]);

        $this->command->info("  ✓ Stacking: {$stackingTime->format('d M Y H:i')}");

        // 9. UNIT LOADING (ON SHIP)
        $this->command->info('');
        $this->command->info('--- STEP 9: UNIT LOADING ---');
        $loadingTime = Carbon::parse('2026-04-21 06:00:00');

        // Manually create track record (bypass MP Check for seeder)
        $loadingTrack = ShipmentTrack::firstOrCreate(
            ['shipment_id' => $this->shipment->id, 'status' => TrackStatus::UnitLoading->value],
            [
                'tracked_at' => $loadingTime,
                'note' => 'Unit dimuat ke KM Dharmawan',
                'location' => 'KM Dharmawan - Tanjung Priok',
                'created_by' => $fc->id,
                'updated_by' => $fc->id,
            ]
        );

        $this->command->info("  ✓ Loaded on vessel: {$loadingTime->format('d M Y H:i')}");

        // 10. VESSEL DEPART
        $this->command->info('');
        $this->command->info('--- STEP 10: VESSEL DEPARTURE ---');
        $departTime = Carbon::parse('2026-04-21 08:00:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::VesselDepart,
            'KM Dharmawan berangkat menuju Ternate',
            'Tanjung Priok',
            null,
            null
        );
        $track->update(['tracked_at' => $departTime, 'created_by' => $fc->id]);

        $this->shipment->refresh();
        $this->command->info("  ✓ Vessel departed: {$departTime->format('d M Y H:i')}");
        $this->command->info("  Status: {$this->shipment->status->label()}");

        // 11. VESSEL ARRIVAL
        $this->command->info('');
        $this->command->info('--- STEP 11: VESSEL ARRIVAL ---');
        $arrivalTime = Carbon::parse('2026-04-28 07:00:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::VesselArrival,
            'KM Dharmawan tiba di Pelabuhan Ternate',
            'Pelabuhan Ternate',
            null,
            null
        );
        $track->update(['tracked_at' => $arrivalTime, 'created_by' => $fc->id]);

        $this->command->info("  ✓ Vessel arrived: {$arrivalTime->format('d M Y H:i')}");

        // 12. UNLOADING
        $this->command->info('');
        $this->command->info('--- STEP 12: UNLOADING ---');
        $unloadingTime = Carbon::parse('2026-04-28 10:00:00');

        // Manually create track record (bypass MP Check for seeder)
        $unloadingTrack = ShipmentTrack::firstOrCreate(
            ['shipment_id' => $this->shipment->id, 'status' => TrackStatus::Unloading->value],
            [
                'tracked_at' => $unloadingTime,
                'note' => 'Pembongkaran unit dari kapal',
                'location' => 'Pelabuhan Ternate',
                'created_by' => $fc->id,
                'updated_by' => $fc->id,
            ]
        );

        $this->command->info("  ✓ Unloading completed: {$unloadingTime->format('d M Y H:i')}");

        // 13. DELIVERY TO CUSTOMER (Dooring Destination)
        $this->command->info('');
        $this->command->info('--- STEP 13: DELIVERY TO CUSTOMER ---');
        $deliveryTime = Carbon::parse('2026-04-28 14:00:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::DeliveryToCustomer,
            'Unit dalam perjalanan ke PT HA TERNATE',
            'Ternate',
            null,
            null
        );
        $track->update(['tracked_at' => $deliveryTime, 'created_by' => $fc->id]);

        $this->command->info("  ✓ Out for delivery: {$deliveryTime->format('d M Y H:i')}");
        $this->command->info('  Destination: PT HA TERNATE');

        // 14. DELIVERED (Door)
        $this->command->info('');
        $this->command->info('--- STEP 14: DELIVERED (DOOR) ---');
        $deliveredTime = Carbon::parse('2026-04-28 16:30:00');
        $track = $this->shipment->appendTrack(
            TrackStatus::Delivered,
            'Unit telah diterima oleh PT HA TERNATE. Penerima: Bagian Operasional. Kondisi: Baik.',
            'PT HA TERNATE - Ternate',
            null,
            null
        );
        $track->update(['tracked_at' => $deliveredTime, 'created_by' => $fc->id]);

        $this->shipment->refresh();
        $this->command->info("  ✓ DELIVERED: {$deliveredTime->format('d M Y H:i')}");
        $this->command->info("  ✓ Final Status: {$this->shipment->status->label()}");
        $this->command->info('  ✓ Lead Time: 8 days');
    }

    private function printSummary(): void
    {
        $this->command->info('');
        $this->command->info('SHIPMENT SUMMARY:');
        $this->command->info('=================');
        $this->command->info("  Shipment Code: {$this->shipment->code}");
        $this->command->info('  Customer: PT HA TERNATE');
        $this->command->info("  PIC: {$this->data['request_by']}");
        $this->command->info('  Route: Jakarta → Ternate');
        $this->command->info('  Service: Door to Door (URGENT)');
        $this->command->info('  Vessel: KM Dharmawan');
        $this->command->info("  ETD: {$this->data['etd']}");
        $this->command->info("  ETA: {$this->data['eta']}");
        $this->command->info("  Status: {$this->shipment->status->label()}");
        $this->command->info('');
        $this->command->info('UNITS:');
        foreach ($this->data['units'] as $index => $unit) {
            $this->command->info('  '.($index + 1).". {$unit['model']} - {$unit['chassis_no']} - {$unit['color']}");
        }
        $this->command->info('');
        $this->command->info('TRACKING TIMELINE:');
        $tracks = ShipmentTrack::where('shipment_id', $this->shipment->id)
            ->whereNotNull('tracked_at')
            ->orderBy('tracked_at')
            ->get();

        foreach ($tracks as $track) {
            $this->command->info("  [{$track->tracked_at->format('d M H:i')}] {$track->status->label()} - {$track->note}");
        }
    }
}

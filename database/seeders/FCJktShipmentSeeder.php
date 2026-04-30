<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Port;
use App\Models\Depot;
use App\Models\City;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\LoadingSession;
use App\Models\RackContainerCheck;
use App\Models\EquipmentCheck;
use App\Models\UnitCheck;
use App\Models\LoadingFinalDecision;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Enums\FinalDecisionStatus;
use App\Enums\RackPillarCondition;
use App\Enums\RackPulleyHookStatus;
use App\Enums\RackTieStatus;
use App\Enums\DropFloorCondition;
use App\Enums\DropFloorStrength;
use App\Enums\IronHookStatus;
use App\Enums\ContainerStructureStatus;
use Spatie\Permission\Models\Role;

class FCJktShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('FC Jakarta - JKT to BTG Route Seeder');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Setup:');
        $this->command->info('- FC: Internal JSS Employee (Jakarta Branch)');
        $this->command->info('- Customer: Toyota Astra Motor (TAM)');
        $this->command->info('- Route: Jakarta (JKT) -> Bontang (BTG)');
        $this->command->info('- Depot: Tanjung Priok (TAM Area)');
        $this->command->info('');

        // Create Roles
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'field_coordinator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'office_admin', 'guard_name' => 'web']);

        // Create Users
        $admin = $this->createAdminJkt();
        $fcJkt = $this->createFCJkt(); // FC Internal JSS

        // Create Master Data
        $branchJkt = $this->createBranchJkt();
        $cityJkt = $this->createCityJakarta();
        $cityBtg = $this->createCityBontang();
        $portTpri = $this->createPortTanjungPriok();
        $portBtg = $this->createPortBontang();
        
        // Depot dengan FC JKT assigned
        $depotTpri = $this->createDepoTanjungPriok($branchJkt->id, $portTpri->id, $fcJkt->id);
        
        // Customer TAM
        $tamCustomer = $this->createCustomerTAM($branchJkt->id);

        $this->command->info('Creating Shipments for FC JKT...');
        $this->command->info('');

        // Shipment 1: COMPLETE - Pre-check MP, APD, Loading done
        $this->createShipmentComplete($tamCustomer, $branchJkt, $cityJkt, $cityBtg, $portTpri, $portBtg, $depotTpri, $fcJkt);

        // Shipment 2: IN PROGRESS - Pre-check MP done, APD in progress
        $this->createShipmentInProgress($tamCustomer, $branchJkt, $cityJkt, $cityBtg, $portTpri, $portBtg, $depotTpri, $fcJkt);

        // Shipment 3: NEW - Ready for Pre-check MP
        $this->createShipmentNew($tamCustomer, $branchJkt, $cityJkt, $cityBtg, $portTpri, $portBtg, $depotTpri, $fcJkt);

        // Shipment 4: UNLOADING at destination
        $this->createShipmentUnloading($tamCustomer, $branchJkt, $cityJkt, $cityBtg, $portTpri, $portBtg, $depotTpri, $fcJkt);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('Seeding completed successfully!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('Admin JKT: admin-jkt@jss.co.id / password123');
        $this->command->info('FC JKT: fc-jkt@jss.co.id / password123');
        $this->command->info('');
        $this->command->info('Shipments Created:');
        $this->command->info('1. TAM-JKT-BTG-001 - COMPLETE (All pre-checks done)');
        $this->command->info('2. TAM-JKT-BTG-002 - IN PROGRESS (MP Check done, Loading ongoing)');
        $this->command->info('3. TAM-JKT-BTG-003 - NEW (Ready for Pre-check MP)');
        $this->command->info('4. TAM-JKT-BTG-004 - UNLOADING (At destination BTG)');
        $this->command->info('');
    }

    // ==========================================
    // USER CREATION
    // ==========================================

    private function createAdminJkt(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin-jkt@jss.co.id'],
            [
                'name' => 'Admin Jakarta',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('super_admin');
        return $admin;
    }

    private function createFCJkt(): User
    {
        // FC Internal JSS - Jakarta Branch
        $fc = User::firstOrCreate(
            ['email' => 'fc-jkt@jss.co.id'],
            [
                'name' => 'Field Coordinator Jakarta',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $fc->assignRole('field_coordinator');
        return $fc;
    }

    // ==========================================
    // MASTER DATA CREATION
    // ==========================================

    private function createBranchJkt(): Branch
    {
        return Branch::firstOrCreate(
            ['code' => 'JKT'],
            [
                'name' => 'Jakarta',
                'address' => 'Jl. Tanjung Priok No. 1, Jakarta Utara',
                'phone' => '021-12345678',
                'email' => 'jakarta@jayasaktisejati.co.id',
            ]
        );
    }

    private function createCityJakarta(): City
    {
        return City::firstOrCreate(
            ['name' => 'Jakarta'],
            [
                'province' => 'DKI Jakarta',
                'country' => 'Indonesia',
            ]
        );
    }

    private function createCityBontang(): City
    {
        return City::firstOrCreate(
            ['name' => 'Bontang'],
            [
                'province' => 'Kalimantan Timur',
                'country' => 'Indonesia',
            ]
        );
    }

    private function createPortTanjungPriok(): Port
    {
        return Port::firstOrCreate(
            ['code' => 'TPRI'],
            [
                'name' => 'Tanjung Priok',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
            ]
        );
    }

    private function createPortBontang(): Port
    {
        return Port::firstOrCreate(
            ['code' => 'BTG'],
            [
                'name' => 'Bontang Port',
                'city' => 'Bontang',
                'country' => 'Indonesia',
            ]
        );
    }

    private function createDepoTanjungPriok(int $branchId, int $portId, int $fcId): Depot
    {
        // Depo Tanjung Priok dengan FC JKT assigned
        return Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            [
                'name' => 'Depo Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $portId,
                'branch_id' => $branchId,
                'coordinator_user_id' => $fcId, // FC JKT assigned here
                'address' => 'Jl. Pelabuhan Tanjung Priok, Area TAM',
            ]
        );
    }

    private function createCustomerTAM(int $branchId): Customer
    {
        // TAM hanya sebagai customer
        return Customer::firstOrCreate(
            ['code' => 'TAM-001'],
            [
                'name' => 'PT Toyota Astra Motor',
                'email' => 'logistik@toyota.astra.co.id',
                'phone' => '021-8195001',
                'address' => 'Jl. Yos Sudarso Kav. 8, Sunter II, Jakarta 14330',
                'type' => 'company',
                'branch_id' => $branchId,
            ]
        );
    }

    // ==========================================
    // SHIPMENT CREATION
    // ==========================================

    private function createShipmentComplete($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc): void
    {
        $this->command->info('Creating Shipment 1: COMPLETE - All Pre-checks Done');

        $shipment = Shipment::firstOrCreate(
            ['code' => 'TAM-JKT-BTG-001'],
            [
                'customer_id' => $customer->id,
                'receiver_id' => $customer->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $cityBtg->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Transit,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'container_size' => '40ft',
                'container_qty' => 2,
                'packages_total' => 15,
                'cbm_total' => 125.5,
                'weight_total' => 22500,
                'pol_id' => $portTpri->id,
                'pod_id' => $portBtg->id,
                'pic_name' => 'Budi Santoso (TAM)',
                'pic_phone' => '081234567890',
                'delivery_contact_name' => 'Ahmad Rizal (TAM Bontang)',
                'delivery_contact_phone' => '081298765432',
                'eta' => now()->addDays(3),
                'notes' => 'TAM Shipment - 15 units. Pre-check MP, APD, Loading Rack COMPLETE by FC JKT',
            ]
        );

        // Loading Session COMPLETE dengan semua pre-check
        $loadingSession = LoadingSession::create([
            'code' => 'LD-JKT-001',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id, // FC JKT
            'branch_id' => $branch->id,
            'operation_type' => LoadingOperationType::Loading->value,
            'status' => LoadingStatus::Completed,
            'mp_required' => 10,
            'mp_present' => 10,
            'mp_sufficient' => true,
            'mp_fit_count' => 10,
            'mp_unfit_count' => 0,
            'apd_complete' => true,
            'apd_clean' => true,
            'equipment_safe' => true,
            'rack_container_safe' => true,
            'unit_measurements_ok' => true,
            'final_decision_status' => FinalDecisionStatus::Go,
            'mp_attendance_completed' => true,
            'health_check_completed' => true,
            'apd_check_completed' => true,
            'equipment_check_completed' => true,
            'rack_container_check_completed' => true,
            'unit_check_completed' => true,
            'final_decision_completed' => true,
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
            'general_notes' => 'FC JKT: All pre-checks passed. MP: 10/10, APD: Complete, Rack: Safe',
        ]);

        // Rack Container Check
        RackContainerCheck::create([
            'loading_session_id' => $loadingSession->id,
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
            'container_wall_status' => ContainerStructureStatus::Good,
            'container_floor_status' => ContainerStructureStatus::Good,
            'container_roof_status' => ContainerStructureStatus::Good,
            'all_pillars_safe' => true,
            'all_drop_floors_safe' => true,
            'container_structure_safe' => true,
            'overall_safe' => true,
            'checked_by' => $fc->id,
            'checked_at' => now()->subDays(2),
        ]);

        // Equipment Check
        EquipmentCheck::create([
            'loading_session_id' => $loadingSession->id,
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
            'checked_by' => $fc->id,
            'checked_at' => now()->subDays(2),
        ]);

        // Unit Check
        UnitCheck::create([
            'loading_session_id' => $loadingSession->id,
            'unit_plate_number' => 'B 1234 TAM',
            'distance_front_rh' => 120,
            'distance_rear_rh' => 115,
            'distance_back_door' => 180,
            'distance_rear_lh' => 118,
            'distance_front_lh' => 122,
            'drop_floor_front_height' => 110,
            'drop_floor_rear_height' => 112,
            'container_roof_distance' => 280,
            'measurements_valid' => true,
            'unit_safe_for_loading' => true,
            'checked_by' => $fc->id,
            'checked_at' => now()->subDays(2),
        ]);

        // Final Decision GO
        LoadingFinalDecision::create([
            'loading_session_id' => $loadingSession->id,
            'status' => FinalDecisionStatus::Go,
            'category' => 'automatic',
            'reason' => 'All pre-checks passed by FC JKT',
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
            'requested_at' => now()->subDays(2),
        ]);

        // Shipment Tracks
        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::Stuffing,
            'tracked_at' => now()->subDays(2),
            'location' => 'Depo Tanjung Priok',
            'note' => 'Pre-check MP, APD, Loading Rack - GO Decision by FC JKT',
        ]);

        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::UnitLoading,
            'tracked_at' => now()->subDay(),
            'location' => 'Tanjung Priok Port',
            'note' => '15 TAM vehicles loaded - Completed by FC JKT',
        ]);

        $this->command->info('  ✓ Shipment 1 created - FC JKT completed all tasks');
    }

    private function createShipmentInProgress($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc): void
    {
        $this->command->info('Creating Shipment 2: IN PROGRESS - MP Check Done, Loading Ongoing');

        $shipment = Shipment::firstOrCreate(
            ['code' => 'TAM-JKT-BTG-002'],
            [
                'customer_id' => $customer->id,
                'receiver_id' => $customer->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $cityBtg->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Pending,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'container_size' => '40ft',
                'container_qty' => 2,
                'packages_total' => 12,
                'cbm_total' => 98.0,
                'weight_total' => 18600,
                'pol_id' => $portTpri->id,
                'pod_id' => $portBtg->id,
                'pic_name' => 'Siti Rahayu (TAM)',
                'pic_phone' => '081345678901',
                'delivery_contact_name' => 'Dedi Supriadi (TAM)',
                'delivery_contact_phone' => '081456789012',
                'eta' => now()->addDays(5),
                'notes' => 'TAM Shipment - 12 units. MP Check done by FC JKT, Loading in progress',
            ]
        );

        // Loading Session IN PROGRESS
        $loadingSession = LoadingSession::create([
            'code' => 'LD-JKT-002',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id, // FC JKT
            'branch_id' => $branch->id,
            'operation_type' => LoadingOperationType::Loading->value,
            'status' => LoadingStatus::RackContainerCheck, // Current step
            'mp_required' => 8,
            'mp_present' => 8,
            'mp_sufficient' => true,
            'mp_fit_count' => 8,
            'mp_unfit_count' => 0,
            'apd_complete' => true,
            'apd_clean' => true,
            'mp_attendance_completed' => true,
            'health_check_completed' => true,
            'apd_check_completed' => true,
            'rack_container_check_completed' => false,
            'equipment_check_completed' => false,
            'unit_check_completed' => false,
            'final_decision_completed' => false,
            'started_at' => now(),
            'general_notes' => 'FC JKT: MP Check (8/8) and APD Check done. Now doing Rack Container Check',
        ]);

        // Partial Rack Container Check
        RackContainerCheck::create([
            'loading_session_id' => $loadingSession->id,
            'pillar_a_condition' => RackPillarCondition::StrongAndStraight,
            'pillar_a_pulley_hook' => RackPulleyHookStatus::PresentAndStrong,
            'pillar_a_tie_status' => RackTieStatus::TiedStrong,
            'pillar_b_condition' => RackPillarCondition::StrongAndStraight,
            'pillar_b_pulley_hook' => RackPulleyHookStatus::PresentAndStrong,
            'pillar_b_tie_status' => RackTieStatus::TiedStrong,
            'pillar_c_condition' => null, // Not checked yet
            'pillar_c_pulley_hook' => null,
            'pillar_c_tie_status' => null,
            'pillar_d_condition' => null, // Not checked yet
            'pillar_d_pulley_hook' => null,
            'pillar_d_tie_status' => null,
            'drop_floor_front_condition' => null,
            'drop_floor_rear_condition' => null,
            'container_wall_status' => null,
            'all_pillars_safe' => false,
            'all_drop_floors_safe' => false,
            'container_structure_safe' => false,
            'overall_safe' => false,
            'checked_by' => $fc->id,
            'checked_at' => now(),
        ]);

        $this->command->info('  ✓ Shipment 2 created - FC JKT in progress (Rack Container Check)');
    }

    private function createShipmentNew($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc): void
    {
        $this->command->info('Creating Shipment 3: NEW - Ready for Pre-check MP');

        $shipment = Shipment::firstOrCreate(
            ['code' => 'TAM-JKT-BTG-003'],
            [
                'customer_id' => $customer->id,
                'receiver_id' => $customer->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $cityBtg->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Draft,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'container_size' => '40ft',
                'container_qty' => 3,
                'packages_total' => 20,
                'cbm_total' => 165.0,
                'weight_total' => 28500,
                'pol_id' => $portTpri->id,
                'pod_id' => $portBtg->id,
                'pic_name' => 'Rini Susanti (TAM)',
                'pic_phone' => '081567890123',
                'delivery_contact_name' => 'Eko Prasetyo (TAM)',
                'delivery_contact_phone' => '081678901234',
                'eta' => now()->addDays(7),
                'notes' => 'TAM Shipment - 20 units. NEW - Waiting for FC JKT Pre-check MP',
            ]
        );

        // Create Loading Session - DRAFT status
        LoadingSession::create([
            'code' => 'LD-JKT-003',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id, // Assigned to FC JKT
            'branch_id' => $branch->id,
            'operation_type' => LoadingOperationType::Loading->value,
            'status' => LoadingStatus::Draft,
            'mp_required' => 12,
            'mp_present' => 0,
            'mp_sufficient' => false,
            'apd_complete' => false,
            'mp_attendance_completed' => false,
            'health_check_completed' => false,
            'apd_check_completed' => false,
            'rack_container_check_completed' => false,
            'equipment_check_completed' => false,
            'unit_check_completed' => false,
            'final_decision_completed' => false,
            'started_at' => null,
            'general_notes' => 'FC JKT: NEW shipment - Ready for Pre-check MP (12 MP required)',
        ]);

        $this->command->info('  ✓ Shipment 3 created - NEW (Ready for FC JKT Pre-check)');
    }

    private function createShipmentUnloading($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc): void
    {
        $this->command->info('Creating Shipment 4: UNLOADING - At Destination BTG');

        $shipment = Shipment::firstOrCreate(
            ['code' => 'TAM-JKT-BTG-004'],
            [
                'customer_id' => $customer->id,
                'receiver_id' => $customer->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $cityBtg->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Transit,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'container_size' => '40ft',
                'container_qty' => 1,
                'packages_total' => 8,
                'cbm_total' => 65.0,
                'weight_total' => 12000,
                'pol_id' => $portTpri->id,
                'pod_id' => $portBtg->id,
                'pic_name' => 'Budi Santoso (TAM)',
                'pic_phone' => '081234567890',
                'delivery_contact_name' => 'Ahmad Rizal (TAM Bontang)',
                'delivery_contact_phone' => '081298765432',
                'eta' => now()->addDay(),
                'notes' => 'TAM Shipment - 8 units. Loading done by FC JKT, now UNLOADING at BTG',
            ]
        );

        // Loading Session COMPLETED (done by FC JKT)
        $loadingSession = LoadingSession::create([
            'code' => 'LD-JKT-004',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id, // FC JKT did this
            'branch_id' => $branch->id,
            'operation_type' => LoadingOperationType::Loading->value,
            'status' => LoadingStatus::Completed,
            'mp_required' => 6,
            'mp_present' => 6,
            'mp_sufficient' => true,
            'mp_fit_count' => 6,
            'mp_unfit_count' => 0,
            'apd_complete' => true,
            'apd_clean' => true,
            'equipment_safe' => true,
            'rack_container_safe' => true,
            'unit_measurements_ok' => true,
            'final_decision_status' => FinalDecisionStatus::Go,
            'mp_attendance_completed' => true,
            'health_check_completed' => true,
            'apd_check_completed' => true,
            'equipment_check_completed' => true,
            'rack_container_check_completed' => true,
            'unit_check_completed' => true,
            'final_decision_completed' => true,
            'started_at' => now()->subWeek(),
            'completed_at' => now()->subDays(5),
            'general_notes' => 'FC JKT: Loading completed. Now at destination for unloading',
        ]);

        // Create Unloading Session (for FC BTG - not created yet, just placeholder)
        LoadingSession::create([
            'code' => 'LD-BTG-004-UNLOAD',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => null, // Will be assigned to FC BTG
            'branch_id' => $branch->id,
            'operation_type' => LoadingOperationType::Unloading->value,
            'status' => LoadingStatus::Draft,
            'mp_required' => 6,
            'mp_present' => 0,
            'mp_sufficient' => false,
            'general_notes' => 'Waiting for FC Bontang to execute unloading',
        ]);

        // Shipment Tracks
        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::Stuffing,
            'tracked_at' => now()->subWeek(),
            'location' => 'Depo Tanjung Priok',
            'note' => 'Pre-check and Loading by FC JKT - GO',
        ]);

        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::UnitLoading,
            'tracked_at' => now()->subDays(5),
            'location' => 'Tanjung Priok Port',
            'note' => 'Loaded by FC JKT',
        ]);

        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::VesselArrival,
            'tracked_at' => now()->subHours(6),
            'location' => 'Bontang Port',
            'note' => 'Arrived at destination - Waiting for FC BTG unloading',
        ]);

        $this->command->info('  ✓ Shipment 4 created - UNLOADING at BTG (Loading done by FC JKT)');
    }
}

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

class TAMShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('TAM - Toyota Astra Motor Seeder');
        $this->command->info('Route: Jakarta (JKT) -> Bontang (BTG)');
        $this->command->info('========================================');

        // Create Roles if not exists
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'field_coordinator', 'guard_name' => 'web']);

        // Create Users
        $admin = $this->createAdmin();
        $fc = $this->createFieldCoordinator();

        // Create Master Data
        $branch = $this->createBranch();
        $cityJkt = $this->createCityJakarta();
        $cityBtg = $this->createCityBontang();
        $portTpri = $this->createPortTanjungPriok();
        $portBtg = $this->createPortBontang();
        $depot = $this->createDepot($branch->id, $portTpri->id, $fc->id);
        $tamCustomer = $this->createTAMCustomer($branch->id);

        $this->command->info('Creating TAM Shipments...');

        // Shipment 1: Complete with Loading Session (GO)
        $this->createShipment1($tamCustomer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc);

        // Shipment 2: In Progress Loading Session
        $this->createShipment2($tamCustomer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc);

        // Shipment 3: Draft (No Loading Session)
        $this->createShipment3($tamCustomer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('Seeding completed successfully!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('Admin: admin@jss.co.id / password123');
        $this->command->info('FC: fc.tam@jss.co.id / password123');
        $this->command->info('');
    }

    private function createAdmin(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@jss.co.id'],
            [
                'name' => 'Administrator JSS',
                'password' => Hash::make('password123'),
                'branch_id' => 1,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('super_admin');
        return $admin;
    }

    private function createFieldCoordinator(): User
    {
        $fc = User::firstOrCreate(
            ['email' => 'fc.tam@jss.co.id'],
            [
                'name' => 'Field Coordinator TAM',
                'password' => Hash::make('password123'),
                'branch_id' => 1,
                'email_verified_at' => now(),
            ]
        );
        $fc->assignRole('field_coordinator');
        return $fc;
    }

    private function createBranch(): Branch
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

    private function createDepot(int $branchId, int $portId, int $fcId): Depot
    {
        return Depot::firstOrCreate(
            ['code' => 'DEP-TAM-JKT'],
            [
                'name' => 'Depo TAM Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $portId,
                'branch_id' => $branchId,
                'coordinator_user_id' => $fcId,
                'address' => 'Jl. Pelabuhan Tanjung Priok, Area TAM',
            ]
        );
    }

    private function createTAMCustomer(int $branchId): Customer
    {
        return Customer::firstOrCreate(
            ['code' => 'TAM-001'],
            [
                'name' => 'PT Toyota Astra Motor',
                'email' => 'shipping@toyota.astra.co.id',
                'phone' => '021-8195001',
                'address' => 'Jl. Yos Sudarso Kav. 8, Sunter II, Jakarta 14330',
                'type' => 'company',
                'branch_id' => $branchId,
            ]
        );
    }

    private function createShipment1($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc): void
    {
        $this->command->info('Creating Shipment 1: COMPLETE with GO decision...');

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
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '081234567890',
                'delivery_contact_name' => 'Ahmad Rizal',
                'delivery_contact_phone' => '081298765432',
                'eta' => now()->addDays(5),
                'notes' => 'TAM Shipment - 15 units Toyota vehicles',
            ]
        );

        // Create Loading Session (COMPLETE)
        $loadingSession = LoadingSession::create([
            'code' => 'LD-TAM-001',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
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
            'general_notes' => 'TAM Loading - All checks passed',
        ]);

        // Create Rack Container Check
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

        // Create Equipment Check
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

        // Create Unit Check
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

        // Create Final Decision
        LoadingFinalDecision::create([
            'loading_session_id' => $loadingSession->id,
            'status' => FinalDecisionStatus::Go,
            'category' => 'automatic',
            'reason' => 'All checks passed successfully',
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

        // Create Shipment Tracks
        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::Stuffing,
            'tracked_at' => now()->subDays(2),
            'location' => 'Depo TAM Tanjung Priok',
            'note' => 'Loading session completed - GO decision',
        ]);

        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::UnitLoading,
            'tracked_at' => now()->subDay(),
            'location' => 'Tanjung Priok Port',
            'note' => 'All 15 TAM vehicles loaded successfully',
        ]);

        $this->command->info('  ✓ Shipment 1 created with complete loading session');
    }

    private function createShipment2($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot, $fc): void
    {
        $this->command->info('Creating Shipment 2: IN PROGRESS loading...');

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
                'pic_name' => 'Siti Rahayu',
                'pic_phone' => '081345678901',
                'delivery_contact_name' => 'Dedi Supriadi',
                'delivery_contact_phone' => '081456789012',
                'eta' => now()->addDays(7),
                'notes' => 'TAM Shipment - 12 units Toyota vehicles (In Progress)',
            ]
        );

        // Create Loading Session (IN PROGRESS - Rack Container Check only)
        $loadingSession = LoadingSession::create([
            'code' => 'LD-TAM-002',
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'branch_id' => $branch->id,
            'operation_type' => LoadingOperationType::Loading->value,
            'status' => LoadingStatus::RackContainerCheck,
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
            'general_notes' => 'TAM Loading Session - Rack Container check in progress',
        ]);

        // Partial Rack Container Check (only pillars done)
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
            'drop_floor_front_condition' => null,
            'drop_floor_front_strength' => null,
            'drop_floor_front_iron_hook' => null,
            'drop_floor_rear_condition' => null,
            'drop_floor_rear_strength' => null,
            'drop_floor_rear_iron_hook' => null,
            'container_wall_status' => null,
            'container_floor_status' => null,
            'container_roof_status' => null,
            'all_pillars_safe' => true,
            'all_drop_floors_safe' => false,
            'container_structure_safe' => false,
            'overall_safe' => false,
            'checked_by' => $fc->id,
            'checked_at' => now(),
        ]);

        $this->command->info('  ✓ Shipment 2 created with in-progress loading session');
    }

    private function createShipment3($customer, $branch, $cityJkt, $cityBtg, $portTpri, $portBtg, $depot): void
    {
        $this->command->info('Creating Shipment 3: DRAFT (No loading session)...');

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
                'pic_name' => 'Rini Susanti',
                'pic_phone' => '081567890123',
                'delivery_contact_name' => 'Eko Prasetyo',
                'delivery_contact_phone' => '081678901234',
                'eta' => now()->addDays(10),
                'notes' => 'TAM Shipment - 20 units Toyota vehicles (Draft)',
            ]
        );

        $this->command->info('  ✓ Shipment 3 created (Draft - no loading session)');
    }
}

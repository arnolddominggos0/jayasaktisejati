<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Branch;
use App\Models\Port;
use App\Models\Depot;
use App\Models\Customer;
use App\Models\City;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\LoadingSession;
use App\Models\RackContainerCheck;
use App\Models\EquipmentCheck;
use App\Models\UnitCheck;
use App\Models\LoadingFinalDecision;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentMode;
use App\Enums\CustomerType;
use App\Enums\LoadingStatus;
use App\Enums\LoadingOperationType;
use App\Enums\FinalDecisionStatus;
use App\Enums\TrackStatus;
use App\Enums\RackPillarCondition;
use App\Enums\RackPulleyHookStatus;
use App\Enums\RackTieStatus;
use App\Enums\DropFloorCondition;
use App\Enums\DropFloorStrength;
use App\Enums\IronHookStatus;
use App\Enums\ContainerStructureStatus;

class ShipmentWithLoadingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info("Starting ShipmentWithLoadingSeeder...");
        $this->createRoles();
        $users = $this->createUsers();
        $masterData = $this->createMasterData();
        $this->createShipments($users, $masterData);
        $this->command->info("ShipmentWithLoadingSeeder completed successfully!");
    }

    private function createRoles(): void
    {
        foreach (["super_admin", "office_admin", "field_coordinator", "customer"] as $r) {
            Role::firstOrCreate(["name" => $r, "guard_name" => "web"]);
        }
        $this->command->info("Roles verified/created.");
    }

    private function createUsers(): array
    {
        $fc = User::firstOrCreate(
            ["email" => "fc@test.com"],
            ["name" => "Field Coordinator", "password" => Hash::make("password123")]
        );
        $fc->syncRoles(["field_coordinator"]);
        $this->command->info("User created/verified: fc@test.com");

        $admin = User::firstOrCreate(
            ["email" => "admin@test.com"],
            ["name" => "Admin User", "password" => Hash::make("password123")]
        );
        $admin->syncRoles(["super_admin"]);
        $this->command->info("User created/verified: admin@test.com");

        return ["fc" => $fc, "admin" => $admin];
    }

    private function createMasterData(): array
    {
        $branch = Branch::firstOrCreate(["code" => "JKT"], ["name" => "Jakarta"]);
        $port = Port::firstOrCreate(["code" => "TPRI"], ["name" => "Tanjung Priok", "city" => "Jakarta"]);
        
        $cities = [];
        foreach (["Jakarta", "Surabaya", "Makassar"] as $cityName) {
            $cities[$cityName] = City::firstOrCreate(
                ["name" => $cityName],
                [
                    "province" => $cityName === "Jakarta" ? "DKI Jakarta" : ($cityName === "Surabaya" ? "Jawa Timur" : "Sulawesi Selatan"),
                    "country" => "Indonesia",
                    "slug" => str()->slug($cityName),
                    "is_active" => true
                ]
            );
        }

        $fc = User::where("email", "fc@test.com")->first();
        $depot = Depot::firstOrCreate(
            ["code" => "DEP-JKT-01"],
            [
                "name" => "Depo Tanjung Priok",
                "mode" => "sea",
                "port_id" => $port->id,
                "branch_id" => $branch->id,
                "coordinator_user_id" => $fc?->id,
                "address" => "Jl. Pelabuhan Tanjung Priok, Jakarta Utara",
                "service_types" => ["sea_freight", "container_handling"]
            ]
        );

        $customers = [];
        $customers[] = Customer::firstOrCreate(
            ["email" => "customer1@test.com"],
            [
                "code" => "CUST-001",
                "type" => CustomerType::Company,
                "name" => "PT Maju Jaya Shipping",
                "phone" => "021-1234567",
                "npwp" => "09.123.456.7-123.000",
                "pic_name" => "Budi Santoso",
                "pic_phone" => "081234567890",
                "city_id" => $cities["Jakarta"]->id,
                "address" => "Jl. Sudirman No. 123, Jakarta Selatan"
            ]
        );
        $customers[] = Customer::firstOrCreate(
            ["email" => "customer2@test.com"],
            [
                "code" => "CUST-002",
                "type" => CustomerType::Individual,
                "name" => "Ahmad Fauzi",
                "phone" => "081298765432",
                "nik" => "3171234567890001",
                "pic_name" => "Ahmad Fauzi",
                "pic_phone" => "081298765432",
                "city_id" => $cities["Surabaya"]->id,
                "address" => "Jl. Pemuda No. 45, Surabaya"
            ]
        );

        return [
            "branch" => $branch,
            "port" => $port,
            "depot" => $depot,
            "customers" => $customers,
            "cities" => $cities
        ];
    }

    private function createShipments(array $users, array $masterData): void
    {
        $fc = $users["fc"];
        $branch = $masterData["branch"];
        $depot = $masterData["depot"];
        $customers = $masterData["customers"];
        $cities = $masterData["cities"];
        $port = $masterData["port"];

        $s1 = Shipment::firstOrCreate(
            ["code" => "JSS0426SEA0001"],
            [
                "customer_id" => $customers[0]->id,
                "origin_city_id" => $cities["Jakarta"]->id,
                "destination_city_id" => $cities["Makassar"]->id,
                "branch_id" => $branch->id,
                "mode" => ShipmentMode::Sea,
                "status" => ShipmentStatus::Transit,
                "pic_name" => "Budi Santoso",
                "pic_phone" => "081234567890",
                "container_size" => "40ft",
                "container_qty" => 1,
                "container_no" => "TGHU1234567",
                "seal_no" => "SL123456",
                "packages_total" => 50,
                "cbm_total" => 45.5,
                "weight_total" => 12000.00,
                "vessel_name" => "KM. JAYA SAKTI",
                "voyage" => "V.123A",
                "pol" => "Tanjung Priok",
                "pod" => "Makassar",
                "pol_id" => $port->id,
                "etd" => now()->subDays(5),
                "eta" => now()->addDays(10),
                "assigned_depot_id" => $depot->id,
                "priority" => "normal",
                "notes" => "Complete loading session with GO decision",
                "requested_at" => now()->subDays(7)
            ]
        );
        $this->command->info("Shipment 1: " . $s1->code);
        $this->createCompleteLoadingSession($s1, $depot, $fc, "LD-2026-0001");
        $this->createShipmentTracks($s1, $fc);

        $s2 = Shipment::firstOrCreate(
            ["code" => "JSS0426SEA0002"],
            [
                "customer_id" => $customers[1]->id,
                "origin_city_id" => $cities["Surabaya"]->id,
                "destination_city_id" => $cities["Jakarta"]->id,
                "branch_id" => $branch->id,
                "mode" => ShipmentMode::Sea,
                "status" => ShipmentStatus::Pending,
                "pic_name" => "Ahmad Fauzi",
                "pic_phone" => "081298765432",
                "container_size" => "20ft",
                "container_qty" => 1,
                "container_no" => "ABCU7654321",
                "seal_no" => "SL654321",
                "packages_total" => 25,
                "cbm_total" => 22.5,
                "weight_total" => 8000.00,
                "vessel_name" => "KM. NUSANTARA",
                "voyage" => "V.456B",
                "pol" => "Tanjung Perak",
                "pod" => "Tanjung Priok",
                "assigned_depot_id" => $depot->id,
                "priority" => "urgent",
                "notes" => "In-progress loading session",
                "requested_at" => now()->subDays(2)
            ]
        );
        $this->command->info("Shipment 2: " . $s2->code);
        $this->createInProgressLoadingSession($s2, $depot, $fc, "LD-2026-0002");

        $s3 = Shipment::firstOrCreate(
            ["code" => "JSS0426SEA0003"],
            [
                "customer_id" => $customers[0]->id,
                "origin_city_id" => $cities["Jakarta"]->id,
                "destination_city_id" => $cities["Surabaya"]->id,
                "branch_id" => $branch->id,
                "mode" => ShipmentMode::Sea,
                "status" => ShipmentStatus::Draft,
                "pic_name" => "Budi Santoso",
                "pic_phone" => "081234567890",
                "container_size" => "40ft",
                "container_qty" => 1,
                "packages_total" => 40,
                "cbm_total" => 35.0,
                "weight_total" => 10000.00,
                "vessel_name" => "KM. PELITA NUSANTARA",
                "voyage" => "V.789C",
                "pol" => "Tanjung Priok",
                "pod" => "Tanjung Perak",
                "priority" => "normal",
                "notes" => "Draft shipment - no loading session yet"
            ]
        );
        $this->command->info("Shipment 3: " . $s3->code . " (Draft - no loading session)");
    }

    private function createCompleteLoadingSession(Shipment $s, Depot $d, User $fc, string $code): void
    {
        $session = LoadingSession::firstOrCreate(
            ["code" => $code],
            [
                "shipment_id" => $s->id,
                "depot_id" => $d->id,
                "coordinator_user_id" => $fc->id,
                "branch_id" => $d->branch_id,
                "operation_type" => LoadingOperationType::Loading,
                "status" => LoadingStatus::Completed,
                "started_at" => now()->subDays(6),
                "completed_at" => now()->subDays(6)->addHours(3),
                "mp_attendance_completed" => true,
                "health_check_completed" => true,
                "apd_check_completed" => true,
                "equipment_check_completed" => true,
                "rack_container_check_completed" => true,
                "unit_check_completed" => true,
                "stock_apd_check_completed" => true,
                "manpower_availability_completed" => true,
                "final_decision_completed" => true,
                "mp_required" => 8,
                "mp_present" => 8,
                "mp_absent" => 0,
                "mp_sick" => 0,
                "mp_sufficient" => true,
                "mp_fit_count" => 8,
                "mp_unfit_count" => 0,
                "apd_complete" => true,
                "apd_clean" => true,
                "equipment_safe" => true,
                "rack_container_safe" => true,
                "rack_pillars_ok" => true,
                "drop_floor_ok" => true,
                "container_structure_ok" => true,
                "unit_measurements_ok" => true,
                "stock_apd_sufficient" => true,
                "final_decision_status" => FinalDecisionStatus::Go,
                "final_decision_notes" => "Semua pemeriksaan berhasil",
                "final_decision_by" => $fc->id,
                "final_decision_at" => now()->subDays(6)->addHours(3),
                "gps_latitude" => -6.1256,
                "gps_longitude" => 106.8748,
                "location_address" => "Depo Tanjung Priok",
                "critical_issues_count" => 0,
                "warning_issues_count" => 0
            ]
        );
        $this->command->info("  Loading Session: " . $session->code . " (Completed - GO)");
        $this->createRackContainerCheck($session, $fc);
        $this->createEquipmentCheck($session, $fc);
        $this->createUnitCheck($session, $fc);
        $this->createFinalDecision($session, $fc);
    }

    private function createInProgressLoadingSession(Shipment $s, Depot $d, User $fc, string $code): void
    {
        $session = LoadingSession::firstOrCreate(
            ["code" => $code],
            [
                "shipment_id" => $s->id,
                "depot_id" => $d->id,
                "coordinator_user_id" => $fc->id,
                "branch_id" => $d->branch_id,
                "operation_type" => LoadingOperationType::Loading,
                "status" => LoadingStatus::RackContainerCheck,
                "started_at" => now()->subHours(2),
                "mp_attendance_completed" => true,
                "health_check_completed" => true,
                "apd_check_completed" => true,
                "rack_container_check_completed" => true,
                "rack_container_safe" => true,
                "rack_pillars_ok" => true,
                "drop_floor_ok" => true,
                "container_structure_ok" => true,
                "mp_required" => 6,
                "mp_present" => 6,
                "mp_sufficient" => true,
                "mp_fit_count" => 6,
                "gps_latitude" => -6.1256,
                "gps_longitude" => 106.8748,
                "location_address" => "Depo Tanjung Priok"
            ]
        );
        $this->command->info("  Loading Session: " . $session->code . " (In Progress - Rack Check done)");
        $this->createRackContainerCheck($session, $fc);
    }

    private function createRackContainerCheck(LoadingSession $s, User $fc): void
    {
        RackContainerCheck::firstOrCreate(
            ["loading_session_id" => $s->id],
            [
                "pillar_a_condition" => RackPillarCondition::StrongAndStraight,
                "pillar_a_pulley_hook" => RackPulleyHookStatus::PresentAndStrong,
                "pillar_a_tie_status" => RackTieStatus::TiedStrong,
                "pillar_b_condition" => RackPillarCondition::StrongAndStraight,
                "pillar_b_pulley_hook" => RackPulleyHookStatus::PresentAndStrong,
                "pillar_b_tie_status" => RackTieStatus::TiedStrong,
                "pillar_c_condition" => RackPillarCondition::StrongAndStraight,
                "pillar_c_pulley_hook" => RackPulleyHookStatus::PresentAndStrong,
                "pillar_c_tie_status" => RackTieStatus::TiedStrong,
                "pillar_d_condition" => RackPillarCondition::StrongAndStraight,
                "pillar_d_pulley_hook" => RackPulleyHookStatus::PresentAndStrong,
                "pillar_d_tie_status" => RackTieStatus::TiedStrong,
                "drop_floor_front_condition" => DropFloorCondition::Straight,
                "drop_floor_front_strength" => DropFloorStrength::Strong,
                "drop_floor_front_iron_hook" => IronHookStatus::Present,
                "drop_floor_rear_condition" => DropFloorCondition::Straight,
                "drop_floor_rear_strength" => DropFloorStrength::Strong,
                "drop_floor_rear_iron_hook" => IronHookStatus::Present,
                "container_wall_status" => ContainerStructureStatus::Good,
                "container_floor_status" => ContainerStructureStatus::Good,
                "container_roof_status" => ContainerStructureStatus::Good,
                "all_pillars_safe" => true,
                "all_drop_floors_safe" => true,
                "container_structure_safe" => true,
                "overall_safe" => true,
                "critical_issues_count" => 0,
                "warning_issues_count" => 0,
                "checked_by" => $fc->id,
                "checked_at" => $s->started_at ?? now()
            ]
        );
        $this->command->info("    Rack Container Check created");
    }

    private function createEquipmentCheck(LoadingSession $s, User $fc): void
    {
        EquipmentCheck::firstOrCreate(
            ["loading_session_id" => $s->id],
            [
                "pulley_top_status" => "ok",
                "pulley_bottom_status" => "ok",
                "mono_rope_condition" => "ok",
                "chain_strength" => "ok",
                "bolt_nut_status" => "tight",
                "bamboo_condition" => "ok",
                "ladder_stability" => "stable",
                "sponds_cleanliness" => "clean",
                "pulley_safe" => true,
                "mono_rope_safe" => true,
                "chain_safe" => true,
                "bolt_nut_safe" => true,
                "bamboo_safe" => true,
                "ladder_safe" => true,
                "sponds_safe" => true,
                "overall_safe" => true,
                "critical_issues_count" => 0,
                "warning_issues_count" => 0,
                "checked_by" => $fc->id,
                "checked_at" => $s->started_at ?? now()
            ]
        );
        $this->command->info("    Equipment Check created");
    }

    private function createUnitCheck(LoadingSession $s, User $fc): void
    {
        UnitCheck::firstOrCreate(
            ["loading_session_id" => $s->id],
            [
                "unit_type" => "truck",
                "unit_plate_number" => "B 1234 ABC",
                "distance_front_rh" => 120,
                "distance_rear_rh" => 115,
                "distance_back_door" => 180,
                "distance_rear_lh" => 118,
                "distance_front_lh" => 122,
                "drop_floor_front_height" => 110,
                "drop_floor_rear_height" => 108,
                "container_roof_distance" => 280,
                "validation_ranges" => UnitCheck::getDefaultValidationRanges(),
                "measurements_valid" => true,
                "unit_safe_for_loading" => true,
                "critical_issues_count" => 0,
                "warning_issues_count" => 0,
                "checked_by" => $fc->id,
                "checked_at" => $s->started_at ?? now()
            ]
        );
        $this->command->info("    Unit Check created");
    }

    private function createFinalDecision(LoadingSession $s, User $fc): void
    {
        LoadingFinalDecision::firstOrCreate(
            ["loading_session_id" => $s->id],
            [
                "status" => FinalDecisionStatus::Go,
                "category" => "automatic",
                "reason" => "Semua pemeriksaan berhasil",
                "notes" => "Loading dapat dilanjutkan",
                "pillar_issues" => false,
                "drop_floor_issues" => false,
                "pulley_issues" => false,
                "apd_incomplete" => false,
                "mp_unhealthy" => false,
                "equipment_unsafe" => false,
                "unit_unsafe" => false,
                "stock_apd_insufficient" => false,
                "mp_insufficient" => false,
                "requested_by" => $fc->id,
                "requested_at" => $s->started_at ?? now(),
                "approved_by" => $fc->id,
                "approved_at" => $s->completed_at ?? now()
            ]
        );
        $this->command->info("    Final Decision (GO) created");
    }

    private function createShipmentTracks(Shipment $s, User $fc): void
    {
        $s->ensureTrackSkeleton();
        $tracks = [
            ["status" => TrackStatus::Stuffing, "tracked_at" => now()->subDays(6), "note" => "Stuffing selesai", "location" => "Depo"],
            ["status" => TrackStatus::UnitLoading, "tracked_at" => now()->subDays(5), "note" => "Dimuat ke kapal", "location" => "Pelabuhan"]
        ];
        foreach ($tracks as $t) {
            ShipmentTrack::updateOrCreate(
                ["shipment_id" => $s->id, "status" => $t["status"]],
                [
                    "tracked_at" => $t["tracked_at"],
                    "note" => $t["note"],
                    "location" => $t["location"],
                    "created_by" => $fc->id,
                    "updated_by" => $fc->id
                ]
            );
        }
        $this->command->info("  Shipment tracks created");
    }
}

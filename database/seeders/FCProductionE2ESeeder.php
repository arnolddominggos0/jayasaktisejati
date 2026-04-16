<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\ContainerStructureStatus;
use App\Enums\CustomerType;
use App\Enums\DropFloorCondition;
use App\Enums\DropFloorStrength;
use App\Enums\FinalDecisionStatus;
use App\Enums\IronHookStatus;
use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Enums\MPCheckStatus;
use App\Enums\PpeCondition;
use App\Enums\PpeType;
use App\Enums\RackPillarCondition;
use App\Enums\RackPulleyHookStatus;
use App\Enums\RackTieStatus;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
// use App\Models\BriefingChecklist;
use App\Models\BriefingSession;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\EquipmentCheck;
use App\Models\LoadingSession;
use App\Models\Manpower;
use App\Models\Port;
use App\Models\RackContainerCheck;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\UnitCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FCProductionE2ESeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║  FC PRODUCTION END-TO-END SEEDER                            ║');
        $this->command->info('║  Flow: Briefing MP → Cek Kesehatan → Cek APD → Loading    ║');
        $this->command->info('║  → Unloading → Door-to-Customer                            ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');

        $this->createRoles();
        $data = $this->createMasterData();

        $this->command->info('');
        $this->command->info('─ Generating End-to-End Flow Data ─');

        $this->createCompletedFlow($data);
        $this->createInProgressFlow($data);
        $this->createUnloadingFlow($data);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║  SEEDER COMPLETED                                           ║');
        $this->command->info('╠══════════════════════════════════════════════════════════════╣');
        $this->command->info('║  FC Login: arnolddominggos7@gmail.com / arnold123            ║');
        $this->command->info('║  Panel:     /fc                                             ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
    }

    private function createRoles(): void
    {
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->command->info('  ✓ Roles verified');
    }

    private function createMasterData(): array
    {
        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdo = Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);
        $btn = Branch::firstOrCreate(['code' => 'BTG'], ['name' => 'Bontang']);

        $cityJkt = City::firstOrCreate(['slug' => 'jakarta'], ['name' => 'Jakarta', 'country' => 'Indonesia']);
        $cityMdo = City::firstOrCreate(['slug' => 'manado'], ['name' => 'Manado', 'country' => 'Indonesia']);
        $cityBtn = City::firstOrCreate(['slug' => 'bontang'], ['name' => 'Bontang', 'country' => 'Indonesia']);
        $citySby = City::firstOrCreate(['slug' => 'surabaya'], ['name' => 'Surabaya', 'country' => 'Indonesia']);
        $cityBtg = City::firstOrCreate(['slug' => 'bitung'], ['name' => 'Bitung', 'country' => 'Indonesia']);

        $portTpri = Port::firstOrCreate(['code' => 'TPRI'], ['name' => 'Tanjung Priok', 'city' => 'Jakarta']);
        $portBtg = Port::firstOrCreate(['code' => 'BTG'], ['name' => 'Bitung Port', 'city' => 'Manado']);
        $portBtn = Port::firstOrCreate(['code' => 'BTNP'], ['name' => 'Bontang Port', 'city' => 'Bontang']);
        $portSby = Port::firstOrCreate(['code' => 'TBY'], ['name' => 'Tanjung Perak', 'city' => 'Surabaya']);

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

        $customerTAM = Customer::firstOrCreate(
            ['code' => 'TAM-001'],
            [
                'name' => 'PT Toyota Astra Motor',
                'email' => 'logistik@toyota.astra.co.id',
                'phone' => '021-8195001',
                'type' => CustomerType::Company->value,
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '08129876000',
                'city_id' => $cityJkt->id,
            ]
        );

        $customerIND = Customer::firstOrCreate(
            ['code' => 'IND-001'],
            [
                'name' => 'PT Indocement Tunggal Prakarsa',
                'email' => 'shipping@indocement.co.id',
                'phone' => '021-6591234',
                'type' => CustomerType::Company->value,
                'pic_name' => 'Hendra Wijaya',
                'pic_phone' => '08131234000',
                'city_id' => $cityJkt->id,
            ]
        );

        // FC User (Arnold)
        $fcArnold = User::firstOrCreate(
            ['email' => 'arnolddominggos7@gmail.com'],
            [
                'name' => 'Arnold Dominggos',
                'password' => Hash::make('arnold123'),
                'branch_id' => $jkt->id,
                'email_verified_at' => now(),
            ]
        );
        $fcArnold->syncRoles(['field_coordinator']);

        $depotJkt->update(['coordinator_user_id' => $fcArnold->id]);

        // Admin user (if not exists)
        $admin = User::firstOrCreate(
            ['email' => 'admin@jayasaktisejati.com'],
            [
                'name' => 'Admin JSS',
                'password' => Hash::make('JSS@Admin2026!'),
                'branch_id' => $jkt->id,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['super_admin']);

        // Manpower - 10 orang di Depot Tanjung Priok
        $mpData = [
            ['name' => 'Arnold Dominggos', 'phone' => '08129876001', 'skills' => ['forklift', 'loading', 'unloading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Heru Suryadi', 'phone' => '081212345001', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Odih Pratama', 'phone' => '081312345001', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Rustam Hidayat', 'phone' => '081412345001', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Markus Simatupang', 'phone' => '081512345001', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Soleh Wahidin', 'phone' => '081612345001', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift']],
            ['name' => 'Habibullah Rizki', 'phone' => '081712345001', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Cemen Sitorus', 'phone' => '081812345001', 'skills' => ['unloading', 'loading'], 'certs' => ['K3']],
            ['name' => 'Doni Saputra', 'phone' => '081912345001', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Eko Prasetyo', 'phone' => '082012345001', 'skills' => ['loading', 'unloading', 'forklift'], 'certs' => ['K3', 'SIO Forklift']],
        ];

        $manpowerList = [];
        foreach ($mpData as $mp) {
            $manpowerList[] = Manpower::firstOrCreate(
                ['name' => $mp['name']],
                [
                    'domain' => 'sea_freight',
                    'skills' => $mp['skills'],
                    'certs' => $mp['certs'],
                    'phone' => $mp['phone'],
                    'branch_id' => $jkt->id,
                    'depot_id' => $depotJkt->id,
                    'active' => true,
                ]
            );
        }

        $this->command->info('  ✓ Master data created');
        $this->command->info('  ✓ FC User: arnolddominggos7@gmail.com / arnold123');
        $this->command->info('  ✓ Manpower: '.count($manpowerList).' MP di Depot Tanjung Priok');

        return compact(
            'fcArnold', 'admin', 'jkt', 'mdo', 'btn',
            'cityJkt', 'cityMdo', 'cityBtn', 'citySby', 'cityBtg',
            'portTpri', 'portBtg', 'portBtn', 'portSby',
            'depotJkt', 'depotMdo',
            'customerTAM', 'customerIND', 'manpowerList'
        );
    }

    private function createCompletedFlow(array $data): void
    {
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════════════');
        $this->command->info('  FLOW 1: COMPLETED - Full End-to-End (Jakarta → Bontang)');
        $this->command->info('  Briefing → Cek Kesehatan → Cek APD → Loading → Unloading → Delivered');
        $this->command->info('════════════════════════════════════════════════════════════');

        $fc = $data['fcArnold'];
        $depot = $data['depotJkt'];
        $branch = $data['jkt'];
        $customer = $data['customerTAM'];
        $cityJkt = $data['cityJkt'];
        $cityBtn = $data['cityBtn'];
        $portTpri = $data['portTpri'];
        $portBtn = $data['portBtn'];
        $mpList = $data['manpowerList'];

        $briefingDate = Carbon::parse('2026-04-14');

        // ── 1. BRIEFING SESSION ──
        $briefing = BriefingSession::firstOrCreate(
            ['date' => $briefingDate->format('Y-m-d'), 'depot_id' => $depot->id],
            [
                'coordinator_user_id' => $fc->id,
                'notes' => 'Briefing Harian - Senin, 14 Apr 2026. Topik: SOP Loading Kendaraan ke Container Rack, Pengecekan APD, Koordinasi dengan Terminal Tanjung Priok.',
                'summary_headcount' => 10,
                'summary_sufficient' => true,
                'mp_check_status' => MPCheckStatus::Approved->value,
                'approved_at' => $briefingDate->copy()->setTime(7, 30),
                'approved_by' => $fc->id,
            ]
        );
        $this->command->info('  ✓ Briefing Session created (ID: '.$briefing->id.')');

        // ── 2. BRIEFING ATTENDANCE + CEK KESEHATAN + CEK APD ──
        $absencePattern = [
            'Rustam Hidayat' => 'sick',
            'Doni Saputra' => 'absent',
        ];

        foreach ($mpList as $mp) {
            $mpName = $mp->name;
            $isSick = isset($absencePattern[$mpName]) && $absencePattern[$mpName] === 'sick';
            $isAbsent = isset($absencePattern[$mpName]) && $absencePattern[$mpName] === 'absent';

            // Check if attendance already exists
            $existingAttendance = BriefingAttendance::where('session_id', $briefing->id)
                ->where('manpower_id', $mp->id)
                ->first();

            if ($existingAttendance) {
                // Skip if already exists
                continue;
            }

            if ($isSick) {
                $attendance = BriefingAttendance::create([
                    'session_id' => $briefing->id,
                    'manpower_id' => $mp->id,
                    'attendance_status' => AttendanceStatus::Sick->value,
                    'temperature' => 38.2,
                    'bp_systolic' => 142,
                    'bp_diastolic' => 95,
                    'remark' => 'Demam tinggi, flu berat',
                    'has_ppe' => false,
                ]);

                continue;
            }

            if ($isAbsent) {
                BriefingAttendance::create([
                    'session_id' => $briefing->id,
                    'manpower_id' => $mp->id,
                    'attendance_status' => AttendanceStatus::Absent->value,
                ]);

                continue;
            }

            // ── CEK KESEHATAN ──
            $temp = round(36.0 + (rand(0, 15) / 10), 1);
            $bpSys = rand(110, 132);
            $bpDia = rand(68, 88);
            $hasComplaint = rand(1, 10) > 8;

            $attendance = BriefingAttendance::create([
                'session_id' => $briefing->id,
                'manpower_id' => $mp->id,
                'attendance_status' => AttendanceStatus::Present->value,
                'temperature' => $temp,
                'bp_systolic' => $bpSys,
                'bp_diastolic' => $bpDia,
                'remark' => $hasComplaint ? 'Sakit kepala ringan' : null,
                'has_ppe' => true,
            ]);

            // ── CEK APD (Alat Pelindung Diri) ──
            $ppeItems = [
                ['type' => PpeType::Helm->value, 'condition' => PpeCondition::Baik->value],
                ['type' => PpeType::Rompi->value, 'condition' => PpeCondition::Baik->value],
                ['type' => PpeType::Sepatu->value, 'condition' => rand(1, 10) > 2 ? PpeCondition::Baik->value : PpeCondition::Rusak->value],
                ['type' => PpeType::SarungTangan->value, 'condition' => PpeCondition::Baik->value],
            ];

            foreach ($ppeItems as $ppe) {
                BriefingAttendancePpeItem::create([
                    'attendance_id' => $attendance->id,
                    'ppe_type' => $ppe['type'],
                    'condition' => $ppe['condition'],
                ]);
            }
        }
        $this->command->info('  ✓ Attendance + Cek Kesehatan + Cek APD for all MP');

        // ── Briefing Checklists ── (Skipped - table not in DB)
        // $checklists = [
        //     ['item' => 'Helm Safety', 'type' => 'ppe'],
        //     ['item' => 'Rompi Reflektif', 'type' => 'ppe'],
        //     ['item' => 'Sepatu Safety', 'type' => 'ppe'],
        //     ['item' => 'Sarung Tangan', 'type' => 'ppe'],
        //     ['item' => 'Pemahaman Muatan & Cara Loading', 'type' => 'safety'],
        //     ['item' => 'Prosedur Darurat & Evakuasi', 'type' => 'safety'],
        //     ['item' => 'Cek Rack & Alat Loading', 'type' => 'equipment'],
        //     ['item' => 'Koordinasi dengan Terminal', 'type' => 'safety'],
        // ];
        //
        // foreach ($checklists as $cl) {
        //     BriefingChecklist::create([
        //         'session_id' => $briefing->id,
        //         'item' => $cl['item'],
        //         'type' => $cl['type'],
        //         'status' => 'ok',
        //     ]);
        // }
        $this->command->info('  ✓ Briefing Checklists created (skipped - table not in DB)');

        // ── 3. SHIPMENT ──
        $shipment = Shipment::firstOrCreate(
            ['code' => 'JSS-260414-001'],
            [
                'customer_id' => $customer->id,
                'receiver_id' => $customer->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $cityBtn->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Delivered->value,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'vehicle_loading' => 'rack',
                'container_size' => '40ft',
                'container_qty' => 2,
                'packages_total' => 12,
                'cbm_total' => 120,
                'weight_total' => 18000,
                'pol_id' => $portTpri->id,
                'pod_id' => $portBtn->id,
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '08129876000',
                'delivery_contact_name' => 'Warehouse Bontang',
                'delivery_contact_phone' => '081555123456',
                'eta' => Carbon::parse('2026-04-18'),
                'notes' => 'Pengiriman Toyota - 12 unit avanza/brio ke Bontang via rack container',
            ]
        );
        $this->command->info('  ✓ Shipment created: '.$shipment->code);

        // ── 4. TRACKING - Full End-to-End ──
        $tracks = [
            ['status' => TrackStatus::Pickup, 'time' => '2026-04-14 06:00', 'note' => 'Unit dijemput dari pool TAM Cakung'],
            ['status' => TrackStatus::Handover, 'time' => '2026-04-14 07:00', 'note' => 'Unit diterima di Depo Tanjung Priok oleh FC Arnold'],
            ['status' => TrackStatus::Stuffing, 'time' => '2026-04-14 08:00', 'note' => 'Proses stuffing & segel container dimulai'],
            ['status' => TrackStatus::DeliveryToPort, 'time' => '2026-04-14 12:00', 'note' => 'Container diantar ke Terminal TermPri Tanjung Priok'],
            ['status' => TrackStatus::Stacking, 'time' => '2026-04-14 14:00', 'note' => 'Container stacking di yard Terminal'],
            ['status' => TrackStatus::UnitLoading, 'time' => '2026-04-14 19:00', 'note' => 'Container loading ke kapal Tandem Mas (Voy. 2514A)'],
            ['status' => TrackStatus::OnShip, 'time' => '2026-04-14 20:00', 'note' => 'Unit di atas kapal, konfirmasi loading selesai'],
            ['status' => TrackStatus::VesselDepart, 'time' => '2026-04-15 06:00', 'note' => 'Kapal Tandem Mas berangkat dari Tanjung Priok'],
            ['status' => TrackStatus::VesselArrival, 'time' => '2026-04-17 14:00', 'note' => 'Kapal tiba di Pelabuhan Bontang'],
            ['status' => TrackStatus::Unloading, 'time' => '2026-04-17 16:00', 'note' => 'Proses pembongkaran dari kapal'],
            ['status' => TrackStatus::DeliveryToCustomer, 'time' => '2026-04-18 08:00', 'note' => 'Unit diantar ke gudang TAM Bontang'],
            ['status' => TrackStatus::Delivered, 'time' => '2026-04-18 10:00', 'note' => 'Unit diterima oleh customer di Bontang - DOOR TO DOOR COMPLETED'],
        ];

        foreach ($tracks as $t) {
            ShipmentTrack::create([
                'shipment_id' => $shipment->id,
                'status' => $t['status']->value,
                'status_normalized' => true,
                'tracked_at' => Carbon::parse($t['time']),
                'note' => $t['note'],
                'created_by' => $fc->id,
            ]);
        }
        $this->command->info('  ✓ Tracking: Full end-to-end (12 checkpoints) - DOOR TO DOOR COMPLETED');

        // ── 5. LOADING SESSION - Full Checkpoints Completed ──
        $loading = LoadingSession::firstOrCreate(
            ['code' => 'LD-2026-0001'],
            [
                'shipment_id' => $shipment->id,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'branch_id' => $branch->id,
                'briefing_session_id' => $briefing->id,
                'operation_type' => LoadingOperationType::Loading->value,
                'status' => LoadingStatus::Completed->value,
                'mp_required' => 8,
                'mp_present' => 8,
                'mp_absent' => 2,
                'mp_sick' => 1,
                'mp_sufficient' => true,
                'mp_fit_count' => 7,
                'mp_unfit_count' => 1,
                'apd_complete' => true,
                'apd_clean' => true,
                'equipment_safe' => true,
                'rack_container_safe' => true,
                'unit_measurements_ok' => true,
                'stock_apd_sufficient' => true,
                'mp_attendance_completed' => true,
                'health_check_completed' => true,
                'apd_check_completed' => true,
                'equipment_check_completed' => true,
                'rack_container_check_completed' => true,
                'unit_check_completed' => true,
                'stock_apd_check_completed' => true,
                'manpower_availability_completed' => true,
                'final_decision_completed' => true,
                'final_decision_status' => FinalDecisionStatus::Go->value,
                'final_decision_by' => $fc->id,
                'final_decision_at' => $briefingDate->copy()->setTime(9, 0),
                'started_at' => $briefingDate->copy()->setTime(8, 0),
                'completed_at' => $briefingDate->copy()->setTime(15, 30),
                'general_notes' => 'Loading selesai - Semua checkpoint OK. 8 dari 8 MP yang hadir sehat & APD lengkap.',
            ]
        );
        $this->command->info('  ✓ Loading Session: COMPLETED (all checkpoints passed)');

        // ── 5a. Rack Container Check ──
        RackContainerCheck::create([
            'loading_session_id' => $loading->id,
            'pillar_a_condition' => RackPillarCondition::StrongAndStraight->value,
            'pillar_a_pulley_hook' => RackPulleyHookStatus::PresentAndStrong->value,
            'pillar_a_tie_status' => RackTieStatus::TiedStrong->value,
            'pillar_b_condition' => RackPillarCondition::StrongAndStraight->value,
            'pillar_b_pulley_hook' => RackPulleyHookStatus::PresentAndStrong->value,
            'pillar_b_tie_status' => RackTieStatus::TiedStrong->value,
            'pillar_c_condition' => RackPillarCondition::StrongAndStraight->value,
            'pillar_c_pulley_hook' => RackPulleyHookStatus::PresentAndStrong->value,
            'pillar_c_tie_status' => RackTieStatus::TiedStrong->value,
            'pillar_d_condition' => RackPillarCondition::StrongAndStraight->value,
            'pillar_d_pulley_hook' => RackPulleyHookStatus::PresentAndStrong->value,
            'pillar_d_tie_status' => RackTieStatus::TiedStrong->value,
            'drop_floor_front_condition' => DropFloorCondition::Straight->value,
            'drop_floor_front_strength' => DropFloorStrength::Strong->value,
            'drop_floor_front_iron_hook' => IronHookStatus::Present->value,
            'drop_floor_rear_condition' => DropFloorCondition::Straight->value,
            'drop_floor_rear_strength' => DropFloorStrength::Strong->value,
            'drop_floor_rear_iron_hook' => IronHookStatus::Present->value,
            'container_wall_status' => ContainerStructureStatus::Good->value,
            'container_floor_status' => ContainerStructureStatus::Good->value,
            'container_roof_status' => ContainerStructureStatus::Good->value,
            'all_pillars_safe' => true,
            'all_drop_floors_safe' => true,
            'container_structure_safe' => true,
            'overall_safe' => true,
            'checked_by' => $fc->id,
            'checked_at' => $briefingDate->copy()->setTime(10, 0),
        ]);
        $this->command->info('  ✓ Rack Container Check: ALL PILLARS & DROP FLOORS SAFE');

        // ── 5b. Equipment Check ──
        EquipmentCheck::create([
            'loading_session_id' => $loading->id,
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
            'checked_at' => $briefingDate->copy()->setTime(10, 30),
        ]);
        $this->command->info('  ✓ Equipment Check: ALL EQUIPMENT SAFE');

        // ── 5c. Unit Check ──
        UnitCheck::create([
            'loading_session_id' => $loading->id,
            'unit_plate_number' => 'B 1234 JKT',
            'distance_front_rh' => 120,
            'distance_rear_rh' => 118,
            'distance_back_door' => 180,
            'distance_rear_lh' => 119,
            'distance_front_lh' => 121,
            'drop_floor_front_height' => 110,
            'drop_floor_rear_height' => 108,
            'container_roof_distance' => 280,
            'measurements_valid' => true,
            'unit_safe_for_loading' => true,
            'checked_by' => $fc->id,
            'checked_at' => $briefingDate->copy()->setTime(11, 0),
        ]);
        $this->command->info('  ✓ Unit Check: DIMENSIONS OK, SAFE FOR LOADING');
    }

    private function createInProgressFlow(array $data): void
    {
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════════════');
        $this->command->info('  FLOW 2: IN PROGRESS - Jakarta → Manado (via Bitung)');
        $this->command->info('  Status: Di Depot (Handover) → Loading Session In Progress');
        $this->command->info('════════════════════════════════════════════════════════════');

        $fc = $data['fcArnold'];
        $depot = $data['depotJkt'];
        $branch = $data['jkt'];
        $customer = $data['customerTAM'];
        $cityJkt = $data['cityJkt'];
        $cityMdo = $data['cityMdo'];
        $portTpri = $data['portTpri'];
        $portBtg = $data['portBtg'];
        $mpList = $data['manpowerList'];

        $briefingDate = Carbon::parse('2026-04-16');

        // ── 1. BRIEFING SESSION ──
        $briefing = BriefingSession::firstOrCreate(
            ['date' => $briefingDate->format('Y-m-d'), 'depot_id' => $depot->id],
            [
                'coordinator_user_id' => $fc->id,
                'notes' => 'Briefing Harian - Rabu, 16 Apr 2026. Topik: penanganan khusus untuk unit SUV, pengecekan rack container double-check.',
                'summary_headcount' => 10,
                'summary_sufficient' => true,
                'mp_check_status' => MPCheckStatus::Approved->value,
                'approved_at' => $briefingDate->copy()->setTime(7, 20),
                'approved_by' => $fc->id,
            ]
        );

        // Attendance - 1 sick
        foreach ($mpList as $i => $mp) {
            $isSick = $mp->name === 'Markus Simatupang';

            // Check if attendance already exists
            $existingAttendance = BriefingAttendance::where('session_id', $briefing->id)
                ->where('manpower_id', $mp->id)
                ->first();

            if ($existingAttendance) {
                continue;
            }

            if ($isSick) {
                BriefingAttendance::create([
                    'session_id' => $briefing->id,
                    'manpower_id' => $mp->id,
                    'attendance_status' => AttendanceStatus::Sick->value,
                    'temperature' => 37.9,
                    'bp_systolic' => 138,
                    'bp_diastolic' => 92,
                    'remark' => 'Sakit perut, tidak bisa kerja berat',
                    'has_ppe' => false,
                ]);

                continue;
            }

            $temp = round(36.0 + (rand(0, 12) / 10), 1);
            $bpSys = rand(112, 128);
            $bpDia = rand(70, 85);

            $attendance = BriefingAttendance::create([
                'session_id' => $briefing->id,
                'manpower_id' => $mp->id,
                'attendance_status' => AttendanceStatus::Present->value,
                'temperature' => $temp,
                'bp_systolic' => $bpSys,
                'bp_diastolic' => $bpDia,
                'remark' => null,
                'has_ppe' => true,
            ]);

            foreach (PpeType::cases() as $ppeType) {
                BriefingAttendancePpeItem::create([
                    'attendance_id' => $attendance->id,
                    'ppe_type' => $ppeType->value,
                    'condition' => PpeCondition::Baik->value,
                ]);
            }
        }

        // Checklists (skipped - table not in DB)
        // foreach (['Helm Safety', 'Rompi Reflektif', 'Sepatu Safety', 'Sarung Tangan', 'Pemahaman Muatan', 'Prosedur Darurat', 'Cek Rack & Alat', 'Koordinasi Terminal'] as $item) {
        //     $type = in_array($item, ['Helm Safety', 'Rompi Reflektif', 'Sepatu Safety', 'Sarung Tangan']) ? 'ppe' : 'safety';
        //     BriefingChecklist::create([
        //         'session_id' => $briefing->id,
        //         'item' => $item,
        //         'type' => $type,
        //         'status' => 'ok',
        //     ]);
        // }
        $this->command->info('  ✓ Briefing + Attendance + Cek Kesehatan + Cek APD');

        // ── 2. SHIPMENT (In Progress - at Handover) ──
        $shipment = Shipment::firstOrCreate(
            ['code' => 'JSS-260416-001'],
            [
                'customer_id' => $customer->id,
                'receiver_id' => $customer->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $cityMdo->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Transit->value,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'vehicle_loading' => 'rack',
                'container_size' => '40ft',
                'container_qty' => 1,
                'packages_total' => 8,
                'cbm_total' => 95,
                'weight_total' => 14000,
                'pol_id' => $portTpri->id,
                'pod_id' => $portBtg->id,
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '08129876000',
                'delivery_contact_name' => 'Warehouse Manila',
                'delivery_contact_phone' => '081555789012',
                'eta' => Carbon::parse('2026-04-20'),
                'notes' => 'Pengiriman 8 unit SUV ke Manila via Bitung',
            ]
        );
        $this->command->info('  ✓ Shipment created: '.$shipment->code);

        // ── Tracking - currently at Handover ──
        $tracks = [
            ['status' => TrackStatus::Pickup, 'time' => '2026-04-16 05:30', 'note' => 'Unit dijemput dari pool TAM Cakung'],
            ['status' => TrackStatus::Handover, 'time' => '2026-04-16 06:30', 'note' => 'Unit diterima di Depo Tanjung Priok oleh FC Arnold - dimulai proses loading'],
        ];

        foreach ($tracks as $t) {
            ShipmentTrack::create([
                'shipment_id' => $shipment->id,
                'status' => $t['status']->value,
                'status_normalized' => true,
                'tracked_at' => Carbon::parse($t['time']),
                'note' => $t['note'],
                'created_by' => $fc->id,
            ]);
        }
        $this->command->info('  ✓ Tracking: Pickup → Handover (in progress)');

        // ── 3. LOADING SESSION (In Progress - at APD Check stage) ──
        $loading = LoadingSession::firstOrCreate(
            ['code' => 'LD-2026-0002'],
            [
                'shipment_id' => $shipment->id,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'branch_id' => $branch->id,
                'briefing_session_id' => $briefing->id,
                'operation_type' => LoadingOperationType::Loading->value,
                'status' => LoadingStatus::ApdCheck->value,
                'mp_required' => 8,
                'mp_present' => 9,
                'mp_absent' => 1,
                'mp_sick' => 1,
                'mp_sufficient' => true,
                'mp_fit_count' => 8,
                'mp_unfit_count' => 1,
                'apd_complete' => true,
                'apd_clean' => true,
                'mp_attendance_completed' => true,
                'health_check_completed' => true,
                'apd_check_completed' => false,
                'equipment_check_completed' => false,
                'rack_container_check_completed' => false,
                'unit_check_completed' => false,
                'stock_apd_check_completed' => false,
                'manpower_availability_completed' => false,
                'final_decision_completed' => false,
                'started_at' => $briefingDate->copy()->setTime(8, 0),
                'general_notes' => 'Loading in progress - menunggu pengecekan APD dan selanjutnya',
            ]
        );
        $this->command->info('  ✓ Loading Session: IN PROGRESS (at APD Check step)');
    }

    private function createUnloadingFlow(array $data): void
    {
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════════════');
        $this->command->info('  FLOW 3: At Unloading - Jakarta → Surabaya');
        $this->command->info('  Status: Kapal Tiba → Pembongkaran (Unloading)');
        $this->command->info('════════════════════════════════════════════════════════════');

        $fc = $data['fcArnold'];
        $depot = $data['depotJkt'];
        $branch = $data['jkt'];
        $customerIND = $data['customerIND'];
        $cityJkt = $data['cityJkt'];
        $citySby = $data['citySby'];
        $portTpri = $data['portTpri'];
        $portSby = $data['portSby'];
        $mpList = $data['manpowerList'];

        $briefingDate = Carbon::parse('2026-04-15');

        // ── Briefing ──
        $briefing = BriefingSession::firstOrCreate(
            ['date' => $briefingDate->format('Y-m-d'), 'depot_id' => $depot->id],
            [
                'coordinator_user_id' => $fc->id,
                'notes' => 'Briefing Harian - Selasa, 15 Apr 2026. Topik: Pembongkaran container dari Surabaya, proses unloading & dooring.',
                'summary_headcount' => 10,
                'summary_sufficient' => true,
                'mp_check_status' => MPCheckStatus::Approved->value,
                'approved_at' => $briefingDate->copy()->setTime(7, 15),
                'approved_by' => $fc->id,
            ]
        );

        foreach ($mpList as $mp) {
            // Check if attendance already exists
            $existingAttendance = BriefingAttendance::where('session_id', $briefing->id)
                ->where('manpower_id', $mp->id)
                ->first();

            if ($existingAttendance) {
                continue;
            }

            $temp = round(36.0 + (rand(0, 12) / 10), 1);
            $bpSys = rand(110, 128);
            $bpDia = rand(70, 84);

            $attendance = BriefingAttendance::create([
                'session_id' => $briefing->id,
                'manpower_id' => $mp->id,
                'attendance_status' => AttendanceStatus::Present->value,
                'temperature' => $temp,
                'bp_systolic' => $bpSys,
                'bp_diastolic' => $bpDia,
                'remark' => null,
                'has_ppe' => true,
            ]);

            foreach (PpeType::cases() as $ppeType) {
                BriefingAttendancePpeItem::create([
                    'attendance_id' => $attendance->id,
                    'ppe_type' => $ppeType->value,
                    'condition' => PpeCondition::Baik->value,
                ]);
            }
        }

        // Checklists (skipped - table not in DB)
        // foreach (['Helm Safety', 'Rompi Reflektif', 'Sepatu Safety', 'Sarung Tangan', 'Pemahaman Muatan', 'Prosedur Unloading', 'Cek Alat Unloading', 'Koordinasi dengan Terminal'] as $item) {
        //     $type = in_array($item, ['Helm Safety', 'Rompi Reflektif', 'Sepatu Safety', 'Sarung Tangan']) ? 'ppe' : 'safety';
        //     BriefingChecklist::create([
        //         'session_id' => $briefing->id,
        //         'item' => $item,
        //         'type' => $type,
        //         'status' => 'ok',
        //     ]);
        // }
        $this->command->info('  ✓ Briefing + Attendance + Cek Kesehatan + Cek APD');

        // ── Shipment (at Unloading) ──
        $shipment = Shipment::firstOrCreate(
            ['code' => 'JSS-260415-002'],
            [
                'customer_id' => $customerIND->id,
                'receiver_id' => $customerIND->id,
                'origin_city_id' => $cityJkt->id,
                'destination_city_id' => $citySby->id,
                'branch_id' => $branch->id,
                'assigned_depot_id' => $depot->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Transit->value,
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'container_size' => '20ft',
                'container_qty' => 1,
                'packages_total' => 4,
                'cbm_total' => 45,
                'weight_total' => 8000,
                'pol_id' => $portTpri->id,
                'pod_id' => $portSby->id,
                'pic_name' => 'Hendra Wijaya',
                'pic_phone' => '08131234000',
                'delivery_contact_name' => 'Gudang Indocement Surabaya',
                'delivery_contact_phone' => '081666789012',
                'eta' => Carbon::parse('2026-04-17'),
                'notes' => 'Pengiriman semen Indocement ke Surabaya - Unloading & Dooring',
            ]
        );
        $this->command->info('  ✓ Shipment created: '.$shipment->code);

        // Tracking - at VesselArrival (kapal tiba, siap unloading)
        $tracks = [
            ['status' => TrackStatus::Pickup, 'time' => '2026-04-13 06:00', 'note' => 'Container dijemput dari gudang Indocement'],
            ['status' => TrackStatus::Handover, 'time' => '2026-04-13 07:30', 'note' => 'Container diterima di Depo Tanjung Priok'],
            ['status' => TrackStatus::Stuffing, 'time' => '2026-04-13 09:00', 'note' => 'Stuffing & segel container selesai'],
            ['status' => TrackStatus::DeliveryToPort, 'time' => '2026-04-13 12:00', 'note' => 'Container diantar ke Terminal Tanjung Priok'],
            ['status' => TrackStatus::Stacking, 'time' => '2026-04-13 14:00', 'note' => 'Stacking di yard terminal'],
            ['status' => TrackStatus::UnitLoading, 'time' => '2026-04-13 18:00', 'note' => 'Loading ke kapal Meratus Jayabaya'],
            ['status' => TrackStatus::OnShip, 'time' => '2026-04-13 20:00', 'note' => 'Container on board kapal'],
            ['status' => TrackStatus::VesselDepart, 'time' => '2026-04-14 06:00', 'note' => 'Kapal berangkat dari Tanjung Priok'],
            ['status' => TrackStatus::VesselArrival, 'time' => '2026-04-16 08:00', 'note' => 'Kapal tiba di Tanjung Perak Surabaya - siap pembongkaran'],
        ];

        foreach ($tracks as $t) {
            ShipmentTrack::create([
                'shipment_id' => $shipment->id,
                'status' => $t['status']->value,
                'status_normalized' => true,
                'tracked_at' => Carbon::parse($t['time']),
                'note' => $t['note'],
                'created_by' => $fc->id,
            ]);
        }
        $this->command->info('  ✓ Tracking: Pickup → ... → VesselArrival (menunggu Unloading)');

        // ── LOADING SESSION (Unloading - In Progress at Equipment Check) ──
        $loading = LoadingSession::firstOrCreate(
            ['code' => 'LD-2026-0003'],
            [
                'shipment_id' => $shipment->id,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'branch_id' => $branch->id,
                'briefing_session_id' => $briefing->id,
                'operation_type' => LoadingOperationType::Unloading->value,
                'status' => LoadingStatus::EquipmentCheck->value,
                'mp_required' => 6,
                'mp_present' => 10,
                'mp_absent' => 0,
                'mp_sick' => 0,
                'mp_sufficient' => true,
                'mp_fit_count' => 10,
                'mp_unfit_count' => 0,
                'apd_complete' => true,
                'apd_clean' => true,
                'mp_attendance_completed' => true,
                'health_check_completed' => true,
                'apd_check_completed' => true,
                'equipment_check_completed' => false,
                'rack_container_check_completed' => false,
                'unit_check_completed' => false,
                'stock_apd_check_completed' => true,
                'manpower_availability_completed' => false,
                'final_decision_completed' => false,
                'started_at' => $briefingDate->copy()->setTime(9, 0),
                'general_notes' => 'Unloading in progress - semua MP sehat, APD lengkap. Menunggu cek alat.',
            ]
        );
        $this->command->info('  ✓ Loading Session (Unloading): IN PROGRESS at Equipment Check');

        // Equipment & Rack checks for unloading (partial - in progress)
        EquipmentCheck::create([
            'loading_session_id' => $loading->id,
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
            'checked_at' => $briefingDate->copy()->setTime(9, 30),
        ]);
        $this->command->info('  ✓ Equipment Check recorded (OK)');
    }
}

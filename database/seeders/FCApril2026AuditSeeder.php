<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\ContainerStructureStatus;
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
use App\Models\Branch;
use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\BriefingChecklist;
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
use App\Models\UnitCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FCApril2026AuditSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('FC APRIL 2026 - AUDIT DATA SEEDER');
        $this->command->info('Periode: 1 April - 16 April 2026');
        $this->command->info('========================================');

        $this->createRoles();
        $data = $this->createMasterData();
        $this->generateAprilData($data);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('AUDIT DATA APRIL 2026 - COMPLETED!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Login: fc-jkt@jss.co.id / password123');
        $this->command->info('FC Panel: http://103.55.37.130/fc');
    }

    private function createRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'office_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'field_coordinator', 'guard_name' => 'web']);
    }

    private function createMasterData(): array
    {
        $this->command->info('Creating master data...');

        $admin = User::firstOrCreate(
            ['email' => 'admin@jss.co.id'],
            ['name' => 'Admin JSS', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
        );
        $admin->assignRole('super_admin');

        $fcJkt = User::firstOrCreate(
            ['email' => 'fc-jkt@jss.co.id'],
            ['name' => 'Andi Wijaya (FC Jakarta)', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
        );
        $fcJkt->assignRole('field_coordinator');

        $fcBtg = User::firstOrCreate(
            ['email' => 'fc-btg@jss.co.id'],
            ['name' => 'Budi Santoso (FC Bontang)', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
        );
        $fcBtg->assignRole('field_coordinator');

        $branchJkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta', 'address' => 'Jl. Tanjung Priok No. 1']);
        $branchBtg = Branch::firstOrCreate(['code' => 'BTG'], ['name' => 'Bontang', 'address' => 'Jl. Poros Bontang']);

        $cityJkt = City::firstOrCreate(['name' => 'Jakarta'], ['province' => 'DKI Jakarta', 'country' => 'Indonesia']);
        $cityBtg = City::firstOrCreate(['name' => 'Bontang'], ['province' => 'Kalimantan Timur', 'country' => 'Indonesia']);
        $cityMnd = City::firstOrCreate(['name' => 'Manado'], ['province' => 'Sulawesi Utara', 'country' => 'Indonesia']);

        $portTpri = Port::firstOrCreate(['code' => 'TPRI'], ['name' => 'Tanjung Priok', 'city' => 'Jakarta']);
        $portBtg = Port::firstOrCreate(['code' => 'BTG'], ['name' => 'Bontang Port', 'city' => 'Bontang']);
        $portMnd = Port::firstOrCreate(['code' => 'MND'], ['name' => 'Manado Port', 'city' => 'Manado']);

        $depotTpri = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            ['name' => 'Depo Tanjung Priok', 'mode' => 'sea', 'port_id' => $portTpri->id, 'branch_id' => $branchJkt->id, 'coordinator_user_id' => $fcJkt->id]
        );

        $depotBtg = Depot::firstOrCreate(
            ['code' => 'DEP-BTG-01'],
            ['name' => 'Depo Bontang', 'mode' => 'sea', 'port_id' => $portBtg->id, 'branch_id' => $branchBtg->id, 'coordinator_user_id' => $fcBtg->id]
        );

        $customerTAM = Customer::firstOrCreate(
            ['code' => 'TAM-001'],
            ['name' => 'PT Toyota Astra Motor', 'email' => 'logistik@toyota.astra.co.id', 'phone' => '021-8195001', 'type' => 'company', 'branch_id' => $branchJkt->id]
        );

        $manpowerList = $this->createManpower($branchJkt, $depotTpri);

        return compact(
            'admin', 'fcJkt', 'fcBtg', 'branchJkt', 'branchBtg',
            'cityJkt', 'cityBtg', 'cityMnd',
            'portTpri', 'portBtg', 'portMnd',
            'depotTpri', 'depotBtg',
            'customerTAM', 'manpowerList'
        );
    }

    private function createManpower(Branch $branch, Depot $depot): array
    {
        $mpData = [
            ['name' => 'Kurnia Adi Pratama', 'phone' => '081234567890'],
            ['name' => 'Dedi Setiawan', 'phone' => '081234567891'],
            ['name' => 'Eko Prasetyo', 'phone' => '081234567892'],
            ['name' => 'Fajar Nugroho', 'phone' => '081234567893'],
            ['name' => 'Gunawan Hidayat', 'phone' => '081234567894'],
            ['name' => 'Hadi Wijaya', 'phone' => '081234567895'],
            ['name' => 'Irfan Fachlevi', 'phone' => '081234567896'],
            ['name' => 'Joko Susilo', 'phone' => '081234567897'],
            ['name' => 'Karno Utomo', 'phone' => '081234567898'],
            ['name' => 'Lukman Hakim', 'phone' => '081234567899'],
            ['name' => 'Maman Surahman', 'phone' => '081234567800'],
            ['name' => 'Nana Sumarna', 'phone' => '081234567801'],
        ];

        $manpowerList = [];
        foreach ($mpData as $mp) {
            $manpowerList[] = Manpower::firstOrCreate(
                ['name' => $mp['name']],
                [
                    'domain' => 'internal',
                    'skills' => ['loading', 'unloading', 'forklift'],
                    'certs' => ['SIO Forklift', 'K3'],
                    'phone' => $mp['phone'],
                    'branch_id' => $branch->id,
                    'depot_id' => $depot->id,
                    'active' => true,
                ]
            );
        }

        return $manpowerList;
    }

    private function generateAprilData(array $data): void
    {
        $fcJkt = $data['fcJkt'];
        $depotTpri = $data['depotTpri'];
        $branchJkt = $data['branchJkt'];
        $cityJkt = $data['cityJkt'];
        $cityBtg = $data['cityBtg'];
        $cityMnd = $data['cityMnd'];
        $portTpri = $data['portTpri'];
        $portBtg = $data['portBtg'];
        $portMnd = $data['portMnd'];
        $customerTAM = $data['customerTAM'];
        $manpowerList = $data['manpowerList'];

        $this->command->info('');
        $this->command->info('Generating April 2026 data...');

        $dates = [
            '2026-04-01', '2026-04-02', '2026-04-03',  // Week 1
            '2026-04-07', '2026-04-08', '2026-04-09', '2026-04-10',
            '2026-04-13', '2026-04-14', '2026-04-15', '2026-04-16',
        ];

        $shipmentNum = 1;
        $loadingNum = 1;

        foreach ($dates as $index => $dateStr) {
            $date = Carbon::parse($dateStr);
            $dayName = $date->format('D');

            $this->command->info("Processing: {$dateStr} ({$dayName})");

            // Create daily briefing
            $briefing = $this->createDailyBriefing($date, $fcJkt, $depotTpri, $manpowerList);

            // Create 2-3 loading sessions per day
            $sessionsPerDay = rand(2, 3);
            for ($s = 0; $s < $sessionsPerDay; $s++) {
                $destCity = [$cityBtg, $cityMnd][array_rand([0, 1])];
                $destPort = $destCity->name === 'Bontang' ? $portBtg : $portMnd;

                $shipment = $this->createShipment(
                    $shipmentNum++, $date, $customerTAM, $branchJkt,
                    $cityJkt, $destCity, $portTpri, $destPort, $depotTpri
                );

                $statusOptions = ['completed', 'completed', 'completed', 'in_progress', 'draft'];
                $status = $statusOptions[array_rand($statusOptions)];

                $loadingSession = $this->createLoadingSession(
                    $loadingNum++, $date, $shipment, $depotTpri,
                    $fcJkt, $branchJkt, $briefing, $manpowerList, $status
                );

                if ($status === 'completed') {
                    $this->createChecks($loadingSession, $fcJkt);
                }
            }

            $this->command->info("  ✓ Created briefing + {$sessionsPerDay} loading sessions");
        }

        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- Briefing Sessions: '.count($dates));
        $this->command->info('- Loading Sessions: '.($loadingNum - 1));
        $this->command->info('- Shipments: '.($shipmentNum - 1));
    }

    private function createDailyBriefing(Carbon $date, User $fc, Depot $depot, array $manpowerList): BriefingSession
    {
        $presentCount = rand(9, 11);
        $presentMP = collect($manpowerList)->random($presentCount);
        $headcount = 12;

        $briefing = BriefingSession::create([
            'date' => $date->format('Y-m-d'),
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'notes' => 'Briefing harian APILL '.$date->format('d M Y'),
            'summary_headcount' => $headcount,
            'summary_sufficient' => $presentCount >= $headcount,
            'mp_check_status' => MPCheckStatus::Approved,
            'approved_at' => $date->copy()->setTime(rand(7, 8), rand(0, 59)),
            'approved_by' => $fc->id,
        ]);

        // Create attendance records
        foreach ($presentMP as $mp) {
            $temp = rand(355, 372) / 10; // 35.5 - 37.2 Celsius
            $bpSys = rand(110, 135);
            $bpDia = rand(70, 90);
            $hasComplaint = rand(1, 10) > 8;

            $attendance = BriefingAttendance::create([
                'session_id' => $briefing->id,
                'manpower_id' => $mp->id,
                'attendance_status' => AttendanceStatus::Present,
                'temperature' => $temp,
                'bp_systolic' => $bpSys,
                'bp_diastolic' => $bpDia,
                'health_complaint' => $hasComplaint ? 'Sakit kepala ringan' : null,
                'has_ppe' => true,
                'recheck_result' => null,
            ]);

            // Create PPE inspection for this attendance
            $this->createPpeInspection($attendance, $mp);
        }

        // Create absent/sick MPs
        $absentMP = collect($manpowerList)->filter(fn ($mp) => ! $presentMP->contains($mp));
        foreach ($absentMP->take(rand(1, 3)) as $mp) {
            BriefingAttendance::create([
                'session_id' => $briefing->id,
                'manpower_id' => $mp->id,
                'attendance_status' => rand(1, 10) > 7 ? AttendanceStatus::Sick : AttendanceStatus::Absent,
                'health_complaint' => rand(1, 10) > 5 ? 'Demam' : null,
            ]);
        }

        // Create briefing checklists
        $checklists = [
            ['item' => 'Helm Safety', 'type' => 'ppe', 'status' => 'done'],
            ['item' => 'Rompi Reflektif', 'type' => 'ppe', 'status' => 'done'],
            ['item' => 'Sepatu Safety', 'type' => 'ppe', 'status' => 'done'],
            ['item' => 'Sarung Tangan', 'type' => 'ppe', 'status' => 'done'],
            ['item' => 'Pemahaman Muatan', 'type' => 'safety', 'status' => 'done'],
            ['item' => 'Prosedur Darurat', 'type' => 'safety', 'status' => 'done'],
            ['item' => 'Cek Rack & Alat', 'type' => 'equipment', 'status' => 'done'],
        ];

        foreach ($checklists as $cl) {
            BriefingChecklist::create([
                'session_id' => $briefing->id,
                'item' => $cl['item'],
                'type' => $cl['type'],
                'status' => $cl['status'],
            ]);
        }

        return $briefing;
    }

    private function createPpeInspection(BriefingAttendance $attendance, Manpower $mp): void
    {
        $ppeTypes = [
            ['type' => PpeType::Helm, 'condition' => PpeCondition::Baik],
            ['type' => PpeType::Rompi, 'condition' => PpeCondition::Baik],
            ['type' => PpeType::Sepatu, 'condition' => rand(1, 10) > 2 ? PpeCondition::Baik : PpeCondition::KurangBaik],
            ['type' => PpeType::SarungTangan, 'condition' => PpeCondition::Baik],
        ];

        foreach ($ppeTypes as $ppe) {
            BriefingAttendancePpeItem::create([
                'attendance_id' => $attendance->id,
                'manpower_id' => $mp->id,
                'ppe_type' => $ppe['type']->value,
                'condition' => $ppe['condition']->value,
                'remark' => null,
            ]);
        }
    }

    private function createShipment(
        int $num, Carbon $date, Customer $customer, Branch $branch,
        City $origin, City $dest, Port $pol, Port $pod, Depot $depot
    ): Shipment {
        $containerSize = ['20ft', '40ft'][rand(0, 1)];

        return Shipment::create([
            'code' => 'JSS-'.date('ymd').'-'.str_pad($num, 3, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $origin->id,
            'destination_city_id' => $dest->id,
            'branch_id' => $branch->id,
            'assigned_depot_id' => $depot->id,
            'mode' => ShipmentMode::Sea->value,
            'status' => ShipmentStatus::Pending,
            'service_type' => 'sea_freight',
            'service_option' => 'fcl',
            'cargo_type' => 'vehicle',
            'container_size' => $containerSize,
            'container_qty' => rand(1, 3),
            'packages_total' => rand(5, 20),
            'cbm_total' => rand(50, 200),
            'weight_total' => rand(10000, 30000),
            'pol_id' => $pol->id,
            'pod_id' => $pod->id,
            'pic_name' => 'PIC '.$customer->name,
            'pic_phone' => '081234567890',
            'delivery_contact_name' => 'Warehouse '.$dest->name,
            'delivery_contact_phone' => '081234567891',
            'eta' => $date->copy()->addDays(3),
            'notes' => 'Audit April 2026 - '.$customer->name,
        ]);
    }

    private function createLoadingSession(
        int $num, Carbon $date, Shipment $shipment, Depot $depot,
        User $fc, Branch $branch, BriefingSession $briefing, array $manpowerList, string $status
    ): LoadingSession {
        $mpRequired = rand(6, 10);
        $mpPresent = $status === 'draft' ? 0 : rand($mpRequired, $mpRequired + 2);
        $mpFit = $status === 'draft' ? 0 : rand($mpPresent - 1, $mpPresent);

        $loadingStatuses = [
            'draft' => LoadingStatus::Draft,
            'in_progress' => LoadingStatus::MpAttendanceCheck,
            'completed' => LoadingStatus::Completed,
        ];

        return LoadingSession::create([
            'code' => 'LD-'.date('ymd').'-'.str_pad($num, 3, '0', STR_PAD_LEFT),
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'branch_id' => $branch->id,
            'briefing_session_id' => $briefing->id,
            'operation_type' => LoadingOperationType::Loading,
            'status' => $loadingStatuses[$status],
            'mp_required' => $mpRequired,
            'mp_present' => $mpPresent,
            'mp_absent' => max(0, $mpRequired - $mpPresent),
            'mp_sick' => rand(0, 2),
            'mp_sufficient' => $mpPresent >= $mpRequired,
            'mp_fit_count' => $mpFit,
            'mp_unfit_count' => $mpPresent - $mpFit,
            'apd_complete' => $status === 'completed',
            'apd_clean' => $status === 'completed',
            'equipment_safe' => $status === 'completed',
            'rack_container_safe' => $status === 'completed',
            'unit_measurements_ok' => $status === 'completed',
            'stock_apd_sufficient' => true,
            'mp_attendance_completed' => $status !== 'draft',
            'health_check_completed' => $status === 'completed' || ($status === 'in_progress' && rand(0, 1)),
            'apd_check_completed' => $status === 'completed',
            'equipment_check_completed' => $status === 'completed',
            'rack_container_check_completed' => $status === 'completed',
            'unit_check_completed' => $status === 'completed',
            'stock_apd_check_completed' => $status === 'completed',
            'final_decision_completed' => $status === 'completed',
            'final_decision_status' => $status === 'completed' ? FinalDecisionStatus::Go : null,
            'started_at' => $status !== 'draft' ? $date->copy()->setTime(rand(8, 10), rand(0, 59)) : null,
            'completed_at' => $status === 'completed' ? $date->copy()->setTime(rand(14, 17), rand(0, 59)) : null,
            'general_notes' => 'Loading session April 2026',
        ]);
    }

    private function createChecks(LoadingSession $session, User $fc): void
    {
        // Rack Container Check
        RackContainerCheck::create([
            'loading_session_id' => $session->id,
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
            'checked_at' => now(),
        ]);

        // Equipment Check
        EquipmentCheck::create([
            'loading_session_id' => $session->id,
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
            'checked_at' => now(),
        ]);

        // Unit Check
        UnitCheck::create([
            'loading_session_id' => $session->id,
            'unit_plate_number' => 'B '.rand(1000, 9999).' JKT',
            'distance_front_rh' => rand(110, 130),
            'distance_rear_rh' => rand(110, 130),
            'distance_back_door' => rand(170, 190),
            'distance_rear_lh' => rand(110, 130),
            'distance_front_lh' => rand(110, 130),
            'drop_floor_front_height' => rand(100, 120),
            'drop_floor_rear_height' => rand(100, 120),
            'container_roof_distance' => rand(270, 290),
            'measurements_valid' => true,
            'unit_safe_for_loading' => true,
            'checked_by' => $fc->id,
            'checked_at' => now(),
        ]);
    }
}

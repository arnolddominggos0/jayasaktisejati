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

class FCApril2026Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('FC APRIL 2026 - DATA AUDIT HARIAN');
        $this->command->info('========================================');

        $this->createRoles();
        $data = $this->createMasterData();
        $this->generateDailyData($data);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('DATA AUDIT HARIAN APRIL 2026 - SELESAI!');
        $this->command->info('========================================');
    }

    private function createRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'field_coordinator', 'guard_name' => 'web']);
    }

    private function createMasterData(): array
    {
        $this->command->info('Membuat data master...');

        $fc = User::firstOrCreate(
            ['email' => 'suryadi@jss.co.id'],
            ['name' => 'Suryadi', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
        );
        $fc->assignRole('field_coordinator');

        $admin = User::firstOrCreate(
            ['email' => 'admin@jss.co.id'],
            ['name' => 'Admin JSS', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
        );
        $admin->assignRole('super_admin');

        $branch = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);

        $cityJkt = City::firstOrCreate(['name' => 'Jakarta'], ['province' => 'DKI Jakarta']);
        $cityBtg = City::firstOrCreate(['name' => 'Bontang'], ['province' => 'Kalimantan Timur']);

        $portTpri = Port::firstOrCreate(['code' => 'TPRI'], ['name' => 'Tanjung Priok']);
        $portBtg = Port::firstOrCreate(['code' => 'BTG'], ['name' => 'Bontang']);

        $depot = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            ['name' => 'Depo Tanjung Priok', 'mode' => 'sea', 'port_id' => $portTpri->id, 'branch_id' => $branch->id, 'coordinator_user_id' => $fc->id]
        );

        $customer = Customer::firstOrCreate(
            ['code' => 'TAM-001'],
            ['name' => 'PT Toyota Astra Motor', 'email' => 'logistik@toyota.astra.co.id', 'phone' => '021-8195001', 'type' => 'company', 'branch_id' => $branch->id]
        );

        // 12 MP dengan data realistis Indonesia
        $mpData = [
            ['name' => 'Udin Kuswanto', 'phone' => '081298765432', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Suryadi', 'phone' => '081212345678', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Markus Edowin', 'phone' => '081312345678', 'skills' => ['forklift'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Edot Prasetyo', 'phone' => '081412345678', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Solehudin', 'phone' => '081512345678', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Trimulya', 'phone' => '081612345678', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift']],
            ['name' => 'Jumadi', 'phone' => '081712345678', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Kasidi', 'phone' => '081812345678', 'skills' => ['unloading'], 'certs' => ['K3']],
            ['name' => 'Rohmat', 'phone' => '081912345678', 'skills' => ['forklift'], 'certs' => ['SIO Forklift', 'K3']],
            ['name' => 'Wartono', 'phone' => '082098765432', 'skills' => ['loading', 'unloading'], 'certs' => ['K3']],
            ['name' => 'Sukarman', 'phone' => '082198765432', 'skills' => ['loading'], 'certs' => ['K3']],
            ['name' => 'Daryanto', 'phone' => '082298765432', 'skills' => ['forklift', 'loading'], 'certs' => ['SIO Forklift', 'K3']],
        ];

        $manpowerList = [];
        foreach ($mpData as $i => $mp) {
            $manpowerList[] = Manpower::firstOrCreate(
                ['name' => $mp['name']],
                [
                    'domain' => 'internal',
                    'skills' => $mp['skills'],
                    'certs' => $mp['certs'],
                    'phone' => $mp['phone'],
                    'branch_id' => $branch->id,
                    'depot_id' => $depot->id,
                    'active' => true,
                ]
            );
        }

        return compact('fc', 'admin', 'branch', 'cityJkt', 'cityBtg', 'portTpri', 'portBtg', 'depot', 'customer', 'manpowerList');
    }

    private function generateDailyData(array $data): void
    {
        $fc = $data['fc'];
        $depot = $data['depot'];
        $customer = $data['customer'];
        $cityJkt = $data['cityJkt'];
        $cityBtg = $data['cityBtg'];
        $portTpri = $data['portTpri'];
        $portBtg = $data['portBtg'];
        $manpowerList = $data['manpowerList'];
        $branch = $data['branch'];

        // Tanggal 1-16 April 2026 (Senin-Jumat only) - fixed pattern
        $dates = [
            '2026-04-01' => 'Rabu',
            '2026-04-02' => 'Kamis',
            '2026-04-03' => 'Jumat',
            '2026-04-07' => 'Selasa',
            '2026-04-08' => 'Rabu',
            '2026-04-09' => 'Kamis',
            '2026-04-10' => 'Jumat',
            '2026-04-13' => 'Senin',
            '2026-04-14' => 'Selasa',
            '2026-04-15' => 'Rabu',
            '2026-04-16' => 'Kamis',
        ];

        // Fixed attendance pattern per person (name => days absent/sick)
        // Consistent pattern makes data look realistic
        $absencePattern = [
            'Udin Kuswanto' => ['sick' => ['03']],  // Sick on 03
            'Suryadi' => ['absent' => ['09']],       // Absent on 09
            'Markus Edowin' => ['absent' => ['03', '15']],  // Absent on 03, 15
            'Edot Prasetyo' => ['sick' => ['09', '10']],    // Sick on 09, 10
            'Solehudin' => ['absent' => ['01']],      // Absent on 01
            'Trimulya' => [],                          // Always present
            'Jumadi' => ['absent' => ['07']],        // Absent on 07
            'Kasidi' => ['sick' => ['14']],          // Sick on 14
            'Rohmat' => [],                           // Always present
            'Wartono' => ['absent' => ['16']],       // Absent on 16
            'Sukarman' => ['sick' => ['02']],        // Sick on 02
            'Daryanto' => [],                         // Always present
        ];

        $shipmentNum = 1;
        $loadingNum = 1;

        foreach ($dates as $dateStr => $dayName) {
            $date = Carbon::parse($dateStr);
            $day = substr($dateStr, 8, 2);

            $this->command->info("Tanggal {$dateStr} ({$dayName})");

            // Create briefing
            $briefing = BriefingSession::create([
                'date' => $dateStr,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'notes' => "Briefing harian - {$dayName}, {$date->format('d M Y')}",
                'summary_headcount' => 12,
                'summary_sufficient' => true,
                'mp_check_status' => MPCheckStatus::Approved,
                'approved_at' => $date->copy()->setTime(7, rand(15, 45)),
                'approved_by' => $fc->id,
            ]);

            // Process each MP with consistent pattern
            foreach ($manpowerList as $mp) {
                $mpName = $mp->name;
                $isSick = isset($absencePattern[$mpName]['sick']) && in_array($day, $absencePattern[$mpName]['sick']);
                $isAbsent = isset($absencePattern[$mpName]['absent']) && in_array($day, $absencePattern[$mpName]['absent']);

                if ($isSick) {
                    BriefingAttendance::create([
                        'session_id' => $briefing->id,
                        'manpower_id' => $mp->id,
                        'attendance_status' => AttendanceStatus::Sick,
                        'temperature' => 37.8 + (rand(0, 5) / 10),
                        'bp_systolic' => rand(130, 145),
                        'bp_diastolic' => rand(85, 95),
                        'health_complaint' => 'Demam & flu',
                    ]);
                } elseif ($isAbsent) {
                    BriefingAttendance::create([
                        'session_id' => $briefing->id,
                        'manpower_id' => $mp->id,
                        'attendance_status' => AttendanceStatus::Absent,
                    ]);
                } else {
                    // Present - create with health data
                    $temp = 36.0 + (rand(0, 15) / 10); // 36.0 - 37.5
                    $bpSys = rand(110, 130);
                    $bpDia = rand(70, 85);

                    $attendance = BriefingAttendance::create([
                        'session_id' => $briefing->id,
                        'manpower_id' => $mp->id,
                        'attendance_status' => AttendanceStatus::Present,
                        'temperature' => round($temp, 1),
                        'bp_systolic' => $bpSys,
                        'bp_diastolic' => $bpDia,
                        'has_ppe' => true,
                    ]);

                    // APD Items
                    BriefingAttendancePpeItem::create([
                        'attendance_id' => $attendance->id,
                        'manpower_id' => $mp->id,
                        'ppe_type' => 'helm',
                        'condition' => 'baik',
                    ]);
                    BriefingAttendancePpeItem::create([
                        'attendance_id' => $attendance->id,
                        'manpower_id' => $mp->id,
                        'ppe_type' => 'rompi',
                        'condition' => 'baik',
                    ]);
                    BriefingAttendancePpeItem::create([
                        'attendance_id' => $attendance->id,
                        'manpower_id' => $mp->id,
                        'ppe_type' => 'sepatu',
                        'condition' => rand(1, 10) > 9 ? 'kurang_baik' : 'baik',
                    ]);
                    BriefingAttendancePpeItem::create([
                        'attendance_id' => $attendance->id,
                        'manpower_id' => $mp->id,
                        'ppe_type' => 'sarung_tangan',
                        'condition' => 'baik',
                    ]);
                }
            }

            // Briefing Checklists
            $checklists = [
                ['item' => 'Helm Safety', 'type' => 'ppe'],
                ['item' => 'Rompi Reflektif', 'type' => 'ppe'],
                ['item' => 'Sepatu Safety', 'type' => 'ppe'],
                ['item' => 'Sarung Tangan', 'type' => 'ppe'],
                ['item' => 'Pemahaman Muatan', 'type' => 'safety'],
                ['item' => 'Prosedur Darurat', 'type' => 'safety'],
            ];

            foreach ($checklists as $cl) {
                BriefingChecklist::create([
                    'session_id' => $briefing->id,
                    'item' => $cl['item'],
                    'type' => $cl['type'],
                    'status' => 'done',
                ]);
            }

            // Loading Sessions (2-3 per hari)
            $sessionsPerDay = rand(2, 3);
            for ($s = 0; $s < $sessionsPerDay; $s++) {
                $mpRequired = rand(6, 8);
                $mpPresent = min($mpRequired + rand(0, 2), 10);

                $shipment = Shipment::create([
                    'code' => 'JSS-'.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).'-'.str_pad($shipmentNum, 3, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'receiver_id' => $customer->id,
                    'origin_city_id' => $cityJkt->id,
                    'destination_city_id' => $cityBtg->id,
                    'branch_id' => $branch->id,
                    'assigned_depot_id' => $depot->id,
                    'mode' => ShipmentMode::Sea->value,
                    'status' => ShipmentStatus::Pending,
                    'service_type' => 'sea_freight',
                    'cargo_type' => 'vehicle',
                    'container_size' => '40ft',
                    'container_qty' => rand(1, 2),
                    'packages_total' => rand(8, 15),
                    'cbm_total' => rand(60, 140),
                    'weight_total' => rand(15000, 25000),
                    'pol_id' => $portTpri->id,
                    'pod_id' => $portBtg->id,
                    'pic_name' => 'PIC Toyota',
                    'pic_phone' => '081298765432',
                    'delivery_contact_name' => 'Warehouse Bontang',
                    'delivery_contact_phone' => '081212345678',
                    'notes' => "Shipment Toyota - {$date->format('d M Y')}",
                ]);

                // 80% completed, 20% in progress
                $isCompleted = rand(1, 10) <= 8;

                $loading = LoadingSession::create([
                    'code' => 'LD-'.substr($dateStr, 2, 2).substr($dateStr, 5, 2).substr($dateStr, 8, 2).'-'.str_pad($loadingNum, 3, '0', STR_PAD_LEFT),
                    'shipment_id' => $shipment->id,
                    'depot_id' => $depot->id,
                    'coordinator_user_id' => $fc->id,
                    'branch_id' => $branch->id,
                    'briefing_session_id' => $briefing->id,
                    'operation_type' => LoadingOperationType::Loading,
                    'status' => $isCompleted ? LoadingStatus::Completed : LoadingStatus::MpAttendanceCheck,
                    'mp_required' => $mpRequired,
                    'mp_present' => $mpPresent,
                    'mp_absent' => max(0, $mpRequired - $mpPresent),
                    'mp_sufficient' => $mpPresent >= $mpRequired,
                    'mp_fit_count' => $mpPresent - rand(0, 1),
                    'apd_complete' => $isCompleted,
                    'apd_clean' => $isCompleted,
                    'equipment_safe' => $isCompleted,
                    'rack_container_safe' => $isCompleted,
                    'unit_measurements_ok' => $isCompleted,
                    'stock_apd_sufficient' => true,
                    'mp_attendance_completed' => true,
                    'health_check_completed' => $isCompleted,
                    'apd_check_completed' => $isCompleted,
                    'equipment_check_completed' => $isCompleted,
                    'rack_container_check_completed' => $isCompleted,
                    'unit_check_completed' => $isCompleted,
                    'final_decision_completed' => $isCompleted,
                    'final_decision_status' => $isCompleted ? FinalDecisionStatus::Go : null,
                    'started_at' => $date->copy()->setTime(8, rand(0, 30)),
                    'completed_at' => $isCompleted ? $date->copy()->setTime(15, rand(30, 59)) : null,
                ]);

                if ($isCompleted) {
                    // Rack Container Check
                    RackContainerCheck::create([
                        'loading_session_id' => $loading->id,
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
                    ]);

                    // Equipment Check
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
                        'overall_safe' => true,
                        'checked_by' => $fc->id,
                    ]);

                    // Unit Check
                    UnitCheck::create([
                        'loading_session_id' => $loading->id,
                        'unit_plate_number' => 'B '.rand(1000, 9999).' JKT',
                        'distance_front_rh' => rand(115, 125),
                        'distance_rear_rh' => rand(115, 125),
                        'distance_back_door' => rand(175, 185),
                        'distance_rear_lh' => rand(115, 125),
                        'distance_front_lh' => rand(115, 125),
                        'drop_floor_front_height' => rand(105, 115),
                        'drop_floor_rear_height' => rand(105, 115),
                        'container_roof_distance' => rand(275, 285),
                        'measurements_valid' => true,
                        'unit_safe_for_loading' => true,
                        'checked_by' => $fc->id,
                    ]);
                }

                $shipmentNum++;
                $loadingNum++;
            }
        }

        $this->command->info('');
        $this->command->info('=== RINGKASAN DATA ===');
        $this->command->info('Briefing Sessions: '.count($dates));
        $this->command->info('Shipments: '.($shipmentNum - 1));
        $this->command->info('Loading Sessions: '.($loadingNum - 1));
        $this->command->info('Manpower: '.count($manpowerList));
    }
}

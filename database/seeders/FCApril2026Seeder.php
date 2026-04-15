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

class FCApril2026Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('FC APRIL 2026 - AUDIT DATA SEEDER');
        $this->command->info('========================================');

        $this->createRoles();
        $data = $this->createMasterData();
        $this->generateAprilData($data);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('AUDIT DATA APRIL 2026 - COMPLETED!');
        $this->command->info('========================================');
    }

    private function createRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'field_coordinator', 'guard_name' => 'web']);
    }

    private function createMasterData(): array
    {
        $this->command->info('Creating master data...');

        $fc = User::firstOrCreate(
            ['email' => 'fc-jkt@jss.co.id'],
            ['name' => 'Andi Wijaya (FC Jakarta)', 'password' => Hash::make('password123'), 'email_verified_at' => now()]
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

        // Create 12 MP
        $mpNames = [
            'Kurnia Adi Pratama', 'Dedi Setiawan', 'Eko Prasetyo', 'Fajar Nugroho',
            'Gunawan Hidayat', 'Hadi Wijaya', 'Irfan Fachlevi', 'Joko Susilo',
            'Karno Utomo', 'Lukman Hakim', 'Maman Surahman', 'Nana Sumarna',
        ];

        $manpowerList = [];
        foreach ($mpNames as $i => $name) {
            $manpowerList[] = Manpower::firstOrCreate(
                ['name' => $name],
                [
                    'domain' => 'internal',
                    'skills' => ['loading', 'forklift'],
                    'certs' => ['SIO Forklift'],
                    'phone' => '08123'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'branch_id' => $branch->id,
                    'depot_id' => $depot->id,
                    'active' => true,
                ]
            );
        }

        return compact('fc', 'branch', 'cityJkt', 'cityBtg', 'portTpri', 'portBtg', 'depot', 'customer', 'manpowerList');
    }

    private function generateAprilData(array $data): void
    {
        $fc = $data['fc'];
        $depot = $data['depot'];
        $customer = $data['customer'];
        $cityJkt = $data['cityJkt'];
        $cityBtg = $data['cityBtg'];
        $portTpri = $data['portTpri'];
        $portBtg = $data['portBtg'];
        $manpowerList = $data['manpowerList'];

        $this->command->info('Generating April 2026 data...');

        $dates = [
            '2026-04-01', '2026-04-02', '2026-04-03',
            '2026-04-07', '2026-04-08', '2026-04-09', '2026-04-10',
            '2026-04-13', '2026-04-14', '2026-04-15', '2026-04-16',
        ];

        $shipmentNum = 1;
        $loadingNum = 1;

        foreach ($dates as $dateStr) {
            $date = Carbon::parse($dateStr);
            $dayName = $date->format('D');

            $this->command->info("Processing: {$dateStr} ({$dayName})");

            // Create briefing session
            $presentCount = rand(10, 12);
            $presentMP = collect($manpowerList)->random(min($presentCount, count($manpowerList)));

            $briefing = BriefingSession::create([
                'date' => $dateStr,
                'depot_id' => $depot->id,
                'coordinator_user_id' => $fc->id,
                'notes' => 'Briefing harian April 2026',
                'summary_headcount' => 12,
                'summary_sufficient' => true,
                'mp_check_status' => MPCheckStatus::Approved,
                'approved_at' => $date->copy()->setTime(7, 30),
                'approved_by' => $fc->id,
            ]);

            // Create attendance for each present MP
            foreach ($presentMP as $mp) {
                $temp = rand(355, 370) / 10;
                $bpSys = rand(110, 130);
                $bpDia = rand(70, 85);

                $attendance = BriefingAttendance::create([
                    'session_id' => $briefing->id,
                    'manpower_id' => $mp->id,
                    'attendance_status' => AttendanceStatus::Present,
                    'temperature' => $temp,
                    'bp_systolic' => $bpSys,
                    'bp_diastolic' => $bpDia,
                    'has_ppe' => true,
                ]);

                // Create PPE items for attendance
                $ppeItems = [
                    ['type' => PpeType::Helm, 'condition' => PpeCondition::Baik],
                    ['type' => PpeType::Rompi, 'condition' => PpeCondition::Baik],
                    ['type' => PpeType::Sepatu, 'condition' => rand(1, 10) > 2 ? PpeCondition::Baik : PpeCondition::KurangBaik],
                    ['type' => PpeType::SarungTangan, 'condition' => PpeCondition::Baik],
                ];

                foreach ($ppeItems as $ppe) {
                    BriefingAttendancePpeItem::create([
                        'attendance_id' => $attendance->id,
                        'manpower_id' => $mp->id,
                        'ppe_type' => $ppe['type']->value,
                        'condition' => $ppe['condition']->value,
                    ]);
                }
            }

            // Create absent MPs
            $absentMP = collect($manpowerList)->filter(fn ($mp) => ! $presentMP->contains($mp));
            foreach ($absentMP->take(rand(1, 2)) as $mp) {
                BriefingAttendance::create([
                    'session_id' => $briefing->id,
                    'manpower_id' => $mp->id,
                    'attendance_status' => AttendanceStatus::Absent,
                ]);
            }

            // Create checklists
            $checklists = ['Helm Safety', 'Rompi Reflektif', 'Sepatu Safety', 'Sarung Tangan', 'Pemahaman Muatan', 'Prosedur Darurat'];
            foreach ($checklists as $item) {
                BriefingChecklist::create([
                    'session_id' => $briefing->id,
                    'item' => $item,
                    'type' => 'safety',
                    'status' => 'done',
                ]);
            }

            // Create 2-3 loading sessions per day
            $sessionsPerDay = rand(2, 3);
            for ($s = 0; $s < $sessionsPerDay; $s++) {
                $shipment = Shipment::create([
                    'code' => 'JSS-'.str_replace('-', '', $dateStr).'-'.str_pad($shipmentNum, 3, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'receiver_id' => $customer->id,
                    'origin_city_id' => $cityJkt->id,
                    'destination_city_id' => $cityBtg->id,
                    'branch_id' => $data['branch']->id,
                    'assigned_depot_id' => $depot->id,
                    'mode' => ShipmentMode::Sea->value,
                    'status' => ShipmentStatus::Pending,
                    'service_type' => 'sea_freight',
                    'cargo_type' => 'vehicle',
                    'container_size' => '40ft',
                    'container_qty' => rand(1, 2),
                    'packages_total' => rand(5, 15),
                    'cbm_total' => rand(50, 150),
                    'weight_total' => rand(10000, 25000),
                    'pol_id' => $portTpri->id,
                    'pod_id' => $portBtg->id,
                    'pic_name' => 'PIC TAM',
                    'pic_phone' => '081234567890',
                    'notes' => 'Audit April 2026',
                ]);

                $status = rand(1, 10) > 2 ? 'completed' : 'in_progress';

                $loading = LoadingSession::create([
                    'code' => 'LD-'.str_replace('-', '', $dateStr).'-'.str_pad($loadingNum, 3, '0', STR_PAD_LEFT),
                    'shipment_id' => $shipment->id,
                    'depot_id' => $depot->id,
                    'coordinator_user_id' => $fc->id,
                    'branch_id' => $data['branch']->id,
                    'briefing_session_id' => $briefing->id,
                    'operation_type' => LoadingOperationType::Loading,
                    'status' => $status === 'completed' ? LoadingStatus::Completed : LoadingStatus::MpAttendanceCheck,
                    'mp_required' => rand(6, 10),
                    'mp_present' => rand(6, 10),
                    'mp_sufficient' => true,
                    'mp_fit_count' => rand(6, 10),
                    'apd_complete' => $status === 'completed',
                    'apd_clean' => $status === 'completed',
                    'equipment_safe' => $status === 'completed',
                    'rack_container_safe' => $status === 'completed',
                    'unit_measurements_ok' => $status === 'completed',
                    'stock_apd_sufficient' => true,
                    'mp_attendance_completed' => true,
                    'health_check_completed' => $status === 'completed',
                    'apd_check_completed' => $status === 'completed',
                    'equipment_check_completed' => $status === 'completed',
                    'rack_container_check_completed' => $status === 'completed',
                    'unit_check_completed' => $status === 'completed',
                    'final_decision_completed' => $status === 'completed',
                    'final_decision_status' => $status === 'completed' ? FinalDecisionStatus::Go : null,
                    'started_at' => $date->copy()->setTime(8, rand(0, 59)),
                    'completed_at' => $status === 'completed' ? $date->copy()->setTime(15, rand(0, 59)) : null,
                ]);

                if ($status === 'completed') {
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

                    UnitCheck::create([
                        'loading_session_id' => $loading->id,
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
                    ]);
                }

                $shipmentNum++;
                $loadingNum++;
            }

            $this->command->info("  ✓ {$dateStr}: 1 briefing + {$sessionsPerDay} loading sessions");
        }

        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- Briefing Sessions: '.count($dates));
        $this->command->info('- Shipments: '.($shipmentNum - 1));
        $this->command->info('- Loading Sessions: '.($loadingNum - 1));
        $this->command->info('- Manpower: '.count($manpowerList));
    }
}

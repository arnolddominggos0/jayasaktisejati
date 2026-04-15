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
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\BriefingAttendance;
use App\Models\BriefingChecklist;
use App\Models\BriefingSession;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\EquipmentCheck;
use App\Models\LoadingFinalDecision;
use App\Models\LoadingSession;
use App\Models\Manpower;
use App\Models\Port;
use App\Models\RackContainerCheck;
use App\Models\Shipment;
use App\Models\ShippingLine;
use App\Models\UnitCheck;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FCOneMonthSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('FC ONE MONTH DUMMY DATA SEEDER');
        $this->command->info('Simulasi aktivitas FC selama 30 hari');
        $this->command->info('========================================');

        $this->createRoles();
        $masterData = $this->createMasterData();

        $this->command->info('');
        $this->command->info('Generating 30 days of FC activities...');
        $this->command->info('');

        $this->generate30DaysActivities($masterData);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('FC One Month Seeding COMPLETED!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('Admin: admin@jss.co.id / password123');
        $this->command->info('FC Jakarta: fc-jkt@jss.co.id / password123');
        $this->command->info('FC Bontang: fc-btg@jss.co.id / password123');
        $this->command->info('');
        $this->command->info('Akses FC Panel: http://103.55.37.130/fc');
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
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('super_admin');

        $fcJkt = User::firstOrCreate(
            ['email' => 'fc-jkt@jss.co.id'],
            [
                'name' => 'Andi Wijaya (FC Jakarta)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $fcJkt->assignRole('field_coordinator');

        $fcBtg = User::firstOrCreate(
            ['email' => 'fc-btg@jss.co.id'],
            [
                'name' => 'Budi Santoso (FC Bontang)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $fcBtg->assignRole('field_coordinator');

        $branchJkt = Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta', 'address' => 'Jl. Tanjung Priok No. 1', 'phone' => '021-12345678']
        );

        $branchBtg = Branch::firstOrCreate(
            ['code' => 'BTG'],
            ['name' => 'Bontang', 'address' => 'Jl. Poros Bontang', 'phone' => '0541-123456']
        );

        $cityJkt = City::firstOrCreate(['name' => 'Jakarta'], ['province' => 'DKI Jakarta', 'country' => 'Indonesia']);
        $cityBtg = City::firstOrCreate(['name' => 'Bontang'], ['province' => 'Kalimantan Timur', 'country' => 'Indonesia']);
        $cityMnd = City::firstOrCreate(['name' => 'Manado'], ['province' => 'Sulawesi Utara', 'country' => 'Indonesia']);
        $citySmd = City::firstOrCreate(['name' => 'Samarinda'], ['province' => 'Kalimantan Timur', 'country' => 'Indonesia']);

        $portTpri = Port::firstOrCreate(['code' => 'TPRI'], ['name' => 'Tanjung Priok', 'city' => 'Jakarta']);
        $portBtg = Port::firstOrCreate(['code' => 'BTG'], ['name' => 'Bontang Port', 'city' => 'Bontang']);
        $portMnd = Port::firstOrCreate(['code' => 'MND'], ['name' => 'Manado Port', 'city' => 'Manado']);

        $depotTpri = Depot::firstOrCreate(
            ['code' => 'DEP-TPRI-01'],
            [
                'name' => 'Depo Tanjung Priok',
                'mode' => 'sea',
                'port_id' => $portTpri->id,
                'branch_id' => $branchJkt->id,
                'coordinator_user_id' => $fcJkt->id,
            ]
        );

        $depotBtg = Depot::firstOrCreate(
            ['code' => 'DEP-BTG-01'],
            [
                'name' => 'Depo Bontang',
                'mode' => 'sea',
                'port_id' => $portBtg->id,
                'branch_id' => $branchBtg->id,
                'coordinator_user_id' => $fcBtg->id,
            ]
        );

        $customerTAM = Customer::firstOrCreate(
            ['code' => 'TAM-001'],
            [
                'name' => 'PT Toyota Astra Motor',
                'email' => 'logistik@toyota.astra.co.id',
                'phone' => '021-8195001',
                'address' => 'Jl. Yos Sudarso Kav. 8, Jakarta',
                'type' => 'company',
                'branch_id' => $branchJkt->id,
            ]
        );

        $customerIsuzu = Customer::firstOrCreate(
            ['code' => 'ISZ-001'],
            [
                'name' => 'PT Isuzu Astra Motor Indonesia',
                'email' => 'logistik@isuzu.co.id',
                'phone' => '021-89765432',
                'address' => 'Jl. Raya Bekasi Km 23, Jakarta',
                'type' => 'company',
                'branch_id' => $branchJkt->id,
            ]
        );

        $customerDaihatsu = Customer::firstOrCreate(
            ['code' => 'DAI-001'],
            [
                'name' => 'PT Astra Daihatsu Motor',
                'email' => 'logistik@daihatsu.co.id',
                'phone' => '021-89761000',
                'address' => 'Jl. Raya Bekasi Km 23, Jakarta',
                'type' => 'company',
                'branch_id' => $branchJkt->id,
            ]
        );

        $manpowerList = $this->createManpower($branchJkt, $depotTpri);
        $voyages = Voyage::limit(20)->get();

        if ($voyages->isEmpty()) {
            $voyages = $this->createVoyages($portTpri, $portBtg, $portMnd);
        }

        return compact(
            'admin', 'fcJkt', 'fcBtg', 'branchJkt', 'branchBtg',
            'cityJkt', 'cityBtg', 'cityMnd', 'citySmd',
            'portTpri', 'portBtg', 'portMnd',
            'depotTpri', 'depotBtg',
            'customerTAM', 'customerIsuzu', 'customerDaihatsu',
            'manpowerList', 'voyages'
        );
    }

    private function createManpower(Branch $branchJkt, Depot $depot): array
    {
        $names = [
            'Kurnia Adi Pratama', 'Dedi Setiawan', 'Eko Prasetyo', 'Fajar Nugroho',
            'Gunawan Hidayat', 'Hadi Wijaya', 'Irfan Fachlevi', 'Joko Susilo',
            'Karno Utomo', 'Lukman Hakim', 'Maman Surahman', 'Nana Sumarna',
            'Otong Rosuloh', 'Purwanto', 'Qori Saputra', 'Rudi Hermawan',
        ];

        $manpowerList = [];
        foreach ($names as $i => $name) {
            $mp = Manpower::firstOrCreate(
                ['name' => $name],
                [
                    'domain' => 'internal',
                    'skills' => ['loading', 'unloading', 'forklift'],
                    'certs' => ['SIO Forklift', 'K3'],
                    'phone' => '08'.rand(10, 99).rand(1000, 9999).rand(1000, 9999),
                    'branch_id' => $branchJkt->id,
                    'depot_id' => $depot->id,
                    'active' => true,
                ]
            );
            $manpowerList[] = $mp;
        }

        return $manpowerList;
    }

    private function createVoyages(Port $portTpri, Port $portBtg, Port $portMnd): array
    {
        $shippingLine = ShippingLine::firstOrCreate(
            ['name' => 'JSS Shipping Line'],
            ['code' => 'JSS']
        );

        $vesselNames = ['KM Bintang Timur', 'KM Sabuk Nusantara', 'KM Nusantara', 'KM Logindo 1', 'KM Logindo 2'];
        $voyages = [];

        for ($i = 0; $i < 20; $i++) {
            $dayOffset = $i * 2;
            $vesselName = $vesselNames[$i % count($vesselNames)];

            $vessel = Vessel::firstOrCreate(
                ['name' => $vesselName],
                [
                    'shipping_line_id' => $shippingLine->id,
                    'code' => 'V'.($i + 1),
                ]
            );

            $voyages[] = Voyage::firstOrCreate(
                ['voyage_no' => 'JSS-'.date('y').'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'shipping_line_id' => $shippingLine->id,
                    'vessel_id' => $vessel->id,
                    'pol_id' => $portTpri->id,
                    'pod_id' => $portBtg->id,
                    'etd' => now()->addDays($dayOffset),
                    'eta' => now()->addDays($dayOffset + 3),
                    'period_month' => now()->startOfMonth(),
                ]
            );
        }

        return $voyages;
    }

    private function generate30DaysActivities(array $data): void
    {
        $fcJkt = $data['fcJkt'];
        $fcBtg = $data['fcBtg'];
        $depotTpri = $data['depotTpri'];
        $depotBtg = $data['depotBtg'];
        $branchJkt = $data['branchJkt'];
        $branchBtg = $data['branchBtg'];
        $cityJkt = $data['cityJkt'];
        $cityBtg = $data['cityBtg'];
        $cityMnd = $data['cityMnd'];
        $portTpri = $data['portTpri'];
        $portBtg = $data['portBtg'];
        $customerTAM = $data['customerTAM'];
        $customerIsuzu = $data['customerIsuzu'];
        $customerDaihatsu = $data['customerDaihatsu'];
        $manpowerList = $data['manpowerList'];
        $voyages = $data['voyages'];

        $shipmentCount = 1;
        $loadingCount = 1;
        $briefingCount = 1;

        for ($day = 30; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $dayName = $date->format('D');
            $isWeekend = in_array($dayName, ['Sat', 'Sun']);

            $this->command->info("Day {$day} ({$date->format('Y-m-d')}, {$dayName})");

            if ($isWeekend) {
                $this->command->info('  - Weekend, skipping briefing');

                continue;
            }

            $dailyBriefing = $this->createDailyBriefing(
                $date, $fcJkt, $depotTpri, $branchJkt,
                $manpowerList, $briefingCount++
            );

            $sessionsPerDay = rand(2, 4);
            for ($s = 0; $s < $sessionsPerDay; $s++) {
                if ($shipmentCount > 50) {
                    break;
                }

                $statusRand = rand(1, 100);
                if ($statusRand <= 10) {
                    $status = 'draft';
                    $progress = 0;
                } elseif ($statusRand <= 25) {
                    $status = 'mp_attendance';
                    $progress = 20;
                } elseif ($statusRand <= 40) {
                    $status = 'health_check';
                    $progress = 35;
                } elseif ($statusRand <= 55) {
                    $status = 'apd_check';
                    $progress = 50;
                } elseif ($statusRand <= 70) {
                    $status = 'rack_container_check';
                    $progress = 65;
                } elseif ($statusRand <= 85) {
                    $status = 'completed';
                    $progress = 100;
                } else {
                    $status = 'unloading';
                    $progress = 50;
                }

                $customer = [$customerTAM, $customerIsuzu, $customerDaihatsu][array_rand([0, 1, 2])];
                $destinations = [
                    ['city' => $cityBtg, 'port' => $portBtg],
                    ['city' => $cityMnd, 'port' => $portMnd],
                ];
                $dest = $destinations[array_rand($destinations)];

                $shipment = $this->createShipment(
                    $date, $customer, $branchJkt, $cityJkt, $dest['city'],
                    $portTpri, $dest['port'], $depotTpri, $fcJkt, $voyages,
                    $shipmentCount++, $status
                );

                $loadingSession = $this->createLoadingSession(
                    $date, $shipment, $depotTpri, $fcJkt, $branchJkt,
                    $dailyBriefing, $manpowerList, $loadingCount++, $status
                );

                if ($loadingSession && $progress >= 65) {
                    $this->createChecks($loadingSession, $fcJkt, $progress);
                }

                $this->command->info("  + Shipment {$shipment->code} ({$status}) - Loading LD-".str_pad($loadingCount - 1, 4, '0', STR_PAD_LEFT));
            }

            if ($day % 7 === 0) {
                $this->createUnloadingSessions(
                    $date, $fcBtg, $depotBtg, $branchBtg, $cityBtg,
                    $customerTAM, $portTpri, $portBtg, $fcJkt, $voyages, $day
                );
            }
        }
    }

    private function createDailyBriefing(
        $date, User $fc, Depot $depot, Branch $branch,
        array $manpowerList, int $briefingNum
    ): BriefingSession {
        $presentMP = collect($manpowerList)->random(rand(10, 14));
        $headcount = rand(12, 15);

        $briefing = BriefingSession::create([
            'date' => $date->format('Y-m-d'),
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'notes' => "Briefing harian - {$date->format('d M Y')}",
            'summary_headcount' => $headcount,
            'summary_sufficient' => count($presentMP) >= $headcount,
            'mp_check_status' => MPCheckStatus::Approved,
            'approved_at' => $date->copy()->addHours(rand(1, 3)),
            'approved_by' => $fc->id,
        ]);

        foreach ($presentMP as $mp) {
            BriefingAttendance::create([
                'session_id' => $briefing->id,
                'manpower_id' => $mp->id,
                'attendance_status' => AttendanceStatus::Present,
                'temperature' => rand(35, 37) + (rand(0, 9) / 10),
                'bp_systolic' => rand(110, 130),
                'bp_diastolic' => rand(70, 85),
                'health_complaint' => rand(1, 10) > 8 ? 'Sakit kepala ringan' : null,
                'has_ppe' => true,
                'recheck_result' => null,
                'remark' => null,
            ]);
        }

        $absent = count($manpowerList) - count($presentMP);
        if ($absent > 0) {
            $absentMP = collect($manpowerList)->filter(fn ($mp) => ! $presentMP->contains($mp))->take($absent);
            foreach ($absentMP as $mp) {
                BriefingAttendance::create([
                    'session_id' => $briefing->id,
                    'manpower_id' => $mp->id,
                    'attendance_status' => rand(1, 10) > 7 ? AttendanceStatus::Sick : AttendanceStatus::Absent,
                    'health_complaint' => null,
                ]);
            }
        }

        $checklistItems = [
            ['item' => 'Helm Safety', 'type' => 'ppe'],
            ['item' => 'Rompi Reflektif', 'type' => 'ppe'],
            ['item' => 'Sepatu Safety', 'type' => 'ppe'],
            ['item' => 'Sarung Tangan', 'type' => 'ppe'],
            ['item' => 'Safety Belt', 'type' => 'ppe'],
            ['item' => ' Briefing Pemahaman Muatan', 'type' => 'safety'],
            ['item' => 'Pemahaman Prosedur Darurat', 'type' => 'safety'],
            ['item' => 'Cek Kondisi Rack', 'type' => 'equipment'],
        ];

        foreach ($checklistItems as $cl) {
            BriefingChecklist::create([
                'session_id' => $briefing->id,
                'item' => $cl['item'],
                'type' => $cl['type'],
                'status' => 'done',
            ]);
        }

        return $briefing;
    }

    private function createShipment(
        $date, Customer $customer, Branch $branch, City $origin, City $dest,
        Port $pol, Port $pod, Depot $depot, User $fc, array $voyages,
        int $num, string $status
    ): Shipment {
        $voyage = $voyages[array_rand($voyages)];
        $containerSize = ['20ft', '40ft'][rand(0, 1)];
        $containerQty = rand(1, 3);

        $shipmentStatuses = [
            'draft' => ShipmentStatus::Draft,
            'mp_attendance' => ShipmentStatus::Pending,
            'health_check' => ShipmentStatus::Pending,
            'apd_check' => ShipmentStatus::Pending,
            'rack_container_check' => ShipmentStatus::Pending,
            'completed' => ShipmentStatus::Pending,
            'unloading' => ShipmentStatus::Transit,
        ];

        $shipment = Shipment::create([
            'code' => 'JSS-'.$num.'-'.strtoupper(substr($customer->name, 0, 3)),
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $origin->id,
            'destination_city_id' => $dest['city']->id,
            'branch_id' => $branch->id,
            'assigned_depot_id' => $depot->id,
            'mode' => ShipmentMode::Sea->value,
            'status' => $shipmentStatuses[$status],
            'service_type' => 'sea_freight',
            'service_option' => 'fcl',
            'cargo_type' => 'vehicle',
            'container_size' => $containerSize,
            'container_qty' => $containerQty,
            'packages_total' => rand(5, 20),
            'cbm_total' => rand(50, 200),
            'weight_total' => rand(10000, 30000),
            'pol_id' => $pol->id,
            'pod_id' => $dest['port']->id,
            'pic_name' => 'PIC '.$customer->name,
            'pic_phone' => '08'.rand(10, 99).rand(1000, 9999).rand(100, 999),
            'delivery_contact_name' => 'Warehouse '.$dest['city']->name,
            'delivery_contact_phone' => '08'.rand(10, 99).rand(1000, 9999).rand(100, 999),
            'notes' => "Shipment untuk {$customer->name} - Status: {$status}",
        ]);

        return $shipment;
    }

    private function createLoadingSession(
        $date, Shipment $shipment, Depot $depot, User $fc, Branch $branch,
        $briefing, array $manpowerList, int $loadingNum, string $status
    ) {
        $mpRequired = rand(6, 12);
        $mpPresent = $status === 'draft' ? 0 : rand($mpRequired, $mpRequired + 2);
        $mpFit = $status === 'draft' ? 0 : rand($mpPresent - 1, $mpPresent);

        $loadingStatuses = [
            'draft' => LoadingStatus::Draft,
            'mp_attendance' => LoadingStatus::MpAttendanceCheck,
            'health_check' => LoadingStatus::HealthCheck,
            'apd_check' => LoadingStatus::ApdCheck,
            'rack_container_check' => LoadingStatus::RackContainerCheck,
            'completed' => LoadingStatus::Completed,
            'unloading' => LoadingStatus::Draft,
        ];

        $session = LoadingSession::create([
            'code' => 'LD-'.str_pad($loadingNum, 4, '0', STR_PAD_LEFT),
            'shipment_id' => $shipment->id,
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'branch_id' => $branch->id,
            'briefing_session_id' => $briefing->id ?? null,
            'operation_type' => $status === 'unloading' ? LoadingOperationType::Unloading : LoadingOperationType::Loading,
            'status' => $loadingStatuses[$status],
            'mp_required' => $mpRequired,
            'mp_present' => $mpPresent,
            'mp_absent' => $mpRequired - $mpPresent > 0 ? $mpRequired - $mpPresent : 0,
            'mp_sick' => rand(0, 2),
            'mp_sufficient' => $mpPresent >= $mpRequired,
            'mp_fit_count' => $mpFit,
            'mp_unfit_count' => $mpPresent - $mpFit,
            'apd_complete' => in_array($status, ['completed', 'unloading']),
            'apd_clean' => in_array($status, ['completed', 'unloading']),
            'equipment_safe' => in_array($status, ['completed', 'unloading']),
            'rack_container_safe' => in_array($status, ['completed', 'unloading']),
            'unit_measurements_ok' => in_array($status, ['completed', 'unloading']),
            'stock_apd_sufficient' => true,
            'mp_attendance_completed' => in_array($status, ['mp_attendance', 'health_check', 'apd_check', 'rack_container_check', 'completed', 'unloading']),
            'health_check_completed' => in_array($status, ['health_check', 'apd_check', 'rack_container_check', 'completed', 'unloading']),
            'apd_check_completed' => in_array($status, ['apd_check', 'rack_container_check', 'completed', 'unloading']),
            'equipment_check_completed' => in_array($status, ['completed', 'unloading']),
            'rack_container_check_completed' => in_array($status, ['rack_container_check', 'completed', 'unloading']),
            'unit_check_completed' => in_array($status, ['completed', 'unloading']),
            'stock_apd_check_completed' => in_array($status, ['completed', 'unloading']),
            'final_decision_completed' => in_array($status, ['completed']),
            'final_decision_status' => in_array($status, ['completed']) ? FinalDecisionStatus::Go : null,
            'started_at' => in_array($status, ['mp_attendance', 'health_check', 'apd_check', 'rack_container_check', 'completed', 'unloading']) ? $date->copy()->addHours(rand(7, 10)) : null,
            'completed_at' => $status === 'completed' ? $date->copy()->addHours(rand(12, 16)) : null,
            'general_notes' => "Loading session untuk {$shipment->code}",
        ]);

        return $session;
    }

    private function createChecks(LoadingSession $session, User $fc, int $progress): void
    {
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
        ]);

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
        ]);

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
        ]);

        if ($progress >= 85) {
            LoadingFinalDecision::create([
                'loading_session_id' => $session->id,
                'status' => FinalDecisionStatus::Go,
                'category' => 'automatic',
                'reason' => 'All pre-checks passed',
                'critical_issues' => [],
                'warning_issues' => [],
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
                'requested_at' => now(),
            ]);
        }
    }

    private function createUnloadingSessions(
        $date, User $fcBtg, Depot $depotBtg, Branch $branchBtg, City $cityBtg,
        Customer $customer, Port $portTpri, Port $portBtg, User $fcJkt,
        array $voyages, int $dayOffset
    ): void {
        $completedShipments = Shipment::where('status', ShipmentStatus::Transit)
            ->whereHas('tracks', fn ($q) => $q->where('status', TrackStatus::VesselArrival->value))
            ->where('destination_city_id', $cityBtg->id)
            ->where('created_at', '<=', now()->subDays($dayOffset - 7))
            ->limit(rand(1, 3))
            ->get();

        foreach ($completedShipments as $shipment) {
            $loadingSession = LoadingSession::create([
                'code' => 'UNLD-BTG-'.str_pad($dayOffset, 4, '0', STR_PAD_LEFT),
                'shipment_id' => $shipment->id,
                'depot_id' => $depotBtg->id,
                'coordinator_user_id' => $fcBtg->id,
                'branch_id' => $branchBtg->id,
                'operation_type' => LoadingOperationType::Unloading,
                'status' => LoadingStatus::Draft,
                'mp_required' => rand(4, 8),
                'mp_present' => 0,
                'mp_sufficient' => false,
                'general_notes' => "Unloading session untuk {$shipment->code} - FC Bontang",
            ]);

            $this->command->info("  + Unloading Session: {$loadingSession->code}");
        }
    }
}

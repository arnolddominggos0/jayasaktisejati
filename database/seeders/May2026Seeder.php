<?php

namespace Database\Seeders;

use App\Enums\VesselPlanStatus;
use App\Enums\VoyageDelayReason;
use App\Enums\VoyageRegistryStatus;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\Shipment;
use App\Models\ShippingSchedule;
use App\Models\Vessel;
use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use App\Models\VesselPlan;
use App\Models\VesselPlanItem;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * May 2026 Canonical Operational Seeding
 *
 * Transforms real May 2026 operational data into canonical entities.
 * Flow: VesselPlan (Final) → VesselPlanItem → Voyage → Monitoring → Shipment
 */
class May2026Seeder extends Seeder
{
    /**
     * Real May 2026 operational dataset.
     * JSS column → voyages.voyage_no
     * LTS column → ignored
     * Default route: JKT → BTG
     */
    private array $dataset = [
        [
            'etb' => '2026-05-07 00:00:00',
            'etd' => '2026-05-09 00:00:00',
            'eta' => '2026-05-21 00:00:00',
            'vessel' => 'KM Tanto Sejahtera V.154',
            'cargo_plan' => 58,
            'voyage_no' => 'VOY179MVLMNDJSS',
            'shipping_line' => 'Tanto',
            'status' => 'completed',
            'atb' => '2026-05-05 00:00:00',
            'closing_at' => '2026-05-07 12:00:00',
            'atd' => '2026-05-08 00:00:00',
            'ata' => null,
            'cargo_actual' => 17,
            'otb' => true,
            'otd' => true,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-08 00:00:00',
            'etd' => '2026-05-10 00:00:00',
            'eta' => '2026-05-22 00:00:00',
            'vessel' => 'KM Tanto Cahaya V.384',
            'cargo_plan' => 6,
            'voyage_no' => 'VOY151TSLMNDJSS',
            'shipping_line' => 'Tanto',
            'status' => 'completed',
            'atb' => '2026-05-06 00:00:00',
            'closing_at' => '2026-05-07 12:00:00',
            'atd' => '2026-05-09 00:00:00',
            'ata' => '2026-05-17 00:00:00',
            'cargo_actual' => 6,
            'otb' => true,
            'otd' => true,
            'ota' => true,
        ],

        [
            'etb' => '2026-05-13 00:00:00',
            'etd' => '2026-05-15 00:00:00',
            'eta' => '2026-05-27 00:00:00',
            'vessel' => 'KM Tanto Jaya V.309',
            'cargo_plan' => 57,
            'voyage_no' => 'VOY180MVIMNDJSS',
            'shipping_line' => 'Tanto',
            'status' => 'completed',
            'atb' => '2026-05-09 00:00:00',
            'closing_at' => '2026-05-12 14:00:00',
            'atd' => '2026-05-12 00:00:00',
            'ata' => null,
            'cargo_actual' => 38,
            'otb' => true,
            'otd' => true,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-14 00:00:00',
            'etd' => '2026-05-16 00:00:00',
            'eta' => '2026-05-26 00:00:00',
            'vessel' => 'KM Meratus Gorontalo V.210',
            'cargo_plan' => 0,
            'voyage_no' => 'VOY181MRTMNDJSS',
            'shipping_line' => 'Meratus',
            'status' => 'planned',
            'atb' => null,
            'closing_at' => null,
            'atd' => null,
            'ata' => null,
            'cargo_actual' => 0,
            'otb' => false,
            'otd' => false,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-17 00:00:00',
            'etd' => '2026-05-19 00:00:00',
            'eta' => '2026-05-31 00:00:00',
            'vessel' => 'KM Tanto Tangguh V.248',
            'cargo_plan' => 20,
            'voyage_no' => 'VOY182TTGMNDJSS',
            'shipping_line' => 'Tanto',
            'status' => 'sailing',
            'atb' => '2026-05-13 00:00:00',
            'closing_at' => '2026-05-13 10:00:00',
            'atd' => '2026-05-17 00:00:00',
            'ata' => null,
            'cargo_actual' => 44,
            'otb' => true,
            'otd' => true,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-18 00:00:00',
            'etd' => '2026-05-20 00:00:00',
            'eta' => '2026-05-30 00:00:00',
            'vessel' => 'KM Meratus Wakotobi V.211',
            'cargo_plan' => 14,
            'voyage_no' => 'VOY183MRWMNDJSS',
            'shipping_line' => 'Meratus',
            'status' => 'planned',
            'atb' => null,
            'closing_at' => null,
            'atd' => null,
            'ata' => null,
            'cargo_actual' => 0,
            'otb' => false,
            'otd' => false,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-23 00:00:00',
            'etd' => '2026-05-25 00:00:00',
            'eta' => '2026-06-06 00:00:00',
            'vessel' => 'KM Tanto Salam V.161',
            'cargo_plan' => 49,
            'voyage_no' => 'VOY184TSLMNDJSS',
            'shipping_line' => 'Tanto',
            'status' => 'planned',
            'atb' => null,
            'closing_at' => null,
            'atd' => null,
            'ata' => null,
            'cargo_actual' => 0,
            'otb' => false,
            'otd' => false,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-24 00:00:00',
            'etd' => '2026-05-26 00:00:00',
            'eta' => '2026-06-05 00:00:00',
            'vessel' => 'KM Meratus Medan V.212',
            'cargo_plan' => 8,
            'voyage_no' => 'VOY185MRMMNDJSS',
            'shipping_line' => 'Meratus',
            'status' => 'planned',
            'atb' => null,
            'closing_at' => null,
            'atd' => null,
            'ata' => null,
            'cargo_actual' => 0,
            'otb' => false,
            'otd' => false,
            'ota' => false,
        ],

        [
            'etb' => '2026-05-28 00:00:00',
            'etd' => '2026-05-30 00:00:00',
            'eta' => '2026-06-11 00:00:00',
            'vessel' => 'KM Tanto Sejahtera V.155',
            'cargo_plan' => 27,
            'voyage_no' => 'VOY186TSHMNDJSS',
            'shipping_line' => 'Tanto',
            'status' => 'planned',
            'atb' => null,
            'closing_at' => null,
            'atd' => null,
            'ata' => null,
            'cargo_actual' => 0,
            'otb' => false,
            'otd' => false,
            'ota' => false,
        ],
    ];

    public function run(): void
    {
        $this->command->info('=== May 2026 Canonical Operational Seeding ===');

        // Stage 1: Base entities
        $base = $this->seedBaseEntities();

        // Stage 2: VesselPlan (Final)
        $vesselPlan = $this->seedVesselPlan($base);

        // Stage 3: VesselPlanItems + Voyages
        $voyages = $this->seedVesselPlanItemsAndVoyages($vesselPlan, $base);

        // Stage 4: ShippingSchedule (transitional layer)
        $this->seedShippingSchedules($voyages);

        // Stage 5: Shipments consuming Voyages
        $this->seedShipments($voyages, $base);

        // Stage 6: VesselCheck scenarios
        $this->seedVesselChecks($voyages);

        $this->command->info('=== Seeding Complete ===');
        $this->command->info('Voyages: ' . count($voyages));
        $this->command->info('Scheduled: ' . collect($voyages)->where('atd_at', null)->count());
        $this->command->info('Sailing: ' . collect($voyages)->whereNotNull('atd_at')->whereNull('ata_at')->count());
        $this->command->info('Completed: ' . collect($voyages)->whereNotNull('ata_at')->count());
    }

    /**
     * Stage 1: Seed base entities required for the operational flow.
     */
    private function seedBaseEntities(): array
    {
        $this->command->info('Stage 1: Seeding base entities...');

        // Ports: JKT (POL) and BTG (POD)
        $pol = Port::updateOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Tanjung Priok', 'city' => 'Jakarta']
        );
        $pod = Port::updateOrCreate(
            ['code' => 'BTG'],
            ['name' => 'Pelabuhan Bitung', 'city' => 'Bitung']
        );

        // Shipping Lines
        $tanto = ShippingLine::updateOrCreate(
            ['code' => 'TANTO'],
            ['name' => 'Tanto']
        );
        $meratus = ShippingLine::updateOrCreate(
            ['code' => 'MERATUS'],
            ['name' => 'Meratus']
        );

        // Customer (TAM as the operational customer)
        $customer = Customer::updateOrCreate(
            ['email' => 'tam@jss.local'],
            [
                'code' => 'TAM-0001',
                'name' => 'TAM',
                'phone' => '081234567890',
                'type' => 'company',
            ]
        );

        // Branches (required for shipments)
        $jktBranch = Branch::updateOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta']
        );
        $mdoBranch = Branch::updateOrCreate(
            ['code' => 'MDO'],
            ['name' => 'Manado']
        );

        // Cities (for shipments)
        $jktCity = City::updateOrCreate(
            ['name' => 'Jakarta'],
            ['province' => 'DKI Jakarta', 'slug' => 'jakarta']
        );
        $mdoCity = City::updateOrCreate(
            ['name' => 'Manado'],
            ['province' => 'Sulawesi Utara', 'slug' => 'manado']
        );

        // Vessels
        $vesselMap = [];
        foreach ($this->dataset as $row) {
            $slCode = $row['shipping_line'] === 'Tanto' ? 'TANTO' : 'MERATUS';
            $slId = $slCode === 'TANTO' ? $tanto->id : $meratus->id;

            $vessel = Vessel::updateOrCreate(
                ['name' => $row['vessel']],
                [
                    'shipping_line_id' => $slId,
                    'code' => strtoupper(str_replace([' ', '.'], ['', ''], $row['vessel'])),
                    'capacity' => 200,
                ]
            );
            $vesselMap[$row['vessel']] = $vessel;
        }

        $this->command->info('  ✓ Ports, ShippingLines, Customer, Branches, Cities, Vessels');

        return compact('pol', 'pod', 'tanto', 'meratus', 'customer', 'jktBranch', 'mdoBranch', 'jktCity', 'mdoCity', 'vesselMap');
    }

    /**
     * Stage 2: Create finalized VesselPlan for May 2026.
     */
    private function seedVesselPlan(array $base): VesselPlan
    {
        $this->command->info('Stage 2: Creating VesselPlan (Final)...');

        $periodMonth = Carbon::create(2026, 5, 1)->startOfMonth()->toDateString();

        $vesselPlan = VesselPlan::updateOrCreate(
            [
                'period_month' => $periodMonth,
                'route_code' => 'JKT-BTG',
            ],
            [
                'customer_id' => $base['customer']->id,
                'pol_id' => $base['pol']->id,
                'pod_id' => $base['pod']->id,
                'status' => VesselPlanStatus::Final,
                'sent_at' => Carbon::parse('2026-04-28 09:00:00'),
                'sent_by' => null,
            ]
        );

        $this->command->info("  ✓ VesselPlan #{$vesselPlan->id} — May 2026 (Final)");

        return $vesselPlan;
    }

    /**
     * Stage 3: Create VesselPlanItems and generate Voyages.
     */
    private function seedVesselPlanItemsAndVoyages(VesselPlan $vesselPlan, array $base): array
    {
        $this->command->info('Stage 3: Generating VesselPlanItems + Voyages...');

        $voyages = [];

        foreach ($this->dataset as $row) {
            $sl = $row['shipping_line'] === 'Tanto'
                ? $base['tanto']
                : $base['meratus'];

            $vessel = $base['vesselMap'][$row['vessel']];

            $etd = Carbon::parse($row['etd']);
            $eta = Carbon::parse($row['eta']);

            $periodMonth = $etd->copy()
                ->startOfMonth()
                ->toDateString();

            $item = VesselPlanItem::updateOrCreate(
                [
                    'vessel_plan_id' => $vesselPlan->id,
                    'vessel_id' => $vessel->id,
                    'planned_etd' => $etd,
                ],
                [
                    'shipping_line_id' => $sl->id,
                    'planned_eta' => $eta,
                ]
            );

            $voyageData = [
                'vessel_plan_id' => $vesselPlan->id,
                'vessel_plan_item_id' => $item->id,
                'shipping_line_id' => $sl->id,
                'vessel_id' => $vessel->id,

                'pol_id' => $base['pol']->id,
                'pod_id' => $base['pod']->id,

                'voyage_no' => $row['voyage_no'],

                'service' => 'JKT-BTG',

                'etd' => $etd,
                'eta' => $eta,

                'etb' => $etd->copy()->subHours(4),

                'cargo_plan' => $row['cargo_plan'],
                'cargo_actual' => $row['cargo_actual'] ?? 0,

                'period_month' => $periodMonth,
            ];

            if ($row['status'] === 'completed') {

                $voyageData['atd_at'] = Carbon::parse($row['atd']);
                $voyageData['ata_at'] = Carbon::parse($row['ata']);

                $voyageData['atb_at'] = Carbon::parse($row['atd'])
                    ->subHours(2);

                $voyageData['closing_at'] = Carbon::parse($row['atd'])
                    ->subHour();

                $voyageData['registry_status'] = VoyageRegistryStatus::COMPLETED;
            } elseif ($row['status'] === 'sailing') {

                $voyageData['atd_at'] = Carbon::parse($row['atd']);

                $voyageData['atb_at'] = Carbon::parse($row['atd'])
                    ->subHours(2);

                $voyageData['closing_at'] = Carbon::parse($row['atd'])
                    ->subHour();

                $voyageData['registry_status'] = VoyageRegistryStatus::ACTIVE;
            } elseif ($row['status'] === 'delayed') {

                $voyageData['atd_at'] = Carbon::parse($row['atd']);

                $voyageData['atb_at'] = Carbon::parse($row['atd'])
                    ->subHours(2);

                $voyageData['closing_at'] = Carbon::parse($row['atd'])
                    ->subHour();

                $voyageData['manual_delay_reason'] = VoyageDelayReason::VESSEL;

                $voyageData['final_note'] =
                    'Delay due to vessel maintenance. ETD shifted by 7 hours.';

                $voyageData['registry_status'] = VoyageRegistryStatus::DELAYED;
            } else {

                $voyageData['registry_status'] = VoyageRegistryStatus::PLANNED;
            }

            $voyage = Voyage::updateOrCreate(
                [
                    'voyage_no' => $row['voyage_no'],
                ],
                $voyageData
            );

            if (! $item->voyage_id) {
                $item->voyage_id = $voyage->id;
                $item->save();
            }

            $voyages[] = $voyage;

            $this->command->info(
                "  ✓ {$row['voyage_no']} — {$row['vessel']} — {$row['status']}"
            );
        }

        return $voyages;
    }

    /**
     * Stage 4: Create ShippingSchedule as transitional compatibility layer.
     */
    private function seedShippingSchedules(array $voyages): void
    {
        $this->command->info('Stage 4: Generating ShippingSchedule (transitional)...');

        foreach ($voyages as $voyage) {
            ShippingSchedule::updateOrCreate(
                ['voyage_id' => $voyage->id],
                [
                    'shipping_line_id' => $voyage->shipping_line_id,
                    'vessel_id' => $voyage->vessel_id,
                    'vessel_name' => $voyage->vessel?->name,
                    'voyage_no' => $voyage->voyage_no,
                    'cargo_plan' => $voyage->cargo_plan ?? 0,
                    'jss' => $voyage->voyage_no,
                    'etd' => $voyage->etd,
                    'eta' => $voyage->eta,
                    'period_month' => $voyage->period_month,
                    'state' => 'final',
                ]
            );
        }

        $this->command->info('  ✓ ' . count($voyages) . ' ShippingSchedule records');
    }

    /**
     * Stage 5: Create Shipments consuming Voyage data.
     */
    private function seedShipments(array $voyages, array $base): void
    {
        $this->command->info('Stage 5: Generating Shipments (consuming Voyages)...');

        $shipmentCount = 0;
        foreach ($voyages as $voyage) {
            // Each voyage gets 1-3 shipments
            $numShipments = rand(1, 3);
            for ($i = 0; $i < $numShipments; $i++) {
                $cargo = rand(1, 20);

                $shipment = Shipment::updateOrCreate(
                    [
                        'code' => "SHP-{$voyage->voyage_no}-" . ($i + 1),
                    ],
                    [
                        'customer_id' => $base['customer']->id,
                        'branch_id' => $base['jktBranch']->id,
                        'mode' => 'sea',
                        'service_type' => 'sea_freight',
                        'service_option' => 'fcl',
                        'cargo_type' => 'vehicle',
                        'status' => 'pending',
                        'request_type' => 'sppb_do',
                        'origin_city_id' => $base['jktCity']->id,
                        'destination_city_id' => $base['mdoCity']->id,
                        'route_from' => 'Jakarta',
                        'route_to' => 'Bitung',
                        'route_summary' => 'Jakarta → Bitung',
                        'voyage_id' => $voyage->id,
                        'vessel_name' => $voyage->vessel?->name,
                        'voyage' => $voyage->voyage_no,
                        'pol' => $base['pol']->name,
                        'pod' => $base['pod']->name,
                        'etd' => $voyage->etd,
                        'eta' => $voyage->eta,
                        'container_size' => '40HC',
                        'container_qty' => 1,
                        'packages_total' => $cargo,
                        'pic_name' => 'PIC ' . ($i + 1),
                        'pic_phone' => '0812' . rand(10000000, 99999999),
                        'priority' => 'normal',
                        'pol_id' => $base['pol']->id,
                        'pod_id' => $base['pod']->id,
                        'confirm_is_true' => true,
                    ]
                );
                $shipmentCount++;
            }
        }

        $this->command->info("  ✓ {$shipmentCount} Shipment records");
    }

    /**
     * Stage 6: Create VesselCheck scenarios for operational realism.
     */
    private function seedVesselChecks(array $voyages): void
    {
        $this->command->info('Stage 6: Generating VesselCheck scenarios...');

        $checkCount = 0;

        foreach ($voyages as $voyage) {

            $schedule = ShippingSchedule::where(
                'voyage_id',
                $voyage->id
            )->first();

            if (! $schedule) {
                continue;
            }

            if ($voyage->etd) {

                $d1Date = $voyage->etd
                    ->copy()
                    ->subDay()
                    ->startOfDay();

                $d2Date = $voyage->etd
                    ->copy()
                    ->subDays(2)
                    ->startOfDay();

                VesselCheck::updateOrCreate(
                    [
                        'shipping_schedule_id' => $schedule->id,
                        'check_date' => $d1Date,
                    ],
                    [
                        'voyage_id' => $voyage->id,
                        'day_code' => 'D-1',
                        'etd_plan' => $voyage->etd,
                        'etd_current' => $voyage->etd,
                        'status' => rand(0, 4) === 0
                            ? 'potential_delay'
                            : 'on_schedule',
                        'note' => 'Operational readiness check',
                        'source' => 'System',
                    ]
                );

                $checkCount++;

                VesselCheck::updateOrCreate(
                    [
                        'shipping_schedule_id' => $schedule->id,
                        'check_date' => $d2Date,
                    ],
                    [
                        'voyage_id' => $voyage->id,
                        'day_code' => 'D-2',
                        'etd_plan' => $voyage->etd,
                        'etd_current' => $voyage->etd,
                        'status' => 'on_schedule',
                        'note' => 'All systems nominal',
                        'source' => 'System',
                    ]
                );

                $checkCount++;
            }

            if ($voyage->atd_at) {

                $h1Date = $voyage->atd_at
                    ->copy()
                    ->subDay()
                    ->startOfDay();

                $exists = VesselCheck::where(
                    'shipping_schedule_id',
                    $schedule->id
                )
                    ->whereDate('check_date', $h1Date)
                    ->exists();

                if (! $exists) {

                    VesselCheck::create([
                        'shipping_schedule_id' => $schedule->id,
                        'voyage_id' => $voyage->id,
                        'day_code' => 'H-1',
                        'check_date' => $h1Date,
                        'etd_plan' => $voyage->etd,
                        'etd_current' => $voyage->atd_at,
                        'status' => 'on_schedule',
                        'note' => 'Ready for departure',
                        'source' => 'System',
                    ]);

                    $checkCount++;
                }
            }
        }

        $this->command->info("  ✓ {$checkCount} VesselCheck records");
    }
}

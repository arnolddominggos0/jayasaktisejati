<?php

namespace Database\Seeders;

use App\Enums\VesselPlanStatus;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\Vessel;
use App\Models\VesselPlan;
use App\Models\VesselPlanItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Lightweight planning-only seeder.
 *
 * Purpose:
 * - testing Vessel Plan workflow
 * - testing SOP gap validation
 * - testing draft/revision/final flow
 * - testing UI planning layer
 *
 * This seeder intentionally DOES NOT create:
 * - Voyage
 * - Shipment
 * - ShippingSchedule
 * - VesselCheck
 *
 * Planning layer only.
 */
class May2026VesselPlanDraftSeeder extends Seeder
{
    /**
     * Pure planning dataset.
     */
    private array $dataset = [

        [
            'shipping_line' => 'Tanto',
            'vessel' => 'KM Tanto Sejahtera V.154',
            'etd' => '2026-05-09 00:00:00',
            'eta' => '2026-05-21 00:00:00',
        ],

        [
            'shipping_line' => 'Tanto',
            'vessel' => 'KM Tanto Cahaya V.384',
            'etd' => '2026-05-10 00:00:00',
            'eta' => '2026-05-22 00:00:00',
        ],

        [
            'shipping_line' => 'Tanto',
            'vessel' => 'KM Tanto Jaya V.309',
            'etd' => '2026-05-15 00:00:00',
            'eta' => '2026-05-27 00:00:00',
        ],

        [
            'shipping_line' => 'Meratus',
            'vessel' => 'KM Meratus Gorontalo V.210',
            'etd' => '2026-05-16 00:00:00',
            'eta' => '2026-05-26 00:00:00',
        ],

        [
            'shipping_line' => 'Tanto',
            'vessel' => 'KM Tanto Tangguh V.248',
            'etd' => '2026-05-19 00:00:00',
            'eta' => '2026-05-31 00:00:00',
        ],

        [
            'shipping_line' => 'Meratus',
            'vessel' => 'KM Meratus Wakotobi V.211',
            'etd' => '2026-05-20 00:00:00',
            'eta' => '2026-05-30 00:00:00',
        ],

        [
            'shipping_line' => 'Tanto',
            'vessel' => 'KM Tanto Salam V.161',
            'etd' => '2026-05-25 00:00:00',
            'eta' => '2026-06-06 00:00:00',
        ],

        [
            'shipping_line' => 'Meratus',
            'vessel' => 'KM Meratus Medan V.212',
            'etd' => '2026-05-26 00:00:00',
            'eta' => '2026-06-05 00:00:00',
        ],

        [
            'shipping_line' => 'Tanto',
            'vessel' => 'KM Tanto Sejahtera V.155',
            'etd' => '2026-05-30 00:00:00',
            'eta' => '2026-06-11 00:00:00',
        ],
    ];

    public function run(): void
    {
        $this->command->info('=== May 2026 Vessel Plan Draft Seeder ===');

        /**
         * Ports
         */
        $pol = Port::updateOrCreate(
            ['code' => 'JKT'],
            [
                'name' => 'Tanjung Priok',
                'city' => 'Jakarta',
            ]
        );

        $pod = Port::updateOrCreate(
            ['code' => 'BTG'],
            [
                'name' => 'Pelabuhan Bitung',
                'city' => 'Bitung',
            ]
        );

        /**
         * Shipping Lines
         */
        $tanto = ShippingLine::updateOrCreate(
            ['code' => 'TANTO'],
            ['name' => 'Tanto']
        );

        $meratus = ShippingLine::updateOrCreate(
            ['code' => 'MERATUS'],
            ['name' => 'Meratus']
        );

        /**
         * Customer TAM
         */
        $tamCustomer = Customer::updateOrCreate(
            ['email' => 'tam@jss.local'],
            [
                'code' => 'TAM-0001',
                'name' => 'Toyota Astra Motor',
                'phone' => '081265559397',
                'type' => 'company',
            ]
        );

        $hasjratCustomer = Customer::updateOrCreate(
            ['email' => 'hasjrat@jss.local'],
            [
                'code' => 'HASJRAT-0001',
                'name' => 'Hasjrat Abadi',
                'phone' => '081265559397',
                'type' => 'company',
            ]
        );
        /**
         * Vessel map
         */
        $vesselMap = [];

        foreach ($this->dataset as $row) {

            $shippingLine = $row['shipping_line'] === 'Tanto'
                ? $tanto
                : $meratus;

            $vessel = Vessel::updateOrCreate(
                ['name' => $row['vessel']],
                [
                    'shipping_line_id' => $shippingLine->id,
                    'code' => strtoupper(
                        str_replace([' ', '.'], ['', ''], $row['vessel'])
                    ),
                    'capacity' => 200,
                ]
            );

            $vesselMap[$row['vessel']] = $vessel;
        }

        /**
         * Vessel Plan (DRAFT)
         */
        $vesselPlan = VesselPlan::updateOrCreate(
            [
                'period_month' => Carbon::create(2026, 5, 1)
                    ->startOfMonth()
                    ->toDateString(),

                'route_code' => 'JKT-MND',
            ],
            [
                'customer_id' => $tamCustomer->id,

                'pol_id' => $pol->id,
                'pod_id' => $pod->id,

                /**
                 * IMPORTANT:
                 * Draft mode for workflow testing.
                 */
                'status' => VesselPlanStatus::Draft,

                'sent_at' => null,
                'sent_by' => null,

                'feedback_reason' => null,
                'feedback_by' => null,
                'feedback_at' => null,
            ]
        );

        $this->command->info(
            "✓ VesselPlan #{$vesselPlan->id} created as DRAFT"
        );

        /**
         * Vessel Plan Items
         */
        foreach ($this->dataset as $row) {

            $shippingLine = $row['shipping_line'] === 'Tanto'
                ? $tanto
                : $meratus;

            $vessel = $vesselMap[$row['vessel']];

            VesselPlanItem::updateOrCreate(
                [
                    'vessel_plan_id' => $vesselPlan->id,
                    'vessel_id' => $vessel->id,
                    'planned_etd' => Carbon::parse($row['etd']),
                ],
                [
                    'shipping_line_id' => $shippingLine->id,

                    'planned_eta' => Carbon::parse($row['eta']),
                ]
            );

            $this->command->info(
                "  ✓ {$row['vessel']} ({$row['shipping_line']})"
            );
        }

        $analysis = $vesselPlan->analyze();

        $this->command->info('');
        $this->command->info('=== SOP SUMMARY ===');

        $this->command->info(
            'Schedule Count: ' . ($analysis['schedule_count'] ?? 0)
        );

        $this->command->info(
            'Avg Sailing: ' . ($analysis['sailing_avg'] ?? 0) . ' hari'
        );

        $this->command->info(
            'Max Gap: ' . ($analysis['max_gap'] ?? 0) . ' hari'
        );

        $this->command->info(
            'Gap Limit: ' . ($analysis['gap_limit'] ?? 6) . ' hari'
        );

        $this->command->info(
            'SOP Status: ' . (($analysis['ok'] ?? false)
                ? 'SESUAI SOP'
                : 'PERLU REVISI')
        );

        $this->command->info('');
        $this->command->info('=== Planning-only Seeder Complete ===');
    }
}
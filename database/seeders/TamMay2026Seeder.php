<?php

namespace Database\Seeders;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TamMay2026Seeder extends Seeder
{
    private const CUSTOMER_NAME = 'Toyota Astra Motor';

    private const BRANCH_CODE = 'JKT';

    private const ROUTE_FROM = 'Jakarta';

    private const ROUTE_TO = 'Manado';

    private const POL = 'Tanjung Priok';

    private const POD = 'Bitung';

    private array $vessels = [
        [
            'vessel' => 'KM Tanto Cahaya V.384',
            'voyage_no' => 'VOY151TSLMNDJSS',
            'units' => [
                [
                    'model' => 'Avanza 1.5 G',
                    'color' => 'Silver',
                    'pickup' => '2026-05-05 08:00:00',
                    'unit_loading' => '2026-05-09 14:00:00',
                    'vessel_arrival' => '2026-05-17 09:00:00',
                    'delivered' => '2026-05-19 10:00:00',
                ],
                [
                    'model' => 'Fortuner 2.4 VRZ',
                    'color' => 'Hitam',
                    'pickup' => '2026-05-04 10:00:00',
                    'unit_loading' => '2026-05-11 16:00:00',
                    'vessel_arrival' => '2026-05-17 09:00:00',
                    'delivered' => '2026-05-19 14:00:00',
                ],
            ],
        ],
        [
            'vessel' => 'KM Tanto Jaya V.309',
            'voyage_no' => 'VOY180MVIMNDJSS',
            'units' => [
                [
                    'model' => 'Innova 2.4 V',
                    'color' => 'Putih',
                    'pickup' => '2026-05-08 07:00:00',
                    'unit_loading' => '2026-05-12 13:00:00',
                    'vessel_arrival' => '2026-05-20 08:00:00',
                    'delivered' => '2026-05-22 11:00:00',
                ],
                [
                    'model' => 'Rush 1.5 TRD',
                    'color' => 'Merah',
                    'pickup' => '2026-05-07 09:00:00',
                    'unit_loading' => '2026-05-12 15:00:00',
                    'vessel_arrival' => '2026-05-25 08:00:00',
                    'delivered' => '2026-05-28 15:00:00',
                ],
                [
                    'model' => 'Hilux 2.4 G',
                    'color' => 'Abu-Abu',
                    'pickup' => '2026-05-08 11:00:00',
                    'unit_loading' => '2026-05-13 17:00:00',
                    'vessel_arrival' => '2026-05-20 08:00:00',
                    'delivered' => '2026-05-22 09:00:00',
                ],
            ],
        ],
        [
            'vessel' => 'KM Tanto Tangguh V.248',
            'voyage_no' => 'VOY182TTGMNDJSS',
            'units' => [
                [
                    'model' => 'Yaris 1.5 E',
                    'color' => 'Putih',
                    'pickup' => '2026-05-12 08:00:00',
                    'unit_loading' => '2026-05-16 14:00:00',
                    'vessel_arrival' => '2026-05-24 10:00:00',
                    'delivered' => '2026-05-26 12:00:00',
                ],
                [
                    'model' => 'Calya 1.2 G',
                    'color' => 'Silver',
                    'pickup' => '2026-05-09 10:00:00',
                    'unit_loading' => '2026-05-18 16:00:00',
                    'vessel_arrival' => '2026-05-26 10:00:00',
                    'delivered' => '2026-05-30 16:00:00',
                ],
            ],
        ],
        [
            'vessel' => 'KM Tanto Salam V.161',
            'voyage_no' => 'VOY184TSLMNDJSS',
            'units' => [
                [
                    'model' => 'Avanza 1.3 E',
                    'color' => 'Hitam',
                    'pickup' => '2026-05-15 07:00:00',
                    'unit_loading' => '2026-05-19 13:00:00',
                    'vessel_arrival' => '2026-05-27 08:00:00',
                    'delivered' => '2026-05-31 10:00:00',
                ],
                [
                    'model' => 'Veloz 1.5',
                    'color' => 'Putih Mutiara',
                    'pickup' => '2026-05-15 09:00:00',
                    'unit_loading' => '2026-05-19 15:00:00',
                    'vessel_arrival' => '2026-05-27 08:00:00',
                    'delivered' => '2026-05-29 14:00:00',
                ],
            ],
        ],
        [
            'vessel' => 'KM Tanto Sejahtera V.155',
            'voyage_no' => 'VOY186TSHMNDJSS',
            'units' => [
                [
                    'model' => 'Fortuner 2.8 GR',
                    'color' => 'Hitam',
                    'pickup' => '2026-05-17 08:00:00',
                    'unit_loading' => '2026-05-24 14:00:00',
                    'vessel_arrival' => '2026-06-02 09:00:00',
                    'delivered' => '2026-06-06 11:00:00',
                ],
                [
                    'model' => 'Innova 2.0 G',
                    'color' => 'Silver',
                    'pickup' => '2026-05-19 10:00:00',
                    'unit_loading' => '2026-05-23 16:00:00',
                    'vessel_arrival' => '2026-05-31 09:00:00',
                    'delivered' => '2026-06-02 15:00:00',
                ],
            ],
        ],
    ];

    private int $shipmentCount = 0;

    private int $trackCount = 0;

    public function run(): void
    {
        $this->command->info('=== TAM May 2026 Demo Dataset Seeder ===');

        $customer = $this->ensureCustomer();
        $branch = $this->ensureBranch();

        $this->command->info("Customer: {$customer->name} (ID={$customer->id})");
        $this->command->info("Branch: {$branch->name} (ID={$branch->id})");
        $this->command->info('');

        foreach ($this->vessels as $vesselData) {
            $this->seedVessel($vesselData, $customer, $branch);
        }

        $this->command->info('');
        $this->command->info('=== Seeding Complete ===');
        $this->command->info("Shipments created/updated: {$this->shipmentCount}");
        $this->command->info("Tracks created/updated: {$this->trackCount}");
    }

    private function ensureCustomer(): Customer
    {
        return Customer::updateOrCreate(
            ['code' => 'TAM-0001'],
            [
                'name' => self::CUSTOMER_NAME,
                'email' => 'tam@toyota.astra.co.id',
                'phone' => '02153665800',
                'type' => 'company',
            ]
        );
    }

    private function ensureBranch(): Branch
    {
        return Branch::firstOrCreate(
            ['code' => self::BRANCH_CODE],
            ['name' => 'Jakarta']
        );
    }

    private function seedVessel(array $vesselData, Customer $customer, Branch $branch): void
    {
        $this->command->info("Vessel: {$vesselData['vessel']} ({$vesselData['voyage_no']})");

        foreach ($vesselData['units'] as $i => $unit) {
            $unitIndex = $i + 1;
            $code = "SHP-TAM-{$vesselData['voyage_no']}-{$unitIndex}";

            $shipment = $this->createOrUpdateShipment(
                $code,
                $customer,
                $branch,
                $vesselData,
                $unit
            );

            $this->createOrUpdateTracks($shipment, $unit);

            $this->command->info(
                "  ✓ {$code} — {$unit['model']} {$unit['color']} "
                .'| Dw='.$this->diffDays($unit['pickup'], $unit['unit_loading'])
                .' Sa='.$this->diffDays($unit['unit_loading'], $unit['vessel_arrival'])
                .' Dr='.$this->diffDays($unit['vessel_arrival'], $unit['delivered'])
                .' Total='.$this->diffDays($unit['pickup'], $unit['delivered'])
            );

            $this->shipmentCount++;
        }
    }

    private function createOrUpdateShipment(
        string $code,
        Customer $customer,
        Branch $branch,
        array $vesselData,
        array $unit
    ): Shipment {
        $deliveredAt = Carbon::parse($unit['delivered']);
        $pickupAt = Carbon::parse($unit['pickup']);

        $voyage = \App\Models\Voyage::where('voyage_no', $vesselData['voyage_no'])->first();
        $voyageId = $voyage ? $voyage->id : null;
        $polId = $voyage ? $voyage->pol_id : null;
        $podId = $voyage ? $voyage->pod_id : null;

        $existing = Shipment::where('code', $code)->first();

        if ($existing) {
            Shipment::withoutEvents(function () use ($existing, $customer, $branch, $vesselData, $unit, $deliveredAt, $voyageId, $polId, $podId) {
                $existing->update([
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'mode' => ShipmentMode::Sea->value,
                    'status' => ShipmentStatus::Delivered->value,
                    'request_type' => 'sppb_do',
                    'route_from' => self::ROUTE_FROM,
                    'route_to' => self::ROUTE_TO,
                    'route_summary' => self::ROUTE_FROM.' → '.self::ROUTE_TO,
                    'vessel_name' => $vesselData['vessel'],
                    'voyage' => $vesselData['voyage_no'],
                    'voyage_id' => $voyageId,
                    'pol_id' => $polId,
                    'pod_id' => $podId,
                    'pol' => self::POL,
                    'pod' => self::POD,
                    'etd' => Carbon::parse($unit['unit_loading'])->subDay(),
                    'eta' => Carbon::parse($unit['vessel_arrival']),
                    'delivered_at' => $deliveredAt,
                    'priority' => 'normal',
                    'service_type' => 'sea_freight',
                    'service_option' => 'fcl',
                    'cargo_type' => 'vehicle',
                    'container_size' => '40HC',
                    'container_qty' => 1,
                    'packages_total' => 1,
                    'pic_name' => 'PIC '.$unit['model'],
                    'pic_phone' => '02153665800',
                    'confirm_is_true' => true,
                ]);
            });

            return $existing;
        }

        $shipment = null;
        Shipment::withoutEvents(function () use (&$shipment, $code, $customer, $branch, $vesselData, $unit, $deliveredAt, $voyageId, $polId, $podId) {
            $shipment = Shipment::create([
                'code' => $code,
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'mode' => ShipmentMode::Sea->value,
                'status' => ShipmentStatus::Delivered->value,
                'request_type' => 'sppb_do',
                'doc_number' => 'SPPB-'.str_replace(['-', ' ', ':'], '', $unit['pickup']),
                'route_from' => self::ROUTE_FROM,
                'route_to' => self::ROUTE_TO,
                'route_summary' => self::ROUTE_FROM.' → '.self::ROUTE_TO,
                'vessel_name' => $vesselData['vessel'],
                'voyage' => $vesselData['voyage_no'],
                'voyage_id' => $voyageId,
                'pol_id' => $polId,
                'pod_id' => $podId,
                'pol' => self::POL,
                'pod' => self::POD,
                'etd' => Carbon::parse($unit['unit_loading'])->subDay(),
                'eta' => Carbon::parse($unit['vessel_arrival']),
                'delivered_at' => $deliveredAt,
                'priority' => 'normal',
                'service_type' => 'sea_freight',
                'service_option' => 'fcl',
                'cargo_type' => 'vehicle',
                'container_size' => '40HC',
                'container_qty' => 1,
                'packages_total' => 1,
                'pic_name' => 'PIC '.$unit['model'],
                'pic_phone' => '02153665800',
                'confirm_is_true' => true,
                'notes' => $unit['model'].' '.$unit['color'],
            ]);
        });

        return $shipment;
    }

    private function createOrUpdateTracks(Shipment $shipment, array $unit): void
    {
        $milestones = [
            'pickup' => $unit['pickup'],
            'unit_loading' => $unit['unit_loading'],
            'vessel_arrival' => $unit['vessel_arrival'],
            'delivered' => $unit['delivered'],
        ];

        $statusNormalizedMap = [
            'pickup' => 10,
            'unit_loading' => 60,
            'vessel_arrival' => 90,
            'delivered' => 120,
        ];

        foreach ($milestones as $status => $trackedAt) {
            $existing = ShipmentTrack::where('shipment_id', $shipment->id)
                ->where('status', $status)
                ->first();

            if ($existing) {
                DB::table('shipment_tracks')
                    ->where('id', $existing->id)
                    ->update([
                        'tracked_at' => Carbon::parse($trackedAt),
                        'status_normalized' => $statusNormalizedMap[$status],
                    ]);
            } else {
                DB::table('shipment_tracks')->insert([
                    'shipment_id' => $shipment->id,
                    'status' => $status,
                    'status_normalized' => $statusNormalizedMap[$status],
                    'tracked_at' => Carbon::parse($trackedAt),
                    'note' => TrackStatus::tryFrom($status)?->label() ?? $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->trackCount++;
        }
    }

    private function diffDays(string $from, string $to): int
    {
        return Carbon::parse($from)->startOfDay()
            ->diffInDays(Carbon::parse($to)->startOfDay());
    }
}

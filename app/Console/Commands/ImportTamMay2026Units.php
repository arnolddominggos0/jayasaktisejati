<?php

namespace App\Console\Commands;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Unit;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTamMay2026Units extends Command
{
    protected $signature = 'import:tam-may-2026-units';

    protected $description = 'Import TAM May 2026 Units';

    public function handle(): int
    {
        $csv = storage_path('imports/tam/may2026/tam_v154.csv');

        if (! file_exists($csv)) {
            $this->error("File tidak ditemukan: {$csv}");
            return self::FAILURE;
        }

        $voyage = Voyage::with('vessel')->where('voyage_no', '154')->first();

        if (! $voyage) {
            $this->error('Voyage 154 tidak ditemukan.');
            return self::FAILURE;
        }

        $handle = fopen($csv, 'r');

        fgetcsv($handle); // skip header

        $createdShipment = 0;
        $createdUnit = 0;
        $createdTrack = 0;

        DB::transaction(function () use (
            $handle,
            $voyage,
            &$createdShipment,
            &$createdUnit,
            &$createdTrack
        ) {

            while (($row = fgetcsv($handle)) !== false) {

                [
                    $no,
                    $vin,
                    $engineNo,
                    $model,
                    $destination,
                    $vesselName,
                    $pickupDate,
                    $atd,
                    $ata,
                    $deliveredAt
                ] = $row;

                $runningNo = (int) $no;

                $voyageCode = $voyage->code ?? sprintf('VOY%s', $voyage->voyage_no);

                $shipment = Shipment::updateOrCreate(
                    [
                        'code' => sprintf('%s-%03d', $voyageCode, $runningNo),
                    ],
                    [
                        'customer_id' => 1,
                        'receiver_id' => 1,

                        'branch_id' => 1,

                        'mode' => ShipmentMode::Sea->value,

                        'status' => ShipmentStatus::Delivered->value,

                        'request_type' => 'sppb_do',

                        'route_from' => 'Jakarta',
                        'route_to' => 'Manado',

                        'route_summary' => 'Jakarta → Manado',

                        'service_type' => 'sea_freight',
                        'service_option' => 'fcl',

                        'cargo_type' => 'vehicle',

                        'container_qty' => 1,
                        'packages_total' => 1,

                        'vessel_name' => $voyage->vessel->name,
                        'voyage' => $voyage->voyage_no,
                        'voyage_id' => $voyage->id,

                        'pol' => 'Tanjung Priok',
                        'pod' => 'Bitung',

                        'pol_id' => $voyage->pol_id,
                        'pod_id' => $voyage->pod_id,

                        'etd' => Carbon::parse($atd),
                        'eta' => Carbon::parse($ata),

                        // penting untuk KPI
                        'requested_at' => Carbon::parse($pickupDate),

                        'delivered_at' => Carbon::parse($deliveredAt),

                        'confirm_is_true' => true,

                        'notes' => sprintf(
                            'VIN %s / Engine %s / Model %s',
                            $vin,
                            $engineNo,
                            $model
                        ),
                    ]
                );
                
                Unit::updateOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                    ],
                    [
                        'model_no' => $model,
                        'chassis_no' => $vin,
                        'engine_no' => $engineNo,
                        'qty' => 1,
                    ]
                );

                $shipment->tracks()->delete();

                ShipmentTrack::create([
                    'shipment_id' => $shipment->id,
                    'status' => TrackStatus::Pickup->value,
                    'tracked_at' => Carbon::parse($pickupDate),
                    'note' => 'Imported from TAM V154',
                ]);

                ShipmentTrack::create([
                    'shipment_id' => $shipment->id,
                    'status' => TrackStatus::VesselDepart->value,
                    'tracked_at' => Carbon::parse($atd),
                    'note' => 'Imported from TAM V154',
                ]);

                ShipmentTrack::create([
                    'shipment_id' => $shipment->id,
                    'status' => TrackStatus::VesselArrival->value,
                    'tracked_at' => Carbon::parse($ata),
                    'note' => 'Imported from TAM V154',
                ]);

                ShipmentTrack::create([
                    'shipment_id' => $shipment->id,
                    'status' => TrackStatus::Delivered->value,
                    'tracked_at' => Carbon::parse($deliveredAt),
                    'note' => 'Imported from TAM V154',
                ]);

                $createdShipment++;
                $createdUnit++;
                $createdTrack += 4;

                $this->line(
                    sprintf(
                        '✓ %s - %s',
                        $shipment->code,
                        $vin
                    )
                );
            }
        });

        fclose($handle);

        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Shipment', $createdShipment],
                ['Unit', $createdUnit],
                ['ShipmentTrack', $createdTrack],
            ]
        );

        $this->info('Import TAM V154 selesai.');

        return self::SUCCESS;
    }
}

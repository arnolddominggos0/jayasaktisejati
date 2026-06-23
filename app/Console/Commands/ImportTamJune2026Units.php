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

class ImportTamJune2026Units extends Command
{
    protected $signature = 'import:tam-june-2026-units';

    protected $description = 'Import TAM June 2026 Units';

    public function handle(): int
    {
        $jsonFile = storage_path(
            'imports/tam/june2026/units.json'
        );

        if (! file_exists($jsonFile)) {
            $this->error(
                "File tidak ditemukan: {$jsonFile}"
            );

            return self::FAILURE;
        }

        $payload = json_decode(
            file_get_contents($jsonFile),
            true
        );

        $rows = $payload['units'] ?? [];
        
        if (! is_array($rows)) {
            $this->error('Format JSON tidak valid');

            return self::FAILURE;
        }

        $createdShipment = 0;
        $createdUnit = 0;
        $createdTrack = 0;

        DB::transaction(function () use (
            $rows,
            &$createdShipment,
            &$createdUnit,
            &$createdTrack
        ) {

            foreach ($rows as $row) {

                $vin = trim(
                    $row['chassis_no'] ?? ''
                );

                if ($vin === '') {
                    continue;
                }

                $engineNo = trim(
                    $row['engine_no'] ?? ''
                );

                $model = trim(
                    $row['model'] ?? ''
                );

                $runningNo = (int) (
                    $row['no'] ?? 0
                );

                $destination = trim(
                    $row['destination_city']
                        ?? 'Manado'
                );

                $pickupDate =
                    $row['entered_yard_at'] ?? null;

                $atd =
                    $row['atd'] ?? null;

                $voyageName =
                    $row['vessel'] ?? '';

                preg_match(
                    '/V\.(\d+)/',
                    $voyageName,
                    $matches
                );

                $voyageNo =
                    $matches[1] ?? null;

                if (! $voyageNo) {

                    $this->warn(
                        "Voyage tidak ditemukan untuk VIN {$vin}"
                    );

                    continue;
                }

                $voyage = Voyage::with('vessel')
                    ->where('voyage_no', $voyageNo)
                    ->first();

                if (! $voyage) {

                    $this->warn(
                        "Voyage {$voyageNo} tidak ada di database"
                    );

                    continue;
                }

                $voyageCode = $voyage->code ?? sprintf('JUN26V%s', $voyageNo);

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
                        'route_to' => $destination,

                        'route_summary' =>
                        'Jakarta → ' . $destination,

                        'service_type' => 'sea_freight',
                        'service_option' => 'fcl',

                        'cargo_type' => 'vehicle',

                        'container_qty' => 1,
                        'packages_total' => 1,

                        'vessel_name' => $voyage->vessel?->name,
                        'voyage' => $voyage->voyage_no,
                        'voyage_id' => $voyage->id,

                        'pol' => 'Tanjung Priok',
                        'pod' => 'Bitung',

                        'pol_id' => $voyage->pol_id,
                        'pod_id' => $voyage->pod_id,

                        'etd' => $atd
                            ? Carbon::parse($atd)
                            : null,

                        'requested_at' => $pickupDate
                            ? Carbon::parse($pickupDate)
                            : now(),

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

                if ($pickupDate) {

                    ShipmentTrack::create([
                        'shipment_id' => $shipment->id,
                        'status' => TrackStatus::Pickup->value,
                        'tracked_at' => Carbon::parse(
                            $pickupDate
                        ),
                        'note' => 'Imported TAM June 2026',
                    ]);

                    $createdTrack++;
                }

                if ($atd) {

                    ShipmentTrack::create([
                        'shipment_id' => $shipment->id,
                        'status' => TrackStatus::VesselDepart->value,
                        'tracked_at' => Carbon::parse(
                            $atd
                        ),
                        'note' => 'Imported TAM June 2026',
                    ]);

                    $createdTrack++;
                }

                $createdShipment++;
                $createdUnit++;

                $this->line(
                    sprintf(
                        '✓ %s - %s',
                        $shipment->code,
                        $vin
                    )
                );
            }
        });

        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Shipment', $createdShipment],
                ['Unit', $createdUnit],
                ['ShipmentTrack', $createdTrack],
            ]
        );

        $this->info(
            'Import TAM June 2026 Units selesai.'
        );

        return self::SUCCESS;
    }
}

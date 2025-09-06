<?php

namespace Database\Factories;

use App\Enums\CargoType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $from = $this->faker->randomElement(['Jakarta','Tangerang','Bekasi','Depok','Bogor']);
        $to   = $this->faker->randomElement(['Manado','Makassar','Surabaya','Semarang','Medan']);

        // Pakai enum (sesuai cast di model)
        $mode       = $this->faker->randomElement([ShipmentMode::Sea, ShipmentMode::Land]);
        $cargoType  = $this->faker->randomElement([CargoType::Vehicle, CargoType::General]);

        $base = [
            'code'                   => null,  // akan di-generate di model
            'customer_id'            => null,  // biarkan null (FK optional)
            'origin_office_id'       => null,
            'destination_office_id'  => null,
            'route_from'             => $from,
            'route_to'               => $to,
            'mode'                   => $mode,
            'cargo_type'             => $cargoType,

            'status' => $this->faker->randomElement([
                ShipmentStatus::Draft,
                ShipmentStatus::Pending,
                ShipmentStatus::Pickup,
                ShipmentStatus::Transit,
                ShipmentStatus::Delivered,
                ShipmentStatus::Hold,
            ]),

            'notes' => $this->faker->optional()->sentence(),
        ];

        if ($mode === ShipmentMode::Sea) {
            // SEA: tentukan opsi FCL/LCL. Model akan set service_type = SeaFreight saat saving()
            return $base + [
                'service_option' => $this->faker->randomElement(['fcl','lcl']),
                'vessel_name'    => $this->faker->company.' Lines',
                'voyage'         => 'VY'.$this->faker->numberBetween(100, 999),
                'pol'            => $this->faker->randomElement(['Tj. Priok','Tj. Perak']),
                'pod'            => $this->faker->randomElement(['Bitung','Belawan']),
                'etd'            => $this->faker->dateTimeBetween('now', '+3 days'),
                'eta'            => $this->faker->dateTimeBetween('+4 days', '+14 days'),
            ];
        }

        // LAND: pilih vehicle_type; service_option akan otomatis diisi di model (saving) sesuai vehicle_type,
        // tapi kita set juga di sini biar konsisten saat dilihat sebelum saving.
        $vehicleType = $this->faker->randomElement(['car_carrier','towing','truck']);
        $serviceOpt  = match ($vehicleType) {
            'car_carrier' => 'car_carrier',
            'towing'      => 'towing',
            default       => 'truck',
        };

        return $base + [
            'vehicle_type'  => $vehicleType,
            'service_option'=> $serviceOpt,
            'vehicle_plate' => strtoupper($this->faker->bothify('B #### ??')), // <= 20 char
            'pickup_date'   => $this->faker->dateTimeBetween('now', '+5 days'),
            'driver_name'   => $this->faker->name,
            'driver_phone'  => $this->faker->numerify('08##########'), // aman <= 20
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\{CargoType, RequestType, ShipmentMode, ShipmentStatus, ServiceType};
use App\Models\{Shipment, Customer, Office};
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $officeIds = Office::query()->inRandomOrder()->limit(2)->pluck('id')->all();
        if (count($officeIds) < 2) {
            $officeIds = [Office::query()->inRandomOrder()->value('id'), Office::query()->inRandomOrder()->value('id')];
            $officeIds = array_values(array_filter($officeIds));
        }
        $originId = $officeIds[0] ?? null;
        $destId   = $officeIds[1] ?? $officeIds[0];

        if ($originId && $destId && $originId === $destId) {
            $alt = Office::where('id', '!=', $originId)->inRandomOrder()->value('id');
            if ($alt) $destId = $alt;
        }

        $customerId = Customer::inRandomOrder()->value('id') ?? Customer::first()?->id;

        $mode         = fake()->randomElement([ShipmentMode::Sea, ShipmentMode::Land]);
        $cargoType    = fake()->randomElement([CargoType::Vehicle, CargoType::General]);
        $requestType  = fake()->randomElement([RequestType::SPPB_DO, RequestType::WA_TELP, RequestType::WALK_IN]);
        $status       = fake()->randomElement([
            ShipmentStatus::Draft,
            ShipmentStatus::Pending,
            ShipmentStatus::Pickup,
            ShipmentStatus::Transit,
            ShipmentStatus::Delivered,
            ShipmentStatus::Hold,
        ]);

        $originCity = Office::find($originId)?->city ?? fake()->city();
        $destCity   = Office::find($destId)?->city ?? fake()->city();

        $routeSummary = $originCity . ' → ' . $destCity;

        $base = [
            'code'                   => null,
            'customer_id'            => $customerId,
            'receiver_id'            => Customer::inRandomOrder()->value('id') ?? $customerId,
            'origin_office_id'       => $originId,
            'destination_office_id'  => $destId,

            'route_from'             => $originCity,
            'route_to'               => $destCity,
            'route_summary'          => $routeSummary,

            'mode'                   => $mode->value,
            'cargo_type'             => $cargoType->value,
            'status'                 => $status->value,
            'request_type'           => $requestType->value,

            'pic_name'               => fake()->name(),
            'pic_phone'              => '08' . fake()->numerify('##########'),
            'doc_number'             => fake()->boolean(70) ? strtoupper(fake()->bothify('DOC-########')) : null,
            'priority'               => fake()->randomElement(['normal', 'urgent']),
            'requested_at'           => fake()->dateTimeBetween('-7 days', 'now'),

            'notes' => fake()->optional()->sentence(),
            'confirm_is_true' => fake()->boolean(80),
        ];

        if ($mode === ShipmentMode::Sea) {
            $serviceOption = fake()->randomElement(['fcl', 'lcl']);
            $ports = [
                'Jakarta'   => 'Tj. Priok',
                'Surabaya'  => 'Tj. Perak',
                'Manado'    => 'Bitung',
                'Makassar'  => 'Soekarno-Hatta',
            ];
            $pol = $ports[$originCity] ?? 'Pelabuhan Asal';
            $pod = $ports[$destCity]   ?? 'Pelabuhan Tujuan';

            // Jika LCL dan General, isi total CBM/berat supaya kelihatan di tabel
            $packages = null; $cbm = null; $weight = null;
            if ($serviceOption === 'lcl' && $cargoType === CargoType::General) {
                $packages = fake()->numberBetween(3, 30);
                // CBM total wajar 0.5 - 12.0
                $cbm = round(fake()->randomFloat(4, 0.5, 12.0), 3, PHP_ROUND_HALF_UP);
                // berat total wajar 50 - 800 kg
                $weight = round(fake()->randomFloat(4, 50, 800), 2, PHP_ROUND_HALF_UP);
            }

            return $base + [
                'service_type'   => ServiceType::SeaFreight->value,
                'service_option' => $serviceOption,

                // FCL opsional
                'container_size' => $serviceOption === 'fcl' ? fake()->randomElement(['20', '40', '40HC']) : null,
                'container_qty'  => $serviceOption === 'fcl' ? fake()->numberBetween(1, 4) : null,

                // LCL totals (jika ada)
                'packages_total' => $packages,
                'cbm_total'      => $cbm,
                'weight_total'   => $weight,

                'vessel_name'    => fake()->company() . ' Lines',
                'voyage'         => 'VY' . fake()->numberBetween(100, 999),
                'pol'            => $pol,
                'pod'            => $pod,
                'etd'            => fake()->dateTimeBetween('now', '+5 days'),
                'eta'            => fake()->dateTimeBetween('+6 days', '+16 days'),

                // Darat null
                'vehicle_type'   => null,
                'vehicle_plate'  => null,
                'pickup_date'    => null,
                'driver_name'    => null,
                'driver_phone'   => null,
            ];
        }

        // Mode Darat
        $vehicleType = fake()->randomElement(['car_carrier', 'towing', 'truck']);
        $serviceOpt  = match ($vehicleType) {
            'car_carrier' => 'car_carrier',
            'towing'      => 'towing',
            default       => 'truck',
        };
        $serviceType = in_array($vehicleType, ['car_carrier', 'towing'])
            ? ServiceType::CarCarrier->value
            : ServiceType::LandTrucking->value;

        return $base + [
            'service_type'   => $serviceType,
            'service_option' => $serviceOpt,

            'vehicle_type'   => $vehicleType,
            'vehicle_plate'  => strtoupper(fake()->bothify('B #### ??')),
            'pickup_date'    => fake()->dateTimeBetween('now', '+5 days'),
            'driver_name'    => fake()->name(),
            'driver_phone'   => '08' . fake()->numerify('##########'),

            // Laut null
            'vessel_name'    => null,
            'voyage'         => null,
            'pol'            => null,
            'pod'            => null,
            'etd'            => null,
            'eta'            => null,

            // FCL/LCL totals null
            'container_size' => null,
            'container_qty'  => null,
            'packages_total' => null,
            'cbm_total'      => null,
            'weight_total'   => null,
        ];
    }
}

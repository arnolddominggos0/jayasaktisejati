<?php

namespace Database\Factories;

use App\Enums\{CargoType, DeliveryScope, RequestType, ShipmentMode, ShipmentStatus, ServiceType};
use App\Models\{Shipment, Customer, City};
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $cityIds = City::query()->inRandomOrder()->limit(2)->pluck('id')->all();
        if (count($cityIds) < 2) {
            $created = City::factory()->count(2 - count($cityIds))->create()->pluck('id')->all();
            $cityIds = array_merge($cityIds, $created);
        }
        $originCityId = $cityIds[0];
        $destCityId   = $cityIds[1] ?? $cityIds[0];

        if ($originCityId === $destCityId) {
            $alt = City::whereKeyNot($originCityId)->inRandomOrder()->value('id');
            if ($alt) $destCityId = $alt;
        }
        $deliveryScope = fake()->randomElement([
            DeliveryScope::PortToPort,
            DeliveryScope::DoorToPort,
            DeliveryScope::PortToDoor,
            DeliveryScope::DoorToDoor,
        ]);

        $originCity = City::find($originCityId);
        $destCity   = City::find($destCityId);

        $customerId = Customer::inRandomOrder()->value('id') ?? Customer::factory()->create()->id;

        $mode        = fake()->randomElement([ShipmentMode::Sea, ShipmentMode::Land]);
        $cargoType   = fake()->randomElement([CargoType::Vehicle, CargoType::General]);
        $requestType = fake()->randomElement([RequestType::SPPB_DO, RequestType::WA_TELP, RequestType::WALK_IN]);
        $status      = fake()->randomElement([
            ShipmentStatus::Draft,
            ShipmentStatus::Pending,
            ShipmentStatus::Pickup,
            ShipmentStatus::Transit,
            ShipmentStatus::Delivered,
            ShipmentStatus::Hold,
        ]);

        $docNumber = match ($requestType) {
            RequestType::SPPB_DO => strtoupper(fake()->bothify('SPPB-########')),
            RequestType::WALK_IN => 'AUTO-' . now()->format('Ymd-His'),
            default              => fake()->boolean(50) ? strtoupper(fake()->bothify('DOC-########')) : null,
        };

        $routeSummary = ($originCity->name ?? '-') . ' → ' . ($destCity->name ?? '-');

        $base = [
            'code'                    => null, 
            'customer_id'             => $customerId,
            'receiver_id'             => Customer::inRandomOrder()->value('id') ?? $customerId,

            'origin_city_id'          => $originCityId,
            'destination_city_id'     => $destCityId,

            'route_from'              => $originCity->name ?? null,
            'route_to'                => $destCity->name ?? null,
            'route_summary'           => $routeSummary,

            'mode'                    => $mode->value,
            'cargo_type'              => $cargoType->value,
            'status'                  => $status->value,
            'request_type'            => $requestType->value,

            'pic_name'                => fake()->name(),
            'pic_phone'               => '08' . fake()->numerify('##########'),
            'doc_number'              => $docNumber,
            'priority'                => fake()->randomElement(['normal', 'urgent']),
            'requested_at'            => fake()->dateTimeBetween('-7 days', 'now'),

            'notes'                   => fake()->optional()->sentence(),
            'confirm_is_true'         => fake()->boolean(85),
        ];

        if ($mode === ShipmentMode::Sea) {
            $serviceOption = fake()->randomElement(['fcl', 'lcl']);

            $ports = [
                'Jakarta'  => 'Tj. Priok',
                'Surabaya' => 'Tj. Perak',
                'Manado'   => 'Bitung',
                'Makassar' => 'Soekarno-Hatta',
                'Ambon'    => 'Yos Sudarso',
                'Ternate'  => 'Ahmad Yani',
                'Bitung'   => 'Bitung',
            ];

            $oName = $originCity->name ?? '';
            $dName = $destCity->name ?? '';
            $pol   = $ports[$oName] ?? 'Pelabuhan Asal';
            $pod   = $ports[$dName] ?? 'Pelabuhan Tujuan';

            $packages = null; $cbm = null; $weight = null;
            if ($serviceOption === 'lcl' && $cargoType === CargoType::General) {
                $packages = fake()->numberBetween(3, 30);
                $cbm      = round(fake()->randomFloat(4, 0.5, 12.0), 3, PHP_ROUND_HALF_UP);
                $weight   = round(fake()->randomFloat(4, 50, 800), 2, PHP_ROUND_HALF_UP);
            }

            return $base + [
                'service_type'   => ServiceType::SeaFreight->value,
                'service_option' => $serviceOption,

                // FCL opsional
                'container_size' => $serviceOption === 'fcl' ? fake()->randomElement(['20', '40', '40HC', '45HC']) : null,
                'container_qty'  => $serviceOption === 'fcl' ? fake()->numberBetween(1, 4) : null,

                // LCL totals (kalau ada)
                'packages_total' => $packages,
                'cbm_total'      => $cbm,
                'weight_total'   => $weight,
                'delivery_scope' => $deliveryScope,

                // Data kapal
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

            'vessel_name'    => null,
            'voyage'         => null,
            'pol'            => null,
            'pod'            => null,
            'etd'            => null,
            'eta'            => null,

            'container_size' => null,
            'container_qty'  => null,
            'packages_total' => null,
            'cbm_total'      => null,
            'weight_total'   => null,
        ];
    }
}

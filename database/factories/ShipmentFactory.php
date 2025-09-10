<?php

namespace Database\Factories;

use App\Enums\CargoType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\ServiceType;
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

        $mode       = fake()->randomElement([ShipmentMode::Sea, ShipmentMode::Land]);
        $cargoType  = fake()->randomElement([CargoType::Vehicle, CargoType::General]);
        $status     = fake()->randomElement([
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
            'origin_office_id'       => $originId,
            'destination_office_id'  => $destId,

            'route_from'             => $originCity,
            'route_to'               => $destCity,
            'route_summary'          => $routeSummary,

            'mode'                   => $mode->value,
            'cargo_type'             => $cargoType->value,
            'status'                 => $status->value,

            'pic_name'               => fake()->name(),
            'pic_phone'              => '08' . fake()->numerify('##########'),
            'request_type'           => fake()->randomElement(['sppb/do', 'whatsapp/telp', 'walk-in']),
            'doc_number'             => fake()->boolean(70) ? strtoupper(fake()->bothify('DOC-########')) : null,
            'priority'               => fake()->randomElement(['normal', 'urgent']),
            'requested_at'           => fake()->dateTimeBetween('-7 days', 'now'),

            'notes' => fake()->optional()->sentence(),
            'confirm_is_true' => fake()->boolean(80),
        ];

        if ($mode === ShipmentMode::Sea) {
            $ports = [
                'Jakarta'   => 'Tj. Priok',
                'Surabaya'  => 'Tj. Perak',
                'Manado'    => 'Bitung',
                'Makassar'  => 'Soekarno-Hatta',
            ];

            $pol = $ports[$originCity] ?? 'Pelabuhan Asal';
            $pod = $ports[$destCity]   ?? 'Pelabuhan Tujuan';

            return $base + [
                'service_type'   => ServiceType::SeaFreight->value,
                'service_option' => fake()->randomElement(['fcl', 'lcl']),
                'vessel_name'    => fake()->company() . ' Lines',
                'voyage'         => 'VY' . fake()->numberBetween(100, 999),
                'pol'            => $pol,
                'pod'            => $pod,
                'etd'            => fake()->dateTimeBetween('now', '+5 days'),
                'eta'            => fake()->dateTimeBetween('+6 days', '+16 days'),

                'vehicle_type'   => null,
                'vehicle_plate'  => null,
                'pickup_date'    => null,
                'driver_name'    => null,
                'driver_phone'   => null,
            ];
        }

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
        ];
    }
    
}

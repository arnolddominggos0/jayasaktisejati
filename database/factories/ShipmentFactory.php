<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $from = $this->faker->randomElement(['Jakarta', 'Tangerang', 'Bekasi', 'Depok', 'Bogor']);
        $to   = $this->faker->randomElement(['Manado', 'Makassar', 'Surabaya', 'Semarang', 'Medan']);

        return [
            'code'               => null, 
            'customer_id'        => null,
            'origin_office_id'   => null,
            'destination_office_id'=> null,
            'route_from'         => $from,
            'route_to'           => $to,
            'service_type'       => $this->faker->randomElement(['FCL','LCL','Darat','CarCarrier']),
            'status'             => $this->faker->randomElement([
                ShipmentStatus::Draft,
                ShipmentStatus::Pending,
                ShipmentStatus::Pickup,
                ShipmentStatus::Transit,
                ShipmentStatus::Delivered,
                ShipmentStatus::Hold,
            ]),
            'eta'                => $this->faker->optional()->dateTimeBetween('+2 days', '+14 days'),
            'etd'                => $this->faker->optional()->dateTimeBetween('now', '+3 days'),
            'notes'              => $this->faker->optional()->sentence(),
        ];
    }
}

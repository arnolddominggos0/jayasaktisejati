<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentTrackFactory extends Factory
{
    public function definition(): array
    {
        // Correct status_normalized mapping matching the database constraint
        // See migration: 2025_10_20_113417_add_status_normalized_to_shipment_tracks.php
        $statusMap = [
            'pickup'               => 10,
            'handover'             => 20,
            'stuffing'             => 30,
            'delivery_to_port'     => 40,
            'stacking'             => 50,
            'unit_loading'         => 60,
            'onship'               => 70,
            'vessel_depart'        => 80,
            'vessel_arrival'       => 90,
            'unloading'            => 100,
            'handover_trucking'    => 105,
            'delivery_to_customer' => 110,
            'delivered'            => 120,
            'hold'                 => 900,
            'cancelled'            => 999,
        ];

        $status = fake()->randomElement(array_keys($statusMap));

        return [
            'shipment_id' => null,
            'status' => $status,
            'status_normalized' => $statusMap[$status],
            'tracked_at' => now(),
        ];
    }
}

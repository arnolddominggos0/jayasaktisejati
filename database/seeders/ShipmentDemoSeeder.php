<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shipment;
use App\Enums\ShipmentStatus;

class ShipmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $shipments = Shipment::factory()
            ->count(30)
            ->create([
                'status' => ShipmentStatus::Draft->value, 
            ]);

        foreach ($shipments as $s) {
            $s->update([
                'route_summary' => "{$s->route_from} → Hub A → {$s->route_to}",
            ]);

            $s->update([
                'status' => ShipmentStatus::Pickup->value,
            ]);

            if (random_int(0, 1)) {
                $s->update([
                    'status' => ShipmentStatus::Delivered->value,
                ]);
            }
        }
    }
}

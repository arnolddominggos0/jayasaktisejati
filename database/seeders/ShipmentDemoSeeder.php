<?php

namespace Database\Seeders;

use App\Models\Shipment;
use Illuminate\Database\Seeder;

class ShipmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        Shipment::factory()->count(20)->create();
    }
}

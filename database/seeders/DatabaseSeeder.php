<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SlaRuleSeeder::class);
        $this->call(InitialSetupSeeder::class);
        $this->call(VoyageDummySeeder::class);
        $this->call((VesselPlanDummySeeder::class));
        $this->call(TAMShipmentSeeder::class);
        $this->call(SppbUrgentTernateSeeder::class);
    }
}

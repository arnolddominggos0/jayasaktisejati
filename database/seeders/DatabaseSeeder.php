<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndUsersSeeder::class);
        $this->call(InitialSetupSeeder::class);
        // $this->call(DummyOperationalSeeder::class);
        // $this->call(FleetMPSeeder::class);
        // $this->call(FieldCoordinatorSeeder::class);
        $this->call(BackfillShipmentTracksForKpiSeeder::class);
        $this->call(ShippingLineSeed::class);
        $this->call(ShippingScheduleSeeder::class);
    }
}

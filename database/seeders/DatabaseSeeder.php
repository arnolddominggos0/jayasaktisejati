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
<<<<<<< HEAD
        // $this->call(TestShipmentKpiWithTracksSeeder::class);
=======
        $this->call(BackfillShipmentTracksForKpiSeeder::class);
        $this->call(ShippingLineSeed::class);
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
        $this->call(ShippingScheduleSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InitialSetupSeeder::class, 
            ImportTamJanuary2026Voyages::class,
            ImportTamJune2026Voyages::class,
            ImportTamJune2026Units::class,
            ContainerReadinessSessionSeeder::class,
            JslWebsiteSeeder::class,
            ]);
    }
}

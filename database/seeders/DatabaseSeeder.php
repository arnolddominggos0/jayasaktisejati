<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InitialSetupSeeder::class,
            // May2026VesselPlanDraftSeeder::class,
            May2026Seeder::class,
            TamMay2026Seeder::class,
        ]);
    }
}

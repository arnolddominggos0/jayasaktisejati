<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InitialSetupSeeder::class,
            // May 2026 canonical operational seeding
            May2026Seeder::class,
        ]);
    }
}
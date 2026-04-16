<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndUsersSeeder::class);
        $this->call(SlaRuleSeeder::class);
        $this->call(InitialSetupSeeder::class);
        $this->call(ProductionSeeder::class);
        $this->call(FCApril2026Seeder::class);
        $this->call(FCProductionE2ESeeder::class);
    }
}

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
        $this->call(VoyageSeeder::class);
        $this->call(FCApril2026Seeder::class);
    }
}

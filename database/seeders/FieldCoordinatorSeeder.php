<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;

class FieldCoordinatorSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = Branch::firstOrCreate(['name' => 'Jakarta']);

        $fc = User::firstOrCreate(
            ['email' => 'koordinatorjkt@jss.local'],
            [
                'name' => 'Koordinator Jakarta',
                'password' => bcrypt('password123'),
                'branch_id' => $jakarta->id,
            ]
        );
        $fc->assignRole('field_coordinator');
    }
}

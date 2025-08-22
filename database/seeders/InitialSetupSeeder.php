<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Branch;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        foreach (['super_admin', 'office_admin', 'field_coordinator', 'customer'] as $r) {
            Role::findOrCreate($r, 'web');
        }


        // Branch awal
        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'JKT']);


        // Super admin
        if (!User::where('email', 'admin@jss.local')->exists()) {
            $admin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@jss.local',
                'password' => Hash::make('Admin#12345'),
                'branch_id' => $jkt->id,
            ]);
            $admin->syncRoles(['super_admin']);
        }
    }
}

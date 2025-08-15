<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Office;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        $jkt = Office::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdo = Office::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        foreach (['super-admin','admin-office','koordinator-lapangan','customer'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $sa = User::firstOrCreate(
            ['email' => 'superadmin@local.test'],
            [
                'name'      => 'Super Admin',
                'username'  => 'super-admin',
                'password'  => Hash::make('password'),
                'office_id' => $jkt->id,
            ]
        );
        $sa->assignRole('super-admin');
    }
}

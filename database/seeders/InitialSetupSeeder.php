<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Role;

use App\Models\User;
use App\Models\Depot;
use App\Models\Branch;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'fc']);
        Role::firstOrCreate(['name' => 'koordinator']);

        $jkt = Branch::updateOrCreate(
            ['code' => 'JKT'],
            [
                'name' => 'Jakarta',
            ]
        );

        $mdo = Branch::updateOrCreate(
            ['code' => 'MDO'],
            [
                'name' => 'Manado',
            ]
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@jss.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'branch_id' => null,
            ]
        );

        $superAdmin->assignRole('super_admin');

        $fcJkt = User::updateOrCreate(
            ['email' => 'koor.jkt@jss.local'],
            [
                'name' => 'Koordinator Jakarta',
                'password' => Hash::make('password'),
                'branch_id' => $jkt->id,
            ]
        );

        $fcJkt->assignRole('koordinator');

        $fcMdo = User::updateOrCreate(
            ['email' => 'koor.mdo@jss.local'],
            [
                'name' => 'Koordinator Manado',
                'password' => Hash::make('password'),
                'branch_id' => $mdo->id,
            ]
        );

        $fcMdo->assignRole('koordinator');

        $fcPriok = User::updateOrCreate(
            ['email' => 'fc.jkt@jss.local'],
            [
                'name' => 'FC Tanjung Priok',
                'password' => Hash::make('password'),
                'branch_id' => $jkt->id,
            ]
        );

        $fcPriok->assignRole('fc');

        Depot::updateOrCreate(
            ['code' => 'DPTJKT'],
            [
                'name' => 'Depo Tanjung Priok',
                'branch_id' => $jkt->id,
                'coordinator_user_id' => $fcPriok->id,
            ]
        );

        Depot::updateOrCreate(
            ['code' => 'DPDJKT'],
            [
                'name' => 'Depo PDI Jakarta',
                'branch_id' => $jkt->id,
                'coordinator_user_id' => $fcJkt->id,
            ]
        );

        Depot::updateOrCreate(
            ['code' => 'DPBTG'],
            [
                'name' => 'Depo Bitung',
                'branch_id' => $mdo->id,
                'coordinator_user_id' => $fcMdo->id,
            ]
        );

        Depot::updateOrCreate(
            ['code' => 'DPBTGMDO'],
            [
                'name' => 'Depo Bitung Manado',
                'branch_id' => $mdo->id,
                'coordinator_user_id' => null,
            ]
        );
    }
}
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
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'fc']);

        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@jss.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'branch_id' => null,
            ]
        );

        $superAdmin->syncRoles(['super_admin']);

        /*
        |--------------------------------------------------------------------------
        | FC Jakarta
        |--------------------------------------------------------------------------
        */

        $fcJakarta = User::updateOrCreate(
            ['email' => 'fc.jkt@jss.local'],
            [
                'name' => 'FC Jakarta',
                'password' => Hash::make('password'),
                'branch_id' => $jkt->id,
            ]
        );

        $fcJakarta->syncRoles(['fc']);

        /*
        |--------------------------------------------------------------------------
        | FC Manado
        |--------------------------------------------------------------------------
        */

        $fcManado = User::updateOrCreate(
            ['email' => 'fc.mdo@jss.local'],
            [
                'name' => 'FC Manado',
                'password' => Hash::make('password'),
                'branch_id' => $mdo->id,
            ]
        );

        $fcManado->syncRoles(['fc']);

        /*
        |--------------------------------------------------------------------------
        | Depots
        |--------------------------------------------------------------------------
        */

        Depot::updateOrCreate(
            ['code' => 'DPTJKT'],
            [
                'name' => 'Depo Tanjung Priok',
                'branch_id' => $jkt->id,
                'coordinator_user_id' => null,
            ]
        );

        Depot::updateOrCreate(
            ['code' => 'DPDJKT'],
            [
                'name' => 'Depo PDI Jakarta',
                'branch_id' => $jkt->id,
                'coordinator_user_id' => null,
            ]
        );

        Depot::updateOrCreate(
            ['code' => 'DPBTG'],
            [
                'name' => 'Depo Bitung',
                'branch_id' => $mdo->id,
                'coordinator_user_id' => null,
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

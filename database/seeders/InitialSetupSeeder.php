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
        Role::firstOrCreate(['name' => 'field_coordinator']);

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
            ['email' => 'koor.jkt@jss.local'],
            [
                'name' => 'FC Jakarta',
                'password' => Hash::make('password'),
                'branch_id' => $jkt->id,
            ]
        );

        $fcJakarta->syncRoles(['field_coordinator']);

        /*
        |--------------------------------------------------------------------------
        | FC Manado
        |--------------------------------------------------------------------------
        */

        $fcManado = User::updateOrCreate(
            ['email' => 'koor.mdo@jss.local'],
            [
                'name' => 'FC Manado',
                'password' => Hash::make('password'),
                'branch_id' => $mdo->id,
            ]
        );

        $fcManado->syncRoles(['field_coordinator']);

        /*
        |--------------------------------------------------------------------------
        | Depots
        |--------------------------------------------------------------------------
        */

        Depot::updateOrCreate(
            ['code' => 'DEPOPDIJKT'],
            [
                'name' => 'Depo PDI Jakarta',
                'branch_id' => $jkt->id,
                'coordinator_user_id' => null,
            ]
        );

        Depot::updateOrCreate(
            ['code' => 'DEPOBTG'],
            [
                'name' => 'Depo Bitung',
                'branch_id' => $mdo->id,
                'coordinator_user_id' => null,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Depot;
use App\Models\Pool;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FieldCoordinatorSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('field_coordinator', 'web');

        $jakarta = Branch::firstOrCreate(['name' => 'Jakarta']);
        $surabaya = Branch::firstOrCreate(['name' => 'Surabaya']);

        $depotDaratJkt = Depot::firstOrCreate(
            ['branch_id' => $jakarta->id, 'name' => 'JKT Darat'],
            ['mode' => 'land', 'is_active' => true]
        );

        $depotLautJkt = Depot::firstOrCreate(
            ['branch_id' => $jakarta->id, 'name' => 'JKT Laut'],
            ['mode' => 'sea', 'is_active' => true]
        );

        $poolDaratJkt = Pool::firstOrCreate(
            ['branch_id' => $jakarta->id, 'name' => 'JKT Pool Darat'],
            ['mode' => 'land', 'is_active' => true]
        );

        $depotLautSby = Depot::firstOrCreate(
            ['branch_id' => $surabaya->id, 'name' => 'SBY Laut'],
            ['mode' => 'sea', 'is_active' => true]
        );

        $fcDepotDarat = User::firstOrCreate(
            ['email' => 'fc.depot.darat@jss.local'],
            [
                'name'      => 'FC Depot Darat Jakarta',
                'password'  => Hash::make('password123'),
                'branch_id' => $jakarta->id,
                'depot_id'  => $depotDaratJkt->id,
                'pool_id'   => null,
            ]
        );
        $fcDepotDarat->assignRole('field_coordinator');

        $fcDepotLaut = User::firstOrCreate(
            ['email' => 'fc.depot.laut@jss.local'],
            [
                'name'      => 'FC Depot Laut Jakarta',
                'password'  => Hash::make('password123'),
                'branch_id' => $jakarta->id,
                'depot_id'  => $depotLautJkt->id,
                'pool_id'   => null,
            ]
        );
        $fcDepotLaut->assignRole('field_coordinator');

        $fcPoolDarat = User::firstOrCreate(
            ['email' => 'fc.pool.darat@jss.local'],
            [
                'name'      => 'FC Pool Darat Jakarta',
                'password'  => Hash::make('password123'),
                'branch_id' => $jakarta->id,
                'depot_id'  => null,
                'pool_id'   => $poolDaratJkt->id,
            ]
        );
        $fcPoolDarat->assignRole('field_coordinator');

        $fcDepotLautSby = User::firstOrCreate(
            ['email' => 'fc.depot.laut.sby@jss.local'],
            [
                'name'      => 'FC Depot Laut Surabaya',
                'password'  => Hash::make('password123'),
                'branch_id' => $surabaya->id,
                'depot_id'  => $depotLautSby->id,
                'pool_id'   => null,
            ]
        );
        $fcDepotLautSby->assignRole('field_coordinator');
    }
}

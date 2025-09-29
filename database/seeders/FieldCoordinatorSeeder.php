<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Depot;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FieldCoordinatorSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role ada
        Role::findOrCreate('field_coordinator', 'web');

        $jakarta = Branch::firstOrCreate(['name' => 'Jakarta']);

        // Buat dua depot contoh dengan mode berbeda
        $depotDarat = Depot::firstOrCreate([
            'branch_id' => $jakarta->id,
            'name'      => 'JKT Darat',
        ], [
            'mode'      => 'land', // asumsi kolom mode: 'land' | 'sea'
            'is_active' => true,
        ]);

        $depotLaut = Depot::firstOrCreate([
            'branch_id' => $jakarta->id,
            'name'      => 'JKT Laut',
        ], [
            'mode'      => 'sea',
            'is_active' => true,
        ]);

        // FC Darat
        $fcDarat = User::firstOrCreate(
            ['email' => 'fc.darat@jss.local'],
            [
                'name'      => 'FC Jakarta Darat',
                'password'  => Hash::make('password123'),
                'branch_id' => $jakarta->id,
                'depot_id'  => $depotDarat->id,
            ]
        );
        $fcDarat->assignRole('field_coordinator');

        // FC Laut
        $fcLaut = User::firstOrCreate(
            ['email' => 'fc.laut@jss.local'],
            [
                'name'      => 'FC Jakarta Laut',
                'password'  => Hash::make('password123'),
                'branch_id' => $jakarta->id,
                'depot_id'  => $depotLaut->id,
            ]
        );
        $fcLaut->assignRole('field_coordinator');
    }
}

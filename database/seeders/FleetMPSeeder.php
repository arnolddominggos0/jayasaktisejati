<?php

namespace Database\Seeders;

use App\Enums\ArmadaStatus;
use App\Enums\ArmadaType;
use App\Enums\MPDomain;
use App\Models\Armada;
use App\Models\Branch;
use App\Models\Depot;
use App\Models\Manpower;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FleetMpSeeder extends Seeder
{
    public function run(): void
    {
        // Role koordinator lapangan (guard web)
        Role::findOrCreate('field_coordinator', 'web');

        // Cabang
        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta']);
        $mdo = Branch::firstOrCreate(['code' => 'MDO'], ['name' => 'Manado']);

        // User koordinator per depot
        $coordJkt = User::firstOrCreate(['email' => 'coord.tpr@jss.local'], [
            'name'      => 'Koordinator Tj. Priok',
            'password'  => Hash::make('Coord#12345'),
            // hapus baris berikut jika kolom branch_id tidak ada di users
            'branch_id' => $jkt->id,
        ]);
        $coordJkt->syncRoles(['field_coordinator']);

        $coordMdo = User::firstOrCreate(['email' => 'coord.bit@jss.local'], [
            'name'      => 'Koordinator Bitung',
            'password'  => Hash::make('Coord#12345'),
            // hapus baris berikut jika kolom branch_id tidak ada di users
            'branch_id' => $mdo->id,
        ]);
        $coordMdo->syncRoles(['field_coordinator']);

        // Depots (mode laut)
        $tpr = Depot::firstOrCreate(['code' => 'DEP-TPR'], [
            'name'                 => 'Depot Tanjung Priok',
            'mode'                 => 'sea_freight',
            'branch_id'            => $jkt->id,
            'coordinator_user_id'  => $coordJkt->id,
        ]);

        $bit = Depot::firstOrCreate(['code' => 'DEP-BIT'], [
            'name'                 => 'Depot Bitung',
            'mode'                 => 'sea_freight',
            'branch_id'            => $mdo->id,
            'coordinator_user_id'  => $coordMdo->id,
        ]);

        // Armada sampel
        Armada::firstOrCreate(['code' => 'ARM-TRK-001'], [
            'type'      => ArmadaType::Truck->value,
            'plate_number' => 'B 1234 JSS',
            'capacity'  => 10,
            'status'    => ArmadaStatus::Available->value,
            'branch_id' => $jkt->id,
            'depot_id'  => $tpr->id,
        ]);

        Armada::firstOrCreate(['code' => 'ARM-CCTW-001'], [
            'type'      => ArmadaType::CcTw->value,
            'plate_number' => 'B 8899 JSS',
            'capacity'  => 5,
            'status'    => ArmadaStatus::Standby->value,
            'branch_id' => $jkt->id,
            'depot_id'  => $tpr->id,
        ]);

        // MP sample per depot (domain laut) — TANPA kolom phone
        Manpower::firstOrCreate(
            ['name' => 'Budi Stuffing', 'depot_id' => $tpr->id],
            [
                'domain'     => MPDomain::SeaFreight->value,
                'skills'     => ['stuffing','checker'],   
                'branch_id'  => $jkt->id,
                'active'     => true,
            ]
        );

        Manpower::firstOrCreate(
            ['name' => 'Rudi Forklift', 'depot_id' => $bit->id],
            [
                'domain'     => MPDomain::SeaFreight->value,
                'skills'     => ['forklift','loading'],
                'certs'      => ['SIO Forklift'],        
                'branch_id'  => $mdo->id,
                'active'     => true,
            ]
        );
    }
}

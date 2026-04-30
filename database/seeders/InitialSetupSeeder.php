<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Depot;
use App\Models\Branch;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        $jkt = Branch::where('code', 'JKT')->firstOrFail();
        $mdo = Branch::where('code', 'MDO')->firstOrFail();

        $fcJkt = User::where('email', 'koor.jkt@jss.local')->firstOrFail();
        $fcMdo = User::where('email', 'koor.mdo@jss.local')->firstOrFail();
        $fcPriok = User::where('email', 'fc.jkt@jss.local')->firstOrFail();

        Depot::updateOrCreate(
            ['name' => 'Depo Tanjung Priok'],
            [
                'branch_id' => $jkt->id,
                'coordinator_user_id' => $fcPriok->id,
            ]
        );

        Depot::updateOrCreate(
            ['name' => 'Depo PDI Jakarta'],
            [
                'branch_id' => $jkt->id,
                'coordinator_user_id' => $fcJkt->id,
            ]
        );

        Depot::updateOrCreate(
            ['name' => 'Depo Bitung'],
            [
                'branch_id' => $mdo->id,
                'coordinator_user_id' => $fcMdo->id,
            ]
        );

        Depot::updateOrCreate(
            ['name' => 'Depo Bitung Manado'],
            [
                'branch_id' => $mdo->id,
                'coordinator_user_id' => null,
            ]
        );
    }
}

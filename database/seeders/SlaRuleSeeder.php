<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Port;

class SlaRuleSeeder extends Seeder
{
    public function run(): void
    {
        $pol = Port::where('code', 'JKT')->first();
        $pod = Port::where('code', 'BTG')->first();

        if (! $pol || ! $pod) {
            return;
        }

        DB::table('sla_rules')->updateOrInsert(
            [
                'mode'     => 'sea',
                'activity' => 'sailing',
                'pol_id'   => $pol->id,
                'pod_id'   => $pod->id,
            ],
            [
                'target_days' => 10,
                'is_active'   => true,
                'valid_from' => null,
                'valid_to'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

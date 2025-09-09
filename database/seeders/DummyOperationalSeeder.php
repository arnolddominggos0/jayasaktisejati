<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class DummyOperationalSeeder extends Seeder
{
    public function run(): void
    {
        // Jadwal kapal dummy
        if (! DB::table('fleet_schedules')->exists()) {
            $rows = [];
            $vessels = ['Meratus', 'Temas', 'SPIL', 'Tanto'];
            $ports   = ['Tj. Priok', 'Tj. Perak', 'Bitung', 'Makassar'];

            for ($i = 0; $i < 8; $i++) {
                $etd = Carbon::now()->addDays(random_int(0, 7))->setTime(16, 0);
                $eta = (clone $etd)->addDays(random_int(5, 12))->setTime(9, 0);

                $rows[] = [
                    'vessel_name' => $vessels[array_rand($vessels)] . ' Lines',
                    'voyage'      => 'VY' . random_int(100, 999),
                    'pol'         => $ports[array_rand($ports)],
                    'pod'         => $ports[array_rand($ports)],
                    'etd'         => $etd,
                    'eta'         => $eta,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
            DB::table('fleet_schedules')->insert($rows);
        }

        // Supir dummy
        if (! DB::table('drivers')->exists()) {
            $rows = [];
            for ($i = 0; $i < 10; $i++) {
                $rows[] = [
                    'name'       => 'Driver ' . Str::limit(fake()->name(), 20, ''),
                    'phone'      => '08' . str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('drivers')->insert($rows);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voyage;
use App\Models\Vessel;
use App\Models\Port;
use Carbon\Carbon;

class VoyageSeeder extends Seeder
{
    public function run(): void
    {
        $vessels = Vessel::all();
        $ports   = Port::all();

        if ($vessels->count() === 0 || $ports->count() < 2) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $vessel = $vessels->random();

            $pol = $ports->random();
            $pod = $ports->where('id', '!=', $pol->id)->random();

            $etd = Carbon::now()->addDays(rand(1, 10));
            $eta = (clone $etd)->addDays(rand(2, 6));

            Voyage::create([
                'vessel_id' => $vessel->id,
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'voyage_no' => strtoupper(substr($vessel->name, 0, 3)) . sprintf('%03d', $i),
                'etd' => $etd,
                'eta' => $eta,
                'cargo_plan' => rand(80, 250),
                'is_delayed' => false,
            ]);
        }
    }
}

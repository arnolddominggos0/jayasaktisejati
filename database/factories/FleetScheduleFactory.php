<?php

namespace Database\Factories;

use App\Models\FleetSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class FleetScheduleFactory extends Factory
{
    protected $model = FleetSchedule::class;

    public function definition(): array
    {
        $vessels = ['Meratus', 'Temas', 'Spil', 'Tanto'];
        $ports   = ['Tj. Priok', 'Tj. Perak', 'Bitung', 'Makassar'];

        $etd = now()->addDays(fake()->numberBetween(0, 7))->setTime(16, 0);
        $eta = (clone $etd)->addDays(fake()->numberBetween(5, 12))->setTime(9, 0);

        return [
            'vessel_name' => fake()->randomElement($vessels) . ' Lines',
            'voyage'      => 'VY' . fake()->numberBetween(100, 999),
            'pol'         => fake()->randomElement($ports),
            'pod'         => fake()->randomElement($ports),
            'etd'         => $etd,
            'eta'         => $eta,
        ];
    }
}

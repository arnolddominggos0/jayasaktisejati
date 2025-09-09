<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name'  => fake()->name(),
            'phone' => '08' . fake()->numerify('##########'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        $name = fake('id_ID')->unique()->city();
        return [
            'name'      => $name,
            'province'  => null,
            'country'   => 'Indonesia',
            'slug'      => Str::slug($name) . '-' . Str::random(4), 
            'is_active' => true,
        ];
    }
}

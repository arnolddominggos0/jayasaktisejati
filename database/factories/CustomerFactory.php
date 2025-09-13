<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $cityId = City::query()->inRandomOrder()->value('id')
            ?? City::factory()->create()->id;

        return [
            'code'         => strtoupper(fake()->unique()->bothify('CUST-???-###')),
            'name'         => fake('id_ID')->company(),
            'email'        => fake()->unique()->safeEmail(),
            'nik'          => fake()->boolean(40) ? fake()->unique()->numerify('################') : null,
            'npwp'         => fake()->boolean(60) ? fake()->bothify('##.###.###.#-###.###') : null,
            'city_id'      => $cityId,
            'pic_name'     => fake('id_ID')->name(),
            'pic_phone'    => '08' . fake()->numerify('##########'),
            'address'      => fake('id_ID')->address(),
            'postal_code'  => fake()->numerify('#####'),
        ];
    }
}

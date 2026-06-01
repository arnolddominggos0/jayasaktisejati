<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\City;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $cityId = City::query()->inRandomOrder()->value('id')
            ?? City::firstOrCreate(
                ['slug' => Str::slug('Jakarta')],
                ['name' => 'Jakarta', 'country' => 'Indonesia']
            )->id;

        $fakerId = \Faker\Factory::create('id_ID');

        $type = $this->faker->boolean(70)
            ? CustomerType::Company->value      
            : CustomerType::Individual->value;  

        $isCompany = $type === CustomerType::Company->value;

        $name    = $isCompany ? $fakerId->company() : $fakerId->name();
        $picName = $fakerId->name();

        return [
            'code'        => strtoupper($this->faker->unique()->bothify('CUST-???-###')),
            'type'        => $type,
            'name'        => $name,
            'email'       => $this->faker->unique()->safeEmail(),
            'phone'       => '08' . $this->faker->numerify('##########'),

            'nik'         => $isCompany ? null : $this->faker->unique()->numerify(str_repeat('#', 16)),
            'npwp'        => $isCompany ? $this->faker->bothify('##.###.###.#-###.###') : null,

            'city_id'     => $cityId,
            'address'     => $fakerId->address(),
            'postal_code' => $this->faker->numerify('#####'),

            'pic_name'    => $picName,
            'pic_phone'   => '08' . $this->faker->numerify('##########'),
            'pic_email'   => Str::slug($picName, '.') . '@example.test',
        ];
    }
}

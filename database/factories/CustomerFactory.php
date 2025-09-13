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
        // Pastikan ada city; hindari City::factory() bila belum ada CityFactory
        $cityId = City::query()->inRandomOrder()->value('id')
            ?? City::firstOrCreate(
                ['slug' => Str::slug('Jakarta')],
                ['name' => 'Jakarta', 'country' => 'Indonesia']
            )->id;

        // Buat faker lokal Indonesia terpisah
        $fakerId = \Faker\Factory::create('id_ID');

        // 70% company, 30% individual (gunakan value enum lowercase!)
        $type = $this->faker->boolean(70)
            ? CustomerType::Company->value      // 'company'
            : CustomerType::Individual->value;  // 'individual'

        $isCompany = $type === CustomerType::Company->value;

        $name    = $isCompany ? $fakerId->company() : $fakerId->name();
        $picName = $fakerId->name();

        return [
            'code'        => strtoupper($this->faker->unique()->bothify('CUST-???-###')),
            'type'        => $type,
            'name'        => $name,
            'email'       => $this->faker->unique()->safeEmail(),
            'phone'       => '08' . $this->faker->numerify('##########'),

            // Hanya salah satu sesuai tipe
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

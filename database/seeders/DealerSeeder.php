<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dealer;
use Illuminate\Database\Seeder;

/**
 * Master Dealer awal: jaringan distribusi Toyota Astra Motor.
 * Idempotent (firstOrCreate by name).
 */
class DealerSeeder extends Seeder
{
    public function run(): void
    {
        $tam = Customer::whereRaw("LOWER(name) LIKE '%toyota astra%'")->first();

        if (! $tam) {
            $this->command?->warn('Customer "Toyota Astra Motor" tidak ditemukan — DealerSeeder dilewati.');

            return;
        }

        Dealer::firstOrCreate(
            ['name' => 'PT. Hasjrat Abadi'],
            [
                'customer_id' => $tam->id,
                'aliases'     => ['HASJRAT ABADI', 'PT HASJRAT ABADI'],
                'is_active'   => true,
            ],
        );
    }
}

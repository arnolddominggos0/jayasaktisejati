<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingLine;

class ShippingLineSeed extends Seeder
{
    public function run(): void
    {
        $lines = ['Tanto Line', 'Meratus', 'Samudera'];
        foreach ($lines as $name) {
            $code = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(collect(explode(' ', $name))->map(fn($w) => substr($w, 0, 1))->implode(''))), 0, 5) ?: 'LINE';
            $i = 1;
            $base = $code;
            while (ShippingLine::where('code', $code)->exists()) {
                $code = $base . $i;
                $i++;
            }
            ShippingLine::firstOrCreate(['name' => $name], ['code' => $code]);
        }
    }
}
    
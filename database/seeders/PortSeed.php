<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Port;

class PortSeed extends Seeder
{
    public function run(): void
    {
        $data = [
            ['code' => 'IDTPP', 'name' => 'Tanjung Priok'],
            ['code' => 'IDMDC', 'name' => 'Bitung'],
        ];
        foreach ($data as $d) {
            Port::firstOrCreate(['code' => $d['code']], $d);
        }
    }
}
    
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voyage;
use App\Models\Vessel;
use App\Models\Port;
use App\Models\ShippingLine;
use Illuminate\Support\Carbon;

class VoyageDummySeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Master Data
        |--------------------------------------------------------------------------
        */

        $shippingLine = ShippingLine::firstOrCreate(
            ['code' => 'TAM'],
            ['name' => 'TAM Shipping']
        );

        $pol = Port::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta']
        );

        $pod = Port::firstOrCreate(
            ['code' => 'BTG'],
            ['name' => 'Bitung']
        );

        $vessel = Vessel::firstOrCreate(
            ['code' => 'MV-DUMMY'],
            [
                'name' => 'MV Dummy Test',
                'shipping_line_id' => $shippingLine->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ SCHEDULED (belum berangkat)
        |--------------------------------------------------------------------------
        */

        Voyage::updateOrCreate(
            ['voyage_no' => 'V001'],
            [
                'shipping_line_id' => $shippingLine->id,
                'vessel_id' => $vessel->id,
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'etd' => Carbon::now()->addDays(5),
                'eta' => Carbon::now()->addDays(12),
                'period_month' => Carbon::now()->startOfMonth(),
                'cargo_plan' => 100,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ DELAYED (ETD lewat tapi belum ATD)
        |--------------------------------------------------------------------------
        */

        Voyage::updateOrCreate(
            ['voyage_no' => 'V002'],
            [
                'shipping_line_id' => $shippingLine->id,
                'vessel_id' => $vessel->id,
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'etd' => Carbon::now()->subDays(2),
                'eta' => Carbon::now()->addDays(5),
                'period_month' => Carbon::now()->startOfMonth(),
                'cargo_plan' => 120,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ SAILING (sudah ATD, belum ATA)
        |--------------------------------------------------------------------------
        */

        Voyage::updateOrCreate(
            ['voyage_no' => 'V003'],
            [
                'shipping_line_id' => $shippingLine->id,
                'vessel_id' => $vessel->id,
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'etd' => Carbon::now()->subDays(6),
                'atd_at' => Carbon::now()->subDays(5),
                'eta' => Carbon::now()->addDays(2),
                'period_month' => Carbon::now()->startOfMonth(),
                'cargo_plan' => 150,
                'cargo_actual' => 140,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ COMPLETED ON TIME
        |--------------------------------------------------------------------------
        */

        Voyage::updateOrCreate(
            ['voyage_no' => 'V004'],
            [
                'shipping_line_id' => $shippingLine->id,
                'vessel_id' => $vessel->id,
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'etd' => Carbon::now()->subDays(15),
                'atd_at' => Carbon::now()->subDays(15),
                'eta' => Carbon::now()->subDays(8),
                'ata_at' => Carbon::now()->subDays(8),
                'period_month' => Carbon::now()->startOfMonth(),
                'cargo_plan' => 200,
                'cargo_actual' => 200,
                'closing_at' => Carbon::now()->subDays(7),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ COMPLETED LATE
        |--------------------------------------------------------------------------
        */

        Voyage::updateOrCreate(
            ['voyage_no' => 'V005'],
            [
                'shipping_line_id' => $shippingLine->id,
                'vessel_id' => $vessel->id,
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'etd' => Carbon::now()->subDays(20),
                'atd_at' => Carbon::now()->subDays(18), // late departure
                'eta' => Carbon::now()->subDays(10),
                'ata_at' => Carbon::now()->subDays(7), // late arrival
                'period_month' => Carbon::now()->startOfMonth(),
                'cargo_plan' => 180,
                'cargo_actual' => 170,
                'closing_at' => Carbon::now()->subDays(6),
            ]
        );
    }
}
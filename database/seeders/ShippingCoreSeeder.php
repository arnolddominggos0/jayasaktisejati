<?php

namespace Database\Seeders;

use App\Enums\ScheduleState;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\ShippingSchedule;
use App\Models\Vessel;
use App\Models\Voyage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShippingCoreSeeder extends Seeder
{
    public function run(): void
    {
        $meratus = ShippingLine::firstOrCreate(['code' => 'MTS'], ['name' => 'Meratus Line']);
        $tanto = ShippingLine::firstOrCreate(['code' => 'TNT'], ['name' => 'Tanto Intim Line']);
        $jakarta = Port::firstOrCreate(['code' => 'JKT'], ['name' => 'Pelabuhan Tanjung Priok', 'city' => 'Jakarta']);
        $manado = Port::firstOrCreate(['code' => 'MND'], ['name' => 'Pelabuhan Bitung', 'city' => 'Manado']);
        $surabaya = Port::firstOrCreate(['code' => 'SBY'], ['name' => 'Pelabuhan Tanjung Perak', 'city' => 'Surabaya']);
        $meratusVessel = Vessel::firstOrCreate(['shipping_line_id' => $meratus->id, 'name' => 'KM Meratus Gorontalo'], ['code' => 'MTS-GRT', 'capacity' => 800]);
        $tantoVessel = Vessel::firstOrCreate(['shipping_line_id' => $tanto->id, 'name' => 'KM Tanto Salam'], ['code' => 'TNT-SLM', 'capacity' => 900]);
        $baseMonth = now()->startOfMonth();
        $period = $baseMonth->copy()->toDateString();
        $voyages = [
            [
                'vessel' => $meratusVessel,
                'pol' => $surabaya,
                'pod' => $manado,
                'voyage_no' => '179',
                'service' => 'REG',
                'etd' => $baseMonth->copy()->addDays(3)->setTime(8, 0),
                'eta' => $baseMonth->copy()->addDays(11)->setTime(14, 0),
                'atd' => fn($etd) => $etd->copy()->addDay(),
                'ata' => fn($eta) => $eta->copy()->addDay(),
                'cargo' => 7,
            ],
            [
                'vessel' => $tantoVessel,
                'pol' => $jakarta,
                'pod' => $manado,
                'voyage_no' => '151',
                'service' => 'TAM',
                'etd' => $baseMonth->copy()->addDays(8)->setTime(9, 0),
                'eta' => $baseMonth->copy()->addDays(19)->setTime(15, 0),
                'atd' => fn($etd) => $etd->copy()->addDays(2),
                'ata' => fn($eta) => $eta->copy()->addDay(),
                'cargo' => 9,
            ],
            [
                'vessel' => $meratusVessel,
                'pol' => $manado,
                'pod' => $surabaya,
                'voyage_no' => '180',
                'service' => 'RETURN',
                'etd' => $baseMonth->copy()->addDays(20)->setTime(7, 0),
                'eta' => $baseMonth->copy()->addDays(28)->setTime(12, 0),
                'atd' => fn($etd) => $etd->copy()->addDay(),
                'ata' => fn($eta) => $eta->copy()->addDay(),
                'cargo' => 6,
            ],
        ];
        foreach ($voyages as $v) {
            $voy = Voyage::firstOrCreate(
                [
                    'vessel_id' => $v['vessel']->id,
                    'voyage_no' => $v['voyage_no'],
                    'pol_id' => $v['pol']->id,
                    'pod_id' => $v['pod']->id,
                ],
                [
                    'service' => $v['service'],
                    'etd' => $v['etd'],
                    'eta' => $v['eta'],
                ]
            );
            $atd = value($v['atd'], $v['etd']->copy());
            $ata = value($v['ata'], $v['eta']->copy());
            $voy->forceFill(['atd_at' => $atd, 'ata_at' => $ata])->saveQuietly();
            ShippingSchedule::updateOrCreate(
                ['voyage_id' => $voy->id],
                [
                    'shipping_line_id' => $v['vessel']->shipping_line_id,
                    'vessel_id' => $v['vessel']->id,
                    'vessel_name' => $v['vessel']->name,
                    'voyage_no' => $v['voyage_no'],
                    'cargo_plan' => (int) $v['cargo'],
                    'jss' => 'AUTO',
                    'dwelling_days' => 6,
                    'kpi_sailing_days' => 10,
                    'actual_sailing_days' => (int) $atd->diffInDays($ata),
                    'etd' => $v['etd'],
                    'eta' => $v['eta'],
                    'period_month' => $period,
                    'state' => ScheduleState::Final,
                    'approved_by_name' => 'TAM Logistics',
                    'final_note' => 'Autoseeded ' . Str::upper($v['service']),
                    'final_source' => 'Seeder',
                    'final_attachment_path' => null,
                    'finalized_at' => Carbon::now(),
                ]
            );
        }
        ShippingSchedule::query()->with('voyage')->get()->each->refreshActualSailing();
    }
}

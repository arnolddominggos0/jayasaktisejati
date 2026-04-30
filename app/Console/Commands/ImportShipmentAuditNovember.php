<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Shipment, Voyage, ShippingSchedule};
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportShipmentAuditNovember extends Command
{
    protected $signature = 'import:shipment-audit-november {--year=2025}';
    protected $description = 'Import shipment audit November as historical delivered data';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $path = storage_path('app/import/shipment_audit_november.xlsx');

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, false);

        $header = [];
        foreach ($rows as $i => $row) {
            if ($i < 2) continue;
            if (empty(array_filter($row))) continue;

            if (empty($header)) {
                $header = array_map('trim', $row);
                continue;
            }

            $d = array_combine($header, $row);

            if (empty($d['Nama Kapal'])) continue;

            preg_match('/V\.\s*\d+/i', $d['Nama Kapal'], $m);
            $voyageNo = strtoupper(trim($m[0] ?? ''));

            $voyage = Voyage::where('voyage_no', $voyageNo)->first();
            if (! $voyage) continue;

            $schedule = ShippingSchedule::where('voyage_id', $voyage->id)->first();

            $pickupAt = $this->dt($d['Tanggal Masuk Pelabuhan'], $year);
            $atd      = $this->dt($d['Tanggal Kapal Berangkat (ATD)'], $year);
            $ata      = $this->dt($d['Tanggal Kapal Sandar (ATA)'], $year);
            $delivAt  = $this->dt($d['Tanggal Masuk RVDC/Cabang Tujuan'], $year);

            $shipment = Shipment::create([
                'mode'                 => 'sea',
                'service_type'         => 'sea_freight',
                'status'               => 'delivered',
                'vessel_name'          => trim($d['Nama Kapal']),
                'voyage'               => $voyage->voyage_no,
                'voyage_id'            => $voyage->id,
                'shipping_schedule_id' => $schedule?->id,
                'pol'                  => $voyage->pol?->name,
                'pod'                  => $voyage->pod?->name,
                'etd'                  => $atd,
                'eta'                  => $ata,
                'delivered_at'         => $delivAt,
                'units' => [[
                    'no_rangka' => trim((string) $d['No Rangka']),
                    'no_mesin'  => trim((string) $d['No Mesin']),
                    'model'     => trim((string) $d['Model']),
                    'qty'       => 1,
                ]],
            ]);

            $shipment->tracks()->delete();

            $shipment->tracks()->insert([
                [
                    'shipment_id' => $shipment->id,
                    'status'      => 'pickup',
                    'tracked_at'  => $pickupAt,
                    'created_at'  => $pickupAt,
                    'updated_at'  => $pickupAt,
                ],
                [
                    'shipment_id' => $shipment->id,
                    'status'      => 'vessel_depart',
                    'tracked_at'  => $atd,
                    'created_at'  => $atd,
                    'updated_at'  => $atd,
                ],
                [
                    'shipment_id' => $shipment->id,
                    'status'      => 'vessel_arrival',
                    'tracked_at'  => $ata,
                    'created_at'  => $ata,
                    'updated_at'  => $ata,
                ],
                [
                    'shipment_id' => $shipment->id,
                    'status'      => 'delivered',
                    'tracked_at'  => $delivAt,
                    'created_at'  => $delivAt,
                    'updated_at'  => $delivAt,
                ],
            ]);

            $shipment->forceFill([
                'created_at' => $pickupAt,
                'updated_at' => $delivAt,
            ])->saveQuietly();
        }

        $this->info('Shipment audit November imported as historical data');
        return self::SUCCESS;
    }

    protected function dt($val, int $year): ?Carbon
    {
        if (! $val) return null;
        return Carbon::createFromFormat('d-M', trim($val))
            ->year($year)
            ->startOfDay();
    }
}

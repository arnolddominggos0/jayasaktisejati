<?php

namespace App\Console\Commands;

use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\Vessel;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportTamMay2026Voyages extends Command
{
    protected $signature = 'import:tam-may-2026-voyages';

    protected $description = 'Bootstrap TAM May 2026 voyages';

    public function handle(): int
    {
        $csv = storage_path('imports/tam/may2026/voyages.csv');
        
        if (! file_exists($csv)) {
            $this->error("File tidak ditemukan: {$csv}");
            return self::FAILURE;
        }

        $pol = Port::where('name', 'like', '%Tanjung Priok%')->first();
        $pod = Port::where('name', 'like', '%Bitung%')->first();

        if (! $pol || ! $pod) {
            $this->error('Port Tanjung Priok / Bitung belum tersedia.');
            return self::FAILURE;
        }

        $handle = fopen($csv, 'r');

        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {

            [
                $shippingLineName,
                $vesselName,
                $voyageNo,
                $etd,
                $eta,
                $cargoPlan,
                $cargoActual
            ] = $row;

            $shippingLine = ShippingLine::where(
                'name',
                'like',
                "%{$shippingLineName}%"
            )->first();

            if (! $shippingLine) {
                $this->error(
                    "Shipping Line tidak ditemukan: {$shippingLineName}"
                );
                continue;
            }

            $vessel = Vessel::where(
                'shipping_line_id',
                $shippingLine->id
            )
                ->where('name', 'like', "%{$vesselName}%")
                ->first();

            if (! $vessel) {
                $this->error(
                    "Vessel tidak ditemukan: {$vesselName}"
                );
                continue;
            }

            Voyage::updateOrCreate(
                [
                    'vessel_id' => $vessel->id,
                    'voyage_no' => (string) $voyageNo,
                ],
                [
                    'shipping_line_id' => $shippingLine->id,

                    'pol_id' => $pol->id,
                    'pod_id' => $pod->id,

                    'period_month' => '2026-05-01',

                    'etd' => Carbon::parse($etd),
                    'eta' => Carbon::parse($eta),

                    'cargo_plan' => (int) $cargoPlan,
                    'cargo_actual' => (int) $cargoActual,
                ]
            );

            $this->info(
                "✓ {$vessel->name} V.{$voyageNo}"
            );
        }

        fclose($handle);

        $this->newLine();

        $this->info(
            'TAM May 2026 voyage bootstrap selesai.'
        );

        return self::SUCCESS;
    }
}

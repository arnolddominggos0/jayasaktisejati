<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Shipment;
use App\Models\Voyage;
use App\Models\Port;
use Carbon\Carbon;

class ImportShipmentNovember extends Command
{
    protected $signature = 'import:shipment-november {--year=2025}';
    protected $description = 'Import Shipment November (historical) from Excel';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $path = storage_path('app/import/shipment_november.xlsx');

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        $sheets = Excel::toArray([], $path);
        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            $this->error('File kosong');
            return self::FAILURE;
        }

        $headerRowIndex = collect($rows)->search(fn($r) => in_array('shipment_no', array_map('strtolower', $r ?? [])));
        if ($headerRowIndex === false) {
            $this->error('Header tidak ditemukan');
            return self::FAILURE;
        }

        $header = array_map(fn($h) => strtolower(trim($h)), $rows[$headerRowIndex]);

        DB::beginTransaction();

        try {
            foreach (array_slice($rows, $headerRowIndex + 1) as $row) {
                if (empty(array_filter($row))) continue;

                $data = array_combine($header, $row);

                $voyage = Voyage::where('voyage_no', trim($data['voyage_no'] ?? ''))
                    ->whereYear('period_month', $year)
                    ->first();

                if (! $voyage) continue;

                $pol = Port::where('code', strtoupper($data['pol_code'] ?? ''))->first();
                $pod = Port::where('code', strtoupper($data['pod_code'] ?? ''))->first();

                Shipment::updateOrCreate(
                    [
                        'shipment_no' => trim($data['shipment_no']),
                    ],
                    [
                        'voyage_id'       => $voyage->id,
                        'vessel_id'       => $voyage->vessel_id,
                        'pol_id'          => $pol?->id,
                        'pod_id'          => $pod?->id,
                        'container_no'    => trim($data['container_no'] ?? null),
                        'container_size'  => (int) ($data['container_size'] ?? null),
                        'cargo_type'      => trim($data['cargo_type'] ?? null),
                        'atd_at'          => $this->parseExcelDate($data['atd'] ?? null, $year),
                        'ata_at'          => $this->parseExcelDate($data['ata'] ?? null, $year),
                        'is_historical'   => true,
                    ]
                );
            }

            DB::commit();
            $this->info('Shipment November imported');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    protected function parseExcelDate($value, int $year): ?Carbon
    {
        if (! $value) return null;

        if (is_numeric($value)) {
            return Carbon::instance(
                \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
            )->setYear($year)->startOfDay();
        }

        return Carbon::createFromFormat('d-M', trim($value))
            ->setYear($year)
            ->startOfDay();
    }
}

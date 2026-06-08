<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrichVoyage154FromCsv extends Command
{
    protected $signature = 'voyage154:enrich-from-csv
                            {--dry-run : Tampilkan rencana perubahan tanpa menyimpan ke database}';

    protected $description = 'Enrichment & normalisasi data Voyage 154 dari CSV tam_v154.csv: '
        . 'isi reg_no, color, do_number, container_display pada units, '
        . 'dan verifikasi destination_city_id pada shipments.';

    private const CSV_PATH = 'storage/imports/tam/may2026/tam_v154.csv';

    // Indeks kolom CSV (0-based, setelah header dibuang)
    private const COL_NO            = 0;
    private const COL_DEST_CITY     = 1;
    private const COL_RECEIVER      = 2;
    private const COL_REG_NO        = 3;
    private const COL_VIN           = 4;
    private const COL_ENGINE        = 5;
    private const COL_MODEL         = 6;
    private const COL_COLOR         = 7;
    private const COL_DO_NUMBER     = 8;
    private const COL_CONTAINER     = 9;
    private const COL_STUFFING_DATE = 10;
    private const COL_VESSEL        = 11;
    private const COL_PORT_ARRIVAL  = 12;
    private const COL_ATD           = 13;
    private const COL_ATA           = 14;
    private const COL_RVDC_DATE     = 15;

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║   VOYAGE 154 — Enrichment dari CSV tam_v154.csv          ║');
        if ($isDryRun) {
            $this->line('║   MODE: DRY-RUN — tidak ada perubahan yang disimpan      ║');
        }
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        // ─── 1. Baca CSV ─────────────────────────────────────────────────────
        $csvPath = base_path(self::CSV_PATH);

        if (! file_exists($csvPath)) {
            $this->error("File CSV tidak ditemukan: {$csvPath}");
            return self::FAILURE;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (count($lines) < 2) {
            $this->error('CSV kosong atau hanya berisi header.');
            return self::FAILURE;
        }

        // Buang header
        array_shift($lines);
        $totalRows = count($lines);

        $this->line("  File CSV    : " . self::CSV_PATH);
        $this->line("  Total baris : {$totalRows}");
        $this->newLine();

        // ─── 2. Load cities dari DB (case-insensitive lookup) ─────────────────
        $cityRows    = DB::table('cities')->get(['id', 'name']);
        $cityByName  = $cityRows->keyBy(fn ($c) => strtolower(trim($c->name)));

        // ─── 3. Parse setiap baris CSV & bangun rencana ───────────────────────
        $plan              = [];
        $warnings          = [];
        $skippedCityMiss   = 0;
        $skippedChassisMiss = 0;
        $skippedShipMiss   = 0;

        foreach ($lines as $lineNo => $rawLine) {
            $csvLineNum = $lineNo + 2; // +2: 1-based + header row
            $cols = str_getcsv($rawLine);

            $vin          = trim($cols[self::COL_VIN]          ?? '');
            $destCityName = trim($cols[self::COL_DEST_CITY]    ?? '');
            $regNo        = trim($cols[self::COL_REG_NO]       ?? '');
            $color        = trim($cols[self::COL_COLOR]        ?? '');
            $doNumber     = trim($cols[self::COL_DO_NUMBER]    ?? '');
            $container    = trim($cols[self::COL_CONTAINER]    ?? '');
            $csvRowNo     = trim($cols[self::COL_NO]           ?? (string)$csvLineNum);

            // Validasi VIN
            if ($vin === '') {
                $warnings[] = "Baris {$csvLineNum} (No={$csvRowNo}): VIN kosong — dilewati.";
                $skippedChassisMiss++;
                continue;
            }

            // Validasi city
            if ($destCityName === '') {
                $warnings[] = "Baris {$csvLineNum} VIN={$vin}: Destination City kosong — dilewati.";
                $skippedCityMiss++;
                continue;
            }

            $cityKey  = strtolower($destCityName);
            $cityObj  = $cityByName->get($cityKey);

            if (! $cityObj) {
                $warnings[] = "Baris {$csvLineNum} VIN={$vin}: City \"{$destCityName}\" tidak ditemukan di tabel cities — dilewati.";
                $skippedCityMiss++;
                continue;
            }

            // Cari unit berdasarkan chassis_no
            $unit = DB::table('units')->where('chassis_no', $vin)->first();

            if (! $unit) {
                $warnings[] = "Baris {$csvLineNum} VIN={$vin}: chassis_no tidak ditemukan di tabel units — dilewati.";
                $skippedChassisMiss++;
                continue;
            }

            // Cari shipment terkait
            $shipment = DB::table('shipments')->where('id', $unit->shipment_id)->first();

            if (! $shipment) {
                $warnings[] = "Baris {$csvLineNum} VIN={$vin}: shipment_id={$unit->shipment_id} tidak ditemukan di tabel shipments — dilewati.";
                $skippedShipMiss++;
                continue;
            }

            // Dapatkan nama origin city untuk route_summary
            $originCity = DB::table('cities')->where('id', $shipment->origin_city_id)->first();
            $originName = $originCity ? $originCity->name : ($shipment->route_from ?? 'JAKARTA');

            $newDestCityId    = $cityObj->id;
            $newRouteTo       = $cityObj->name;
            $newRouteSummary  = "{$originName} → {$cityObj->name}";

            $plan[] = [
                // Identitas
                'csv_row'          => $csvRowNo,
                'vin'              => $vin,
                'shipment_id'      => $shipment->id,
                'shipment_code'    => $shipment->code,
                'unit_id'          => $unit->id,

                // Shipment: current
                'old_dest_city_id'  => $shipment->destination_city_id,
                'old_route_to'      => $shipment->route_to,
                'old_route_summary' => $shipment->route_summary,

                // Shipment: new
                'new_dest_city_id'  => $newDestCityId,
                'new_route_to'      => $newRouteTo,
                'new_route_summary' => $newRouteSummary,

                // Unit: current
                'old_reg_no'        => $unit->reg_no,
                'old_color'         => $unit->color,
                'old_do_number'     => $unit->do_number,
                'old_container'     => $unit->container_display,

                // Unit: new
                'new_reg_no'        => $regNo   !== '' ? $regNo   : null,
                'new_color'         => $color    !== '' ? $color   : null,
                'new_do_number'     => $doNumber !== '' ? $doNumber : null,
                'new_container'     => $container !== '' ? $container : null,
            ];
        }

        // ─── 4. Tampilkan tabel preview ───────────────────────────────────────
        $this->line('┌──────────────────────────────────────────────────────────┐');
        $this->line('│ PREVIEW PERUBAHAN                                        │');
        $this->line('└──────────────────────────────────────────────────────────┘');
        $this->newLine();

        if (empty($plan)) {
            $this->warn('  Tidak ada baris yang dapat diproses.');
        } else {
            $this->table(
                [
                    'No',
                    'VIN',
                    'Shipment Code',
                    'Old Destination',
                    'New Destination',
                    'Old Reg No',
                    'New Reg No',
                    'Old Color',
                    'New Color',
                    'Old DO',
                    'New DO',
                    'Old Container',
                    'New Container',
                ],
                array_map(fn ($p) => [
                    $p['csv_row'],
                    $p['vin'],
                    $p['shipment_code'],
                    $p['old_route_to']   ?? 'NULL',
                    $p['new_route_to'],
                    $p['old_reg_no']     ?? 'NULL',
                    $p['new_reg_no']     ?? 'NULL',
                    $p['old_color']      ?? 'NULL',
                    $p['new_color']      ?? 'NULL',
                    $p['old_do_number']  ?? 'NULL',
                    $p['new_do_number']  ?? 'NULL',
                    $p['old_container']  ?? 'NULL',
                    $p['new_container']  ?? 'NULL',
                ], $plan)
            );
        }

        // ─── 5. Tampilkan warnings ────────────────────────────────────────────
        if (! empty($warnings)) {
            $this->newLine();
            $this->line('  ⚠  Peringatan:');
            foreach ($warnings as $w) {
                $this->warn("     {$w}");
            }
        }

        $this->newLine();

        // ─── 6. Summary angka sebelum eksekusi ───────────────────────────────
        $countPlan   = count($plan);
        $countSkip   = $skippedCityMiss + $skippedChassisMiss + $skippedShipMiss;

        $this->line(sprintf('  Total baris CSV           : %d', $totalRows));
        $this->line(sprintf('  Akan diproses             : %d', $countPlan));
        $this->line(sprintf('  Dilewati (total)          : %d', $countSkip));
        $this->line(sprintf('    - City tidak ditemukan  : %d', $skippedCityMiss));
        $this->line(sprintf('    - Chassis tidak ada     : %d', $skippedChassisMiss));
        $this->line(sprintf('    - Shipment tidak ada    : %d', $skippedShipMiss));
        $this->newLine();

        // ─── 7. Dry-run berhenti di sini ─────────────────────────────────────
        if ($isDryRun) {
            $this->warn('  DRY-RUN selesai. Tidak ada perubahan yang disimpan ke database.');
            $this->newLine();
            return self::SUCCESS;
        }

        // ─── 8. Konfirmasi sebelum eksekusi ───────────────────────────────────
        if (empty($plan)) {
            $this->warn('  Tidak ada perubahan untuk dieksekusi.');
            return self::SUCCESS;
        }

        if (! $this->confirm("  Lanjutkan update {$countPlan} shipment & unit ke database?", false)) {
            $this->line('  Dibatalkan oleh user.');
            $this->newLine();
            return self::SUCCESS;
        }

        $this->newLine();

        // ─── 9. Eksekusi dalam satu transaksi ────────────────────────────────
        $updatedShipments = 0;
        $updatedUnits     = 0;

        DB::transaction(function () use ($plan, &$updatedShipments, &$updatedUnits) {
            foreach ($plan as $p) {
                // Update shipment (destination_city_id, route_to, route_summary)
                $shipmentChanged =
                    $p['old_dest_city_id']  !== $p['new_dest_city_id']  ||
                    $p['old_route_to']      !== $p['new_route_to']       ||
                    $p['old_route_summary'] !== $p['new_route_summary'];

                if ($shipmentChanged) {
                    DB::table('shipments')
                        ->where('id', $p['shipment_id'])
                        ->update([
                            'destination_city_id' => $p['new_dest_city_id'],
                            'route_to'            => $p['new_route_to'],
                            'route_summary'       => $p['new_route_summary'],
                            'updated_at'          => now(),
                        ]);
                    $updatedShipments++;
                }

                // Update unit (reg_no, color, do_number, container_display)
                DB::table('units')
                    ->where('id', $p['unit_id'])
                    ->update([
                        'reg_no'            => $p['new_reg_no'],
                        'color'             => $p['new_color'],
                        'do_number'         => $p['new_do_number'],
                        'container_display' => $p['new_container'],
                        'updated_at'        => now(),
                    ]);
                $updatedUnits++;

                $this->line(sprintf(
                    '  ✓ %-35s  VIN=%-20s  → %s',
                    $p['shipment_code'],
                    $p['vin'],
                    $p['new_route_summary']
                ));
            }
        });

        $this->newLine();

        // ─── 10. Post-update: verifikasi & distribusi ─────────────────────────
        $this->line('┌──────────────────────────────────────────────────────────┐');
        $this->line('│ POST-UPDATE VERIFICATION                                 │');
        $this->line('└──────────────────────────────────────────────────────────┘');
        $this->newLine();

        // Distribusi destination city
        $cityDist = DB::table('shipments as s')
            ->join('cities as c', 'c.id', '=', 's.destination_city_id')
            ->where('s.code', 'like', 'VOY154%')
            ->selectRaw('c.name as city_name, COUNT(*) as cnt')
            ->groupBy('c.name')
            ->orderByDesc('cnt')
            ->get();

        $this->line('  Distribusi Destination City (Voyage 154):');
        $this->newLine();

        $maxCount = $cityDist->max('cnt') ?: 1;
        foreach ($cityDist as $row) {
            $barLen = (int) round($row->cnt / $maxCount * 20);
            $bar    = str_repeat('█', max(1, $barLen));
            $this->line(sprintf('    %-14s %s (%d)', $row->city_name, $bar, $row->cnt));
        }

        $this->newLine();

        // Cek unit yang masih NULL (seharusnya 0 setelah enrichment)
        $nullUnits = DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->where('s.code', 'like', 'VOY154%')
            ->whereNull('u.reg_no')
            ->count();

        $nullCityShipments = DB::table('shipments')
            ->where('code', 'like', 'VOY154%')
            ->whereNull('destination_city_id')
            ->count();

        $this->line(sprintf('  Shipment diupdate         : %d', $updatedShipments));
        $this->line(sprintf('  Unit diupdate             : %d', $updatedUnits));
        $this->line(sprintf('  Unit masih NULL reg_no    : %d', $nullUnits));
        $this->line(sprintf('  Shipment NULL city_id     : %d', $nullCityShipments));
        $this->newLine();

        if ($nullUnits === 0 && $nullCityShipments === 0) {
            $this->info('  ✅ Enrichment selesai. Seluruh data Voyage 154 sudah lengkap.');
        } else {
            $this->warn("  ⚠  Masih ada data yang belum lengkap — periksa warnings di atas.");
        }

        $this->newLine();
        return self::SUCCESS;
    }
}

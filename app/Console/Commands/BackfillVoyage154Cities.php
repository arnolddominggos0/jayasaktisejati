<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVoyage154Cities extends Command
{
    protected $signature = 'backfill:voyage-154-cities
                            {--dry-run : Tampilkan perubahan tanpa menyimpan ke database}
                            {--force  : Jalankan tanpa konfirmasi interaktif}';

    protected $description = '[COMPLETED — one-time historical import, do not re-run] '
        . 'Backfill origin_city_id dan destination_city_id untuk 17 unit Voyage 154 berdasarkan CSV tam_v154.csv. '
        . 'Shipment codes were subsequently refactored from VOY154TTSTJKTMND-XXX to JSS0526SH-series (Part J).';

    /**
     * Chassis → Destination City (dari kolom "Destination City" CSV tam_v154.csv).
     * Source of truth: storage/imports/tam/may2026/tam_v154.csv
     *
     * NOTE: Shipment codes originally generated as VOY154TTSTJKTMND-XXX have been
     * refactored to JSS0526SH0001–JSS0526SH0009 (9 shipments, 17 units, voyage_id=1).
     * The sequence numbers below (No.1–No.17) are CSV row numbers, not shipment codes.
     */
    private const CHASSIS_TO_DEST_CITY = [
        'MHKA6GJ6JTJ225699' => 'Manado',    // CSV No.1  (Voyage 154, JSS0526SH-series)
        'MHKAB1BA0TJ164766' => 'Manado',    // CSV No.2
        'MHFA71BY8T0006467' => 'Bitung',    // CSV No.3
        'MHFA71BY7T0006492' => 'Tomohon',   // CSV No.4
        'MR0DB8CD4T0136453' => 'Lolak',     // CSV No.5
        'MHKA6GJ6JTJ225706' => 'Bitung',    // CSV No.6
        'MHFA71BY3T0006442' => 'Airmadidi', // CSV No.7
        'MHFA71BY1T0006522' => 'Dumoga',    // CSV No.8
        'MHKAB1BC9TJ084125' => 'Tendean',   // CSV No.9
        'MHKAB1BC0TJ084126' => 'Tendean',   // CSV No.10
        'MHKA6GK6JTJ090667' => 'Tendean',   // CSV No.11
        'MHKA6GJ6JTJ226233' => 'Tendean',   // CSV No.12
        'MHKE8FB3JTK120422' => 'Tendean',   // CSV No.13
        'MHFA71BY8T0005772' => 'Tendean',   // CSV No.14
        'MHFA71BYXT0006034' => 'Tendean',   // CSV No.15
        'MHKA6GJ6JTJ226238' => 'Bitung',    // CSV No.16
        'MR0AW9AA4T0344192' => 'Bitung',    // CSV No.17
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForce  = $this->option('force');

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║   BACKFILL: Voyage 154 City Mapping                  ║');
        $this->line('║   Source of truth: tam_v154.csv                      ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('  MODE: DRY-RUN — tidak ada perubahan yang akan disimpan.');
            $this->newLine();
        }

        // Step 1: Load cities dari database (case-insensitive lookup)
        $cityRows = DB::table('cities')->get(['id', 'name']);
        $cityByName = $cityRows->keyBy(fn ($c) => strtolower(trim($c->name)));

        // Step 2: Resolve semua city yang dibutuhkan
        $neededNames = array_unique(array_merge(
            ['jakarta'],
            array_map('strtolower', array_values(self::CHASSIS_TO_DEST_CITY))
        ));

        $missing = [];
        foreach ($neededNames as $name) {
            if (! $cityByName->has($name)) {
                $missing[] = strtoupper($name);
            }
        }

        if (! empty($missing)) {
            $this->error('City berikut belum ada di tabel cities:');
            foreach ($missing as $m) {
                $this->line("  - {$m}");
            }
            $this->error('Tambahkan city yang hilang terlebih dahulu, lalu jalankan ulang command ini.');
            return self::FAILURE;
        }

        $jakartaCity = $cityByName->get('jakarta');

        // Step 3: Load shipments V154 + units
        $shipmentRows = DB::table('shipments as s')
            ->join('units as u', 'u.shipment_id', '=', 's.id')
            ->where('s.voyage_id', 1)   // Voyage 154 (codes refactored to JSS0526SH-series)
            ->select(
                's.id as shipment_id',
                's.code',
                's.origin_city_id as old_origin_city_id',
                's.destination_city_id as old_dest_city_id',
                's.route_from as old_route_from',
                's.route_to as old_route_to',
                's.route_summary as old_route_summary',
                'u.chassis_no'
            )
            ->orderBy('s.id')
            ->get();

        if ($shipmentRows->isEmpty()) {
            $this->error('Tidak ditemukan shipment Voyage 154 di database.');
            return self::FAILURE;
        }

        // Step 4: Build mapping plan
        $plan       = [];
        $warnings   = [];
        $cityTally  = []; // untuk distribusi per kota

        foreach ($shipmentRows as $row) {
            $chassis = $row->chassis_no;

            if (! isset(self::CHASSIS_TO_DEST_CITY[$chassis])) {
                $warnings[] = "Chassis [{$chassis}] tidak ada dalam mapping CSV — shipment [{$row->code}] dilewati.";
                continue;
            }

            $destCityName = self::CHASSIS_TO_DEST_CITY[$chassis];
            $destCity     = $cityByName->get(strtolower($destCityName));

            $newOriginCityId  = $jakartaCity->id;
            $newDestCityId    = $destCity->id;
            $newRouteFrom     = $jakartaCity->name;
            $newRouteTo       = $destCity->name;
            $newRouteSummary  = "{$jakartaCity->name} → {$destCity->name}";

            $plan[] = [
                'shipment_id'         => $row->shipment_id,
                'code'                => $row->code,
                'chassis_no'          => $chassis,
                'old_origin_city_id'  => $row->old_origin_city_id,
                'old_dest_city_id'    => $row->old_dest_city_id,
                'old_route_from'      => $row->old_route_from,
                'old_route_to'        => $row->old_route_to,
                'old_route_summary'   => $row->old_route_summary,
                'new_origin_city_id'  => $newOriginCityId,
                'new_dest_city_id'    => $newDestCityId,
                'new_route_from'      => $newRouteFrom,
                'new_route_to'        => $newRouteTo,
                'new_route_summary'   => $newRouteSummary,
            ];

            $cityTally[$destCityName] = ($cityTally[$destCityName] ?? 0) + 1;
        }

        // Step 5: Verification report
        $this->line('┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐');
        $this->line('│ VERIFICATION REPORT                                                                                                                     │');
        $this->line('└─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();

        $tableData = array_map(fn ($p) => [
            $p['code'],
            $p['old_dest_city_id']  ?? 'NULL',
            $p['new_dest_city_id'],
            $p['old_route_to']      ?? 'NULL',
            $p['new_route_to'],
            $p['old_route_from']    ?? 'NULL',
            $p['new_route_from'],
            $p['old_route_summary'] ?? 'NULL',
            $p['new_route_summary'],
        ], $plan);

        $this->table(
            [
                'Shipment Code',
                'Old dest_city_id',
                'New dest_city_id',
                'Old route_to',
                'New route_to',
                'Old route_from',
                'New route_from',
                'Old route_summary',
                'New route_summary',
            ],
            $tableData
        );

        $this->newLine();

        // Tampilkan warnings jika ada
        foreach ($warnings as $w) {
            $this->warn("  ⚠  {$w}");
        }
        if (! empty($warnings)) {
            $this->newLine();
        }

        // Step 6: Distribusi per kota
        $this->line('  Distribusi Destination City:');
        arsort($cityTally);
        foreach ($cityTally as $city => $count) {
            $bar = str_repeat('█', $count);
            $this->line(sprintf('    %-12s %s (%d)', $city, $bar, $count));
        }
        $this->newLine();

        // Step 7: Summary angka
        $this->line(sprintf('  Total shipment V154   : %d', $shipmentRows->count()));
        $this->line(sprintf('  Akan di-update        : %d', count($plan)));
        $this->line(sprintf('  Dilewati (no mapping) : %d', count($warnings)));
        $this->newLine();

        // Step 8: Dry-run berhenti di sini
        if ($isDryRun) {
            $this->warn('  DRY-RUN selesai. Tidak ada perubahan yang disimpan.');
            $this->newLine();
            return self::SUCCESS;
        }

        // Step 9: Konfirmasi (kecuali --force)
        if (! $isForce) {
            if (! $this->confirm('  Lanjutkan dan simpan perubahan ke database?', false)) {
                $this->line('  Dibatalkan oleh user.');
                $this->newLine();
                return self::SUCCESS;
            }
            $this->newLine();
        }

        // Step 10: Eksekusi update dalam satu transaksi
        $updated = 0;

        DB::transaction(function () use ($plan, &$updated) {
            foreach ($plan as $p) {
                DB::table('shipments')
                    ->where('id', $p['shipment_id'])
                    ->update([
                        'origin_city_id'      => $p['new_origin_city_id'],
                        'destination_city_id' => $p['new_dest_city_id'],
                        'route_from'          => $p['new_route_from'],
                        'route_to'            => $p['new_route_to'],
                        'route_summary'       => $p['new_route_summary'],
                        'updated_at'          => now(),
                    ]);
                $updated++;
                $this->line("  ✓ {$p['code']}  →  {$p['new_route_summary']}");
            }
        });

        $this->newLine();

        // Step 11: Post-update verification
        $this->line('┌─────────────────────────────────────────────┐');
        $this->line('│ POST-UPDATE VERIFICATION                    │');
        $this->line('└─────────────────────────────────────────────┘');
        $this->newLine();

        $remaining = DB::table('shipments')
            ->where('voyage_id', 1)   // Voyage 154 (codes refactored to JSS0526SH-series)
            ->where(function ($q) {
                $q->whereNull('origin_city_id')->orWhereNull('destination_city_id');
            })
            ->count();

        $cityDist = DB::table('shipments as s')
            ->join('cities as c', 'c.id', '=', 's.destination_city_id')
            ->where('s.voyage_id', 1)   // Voyage 154 (codes refactored to JSS0526SH-series)
            ->selectRaw('c.name as city_name, COUNT(*) as cnt')
            ->groupBy('c.name')
            ->orderByDesc('cnt')
            ->get();

        $this->table(
            ['Destination City', 'Jumlah Shipment'],
            $cityDist->map(fn ($r) => [$r->city_name, $r->cnt])->toArray()
        );
        $this->newLine();

        $this->line(sprintf('  Total V154 shipment     : %d', DB::table('shipments')->where('voyage_id', 1)->count()));
        $this->line(sprintf('  Berhasil di-update      : %d', $updated));
        $this->line(sprintf('  Remaining NULL city_id  : %d', $remaining));
        $this->newLine();

        if ($remaining === 0) {
            $this->info('  ✅ Backfill selesai. Seluruh shipment Voyage 154 sudah memiliki city reference.');
        } else {
            $this->warn("  ⚠  Masih ada {$remaining} shipment dengan city_id NULL.");
        }

        $this->newLine();
        return self::SUCCESS;
    }
}

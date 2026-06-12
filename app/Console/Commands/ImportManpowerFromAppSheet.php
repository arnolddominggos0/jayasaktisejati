<?php

namespace App\Console\Commands;

use App\Models\Depot;
use App\Models\Manpower;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Import master manpower dari CSV AppSheet.
 *
 * CSV wajib memiliki header baris pertama. Kolom yang dikenali (case-insensitive):
 *   appsheet_id  – key dari tabel manpower di AppSheet (wajib, unik)
 *   name         – nama lengkap (wajib)
 *   phone        – nomor telepon (opsional)
 *   depot_id     – ID depot di Laravel (wajib jika branch_id tidak diisi)
 *   depot_code   – alternatif depot_id: kode depot (e.g. DEP-TAM-JKT)
 *   branch_id    – ID cabang di Laravel (diisi otomatis dari depot jika kosong)
 *   domain       – sea_freight | inland (default: sea_freight)
 *   active       – 1/true/yes = aktif, 0/false/no = nonaktif (default: true)
 *
 * Contoh penggunaan:
 *   php artisan manpower:import-appsheet storage/app/manpower.csv
 *   php artisan manpower:import-appsheet storage/app/manpower.csv --dry-run
 *   php artisan manpower:import-appsheet storage/app/manpower.csv --update
 *   php artisan manpower:import-appsheet storage/app/manpower.csv --depot=1
 */
class ImportManpowerFromAppSheet extends Command
{
    protected $signature = 'manpower:import-appsheet
                            {file : Path ke file CSV}
                            {--dry-run : Preview perubahan tanpa menyimpan}
                            {--update : Update record yang sudah ada (upsert berdasarkan appsheet_id)}
                            {--depot= : Override depot_id untuk semua baris (jika CSV tidak memiliki kolom depot_id)}
                            {--domain=sea_freight : Default domain jika CSV tidak memiliki kolom domain}';

    protected $description = 'Import master manpower dari CSV AppSheet (menyertakan appsheet_id sebagai bridge key)';

    // Kolom CSV yang dikenali → field model
    private const COL_MAP = [
        'appsheet_id' => 'appsheet_id',
        'id appsheet' => 'appsheet_id',
        'appsheet id' => 'appsheet_id',
        'mp id'       => 'appsheet_id',   // jika CSV menggunakan header "MP ID"
        'name'        => 'name',
        'nama'        => 'name',
        'nama mp'     => 'name',
        'phone'       => 'phone',
        'telepon'     => 'phone',
        'no. telepon' => 'phone',
        'depot_id'    => 'depot_id',
        'depot id'    => 'depot_id',
        'depot_code'  => 'depot_code',
        'kode depot'  => 'depot_code',
        'branch_id'   => 'branch_id',
        'branch id'   => 'branch_id',
        'domain'      => 'domain',
        'active'      => 'active',
        'aktif'       => 'active',
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $doUpdate = $this->option('update');
        $overrideDepotId = $this->option('depot') ? (int) $this->option('depot') : null;
        $defaultDomain   = $this->option('domain') ?? 'sea_freight';

        // ── Validate file ─────────────────────────────────────────────────────
        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            $this->error("Tidak dapat membaca file: {$filePath}");
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('MODE DRY-RUN — tidak ada data yang disimpan.');
        }

        // ── Read header ───────────────────────────────────────────────────────
        $rawHeader = fgetcsv($handle);
        if (! $rawHeader) {
            $this->error('File CSV kosong atau header tidak terbaca.');
            fclose($handle);
            return self::FAILURE;
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $rawHeader);
        $colIndex = $this->buildColumnIndex($header);

        if (! isset($colIndex['appsheet_id'])) {
            $this->error('Kolom appsheet_id (atau "MP ID") tidak ditemukan di header CSV.');
            $this->line('Header yang terdeteksi: ' . implode(', ', $header));
            fclose($handle);
            return self::FAILURE;
        }

        if (! isset($colIndex['name'])) {
            $this->error('Kolom name (atau "Nama") tidak ditemukan di header CSV.');
            fclose($handle);
            return self::FAILURE;
        }

        $this->info("Header dikenali: " . implode(', ', array_keys($colIndex)));

        // ── Process rows ──────────────────────────────────────────────────────
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $errors = [];
        $rowNum = 1;

        $this->getOutput()->progressStart();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $this->getOutput()->progressAdvance();

            // Map CSV columns to field names
            $fields = $this->mapRow($row, $colIndex);

            if (blank($fields['appsheet_id'] ?? null)) {
                $errors[] = "Baris {$rowNum}: appsheet_id kosong — dilewati.";
                $stats['skipped']++;
                continue;
            }

            if (blank($fields['name'] ?? null)) {
                $errors[] = "Baris {$rowNum}: name kosong (appsheet_id={$fields['appsheet_id']}) — dilewati.";
                $stats['skipped']++;
                continue;
            }

            // ── Resolve depot_id ──────────────────────────────────────────────
            $depotId = $overrideDepotId
                ?? (isset($fields['depot_id']) && $fields['depot_id'] ? (int) $fields['depot_id'] : null);

            // Try depot_code lookup if depot_id not available
            if (! $depotId && isset($fields['depot_code']) && $fields['depot_code']) {
                $depotId = Depot::where('code', $fields['depot_code'])->value('id');
                if (! $depotId) {
                    $errors[] = "Baris {$rowNum}: depot_code '{$fields['depot_code']}' tidak ditemukan (appsheet_id={$fields['appsheet_id']}) — dilewati.";
                    $stats['skipped']++;
                    continue;
                }
            }

            if (! $depotId) {
                $errors[] = "Baris {$rowNum}: depot_id tidak tersedia (appsheet_id={$fields['appsheet_id']}) — gunakan --depot=N atau isi kolom depot_id/depot_code di CSV.";
                $stats['skipped']++;
                continue;
            }

            $depot = Depot::find($depotId);
            if (! $depot) {
                $errors[] = "Baris {$rowNum}: depot_id={$depotId} tidak ditemukan di database (appsheet_id={$fields['appsheet_id']}) — dilewati.";
                $stats['skipped']++;
                continue;
            }

            // ── Resolve branch_id ─────────────────────────────────────────────
            $branchId = isset($fields['branch_id']) && $fields['branch_id']
                ? (int) $fields['branch_id']
                : $depot->branch_id;

            // ── Prepare payload ───────────────────────────────────────────────
            $payload = [
                'appsheet_id'    => (string) $fields['appsheet_id'],
                'name'           => trim($fields['name']),
                'phone'          => isset($fields['phone']) && $fields['phone'] ? trim($fields['phone']) : null,
                'depot_id'       => $depotId,
                'branch_id'      => $branchId,
                'domain'         => $fields['domain'] ?? $defaultDomain,
                'active'         => $this->parseBool($fields['active'] ?? null, true),
            ];

            // ── Check existing ────────────────────────────────────────────────
            $existing = Manpower::where('appsheet_id', $payload['appsheet_id'])->first();

            if ($existing && ! $doUpdate) {
                $stats['skipped']++;
                continue;
            }

            // ── Dry-run preview ───────────────────────────────────────────────
            if ($isDryRun) {
                $action = $existing ? 'UPDATE' : 'CREATE';
                $this->line("  [{$action}] appsheet_id={$payload['appsheet_id']} name={$payload['name']} depot={$depotId}");
                $existing ? $stats['updated']++ : $stats['created']++;
                continue;
            }

            // ── Write to DB ───────────────────────────────────────────────────
            try {
                Manpower::updateOrCreate(
                    ['appsheet_id' => $payload['appsheet_id']],
                    $payload
                );

                $existing ? $stats['updated']++ : $stats['created']++;

                Log::info('[MANPOWER_IMPORT] Record upserted.', [
                    'appsheet_id' => $payload['appsheet_id'],
                    'name'        => $payload['name'],
                    'depot_id'    => $depotId,
                    'action'      => $existing ? 'updated' : 'created',
                ]);
            } catch (\Exception $e) {
                $errors[] = "Baris {$rowNum}: {$e->getMessage()} (appsheet_id={$payload['appsheet_id']})";
                $stats['errors']++;
            }
        }

        $this->getOutput()->progressFinish();
        fclose($handle);

        // ── Summary ───────────────────────────────────────────────────────────
        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Dibuat',   $stats['created']],
                ['Diupdate', $stats['updated']],
                ['Dilewati', $stats['skipped']],
                ['Error',    $stats['errors']],
                ['Total baris (data)', $rowNum - 1],
            ]
        );

        if ($errors) {
            $this->newLine();
            $this->warn('Pesan dari baris yang dilewati/error:');
            foreach (array_slice($errors, 0, 20) as $err) {
                $this->line("  • {$err}");
            }
            if (count($errors) > 20) {
                $this->line('  ... dan ' . (count($errors) - 20) . ' pesan lainnya.');
            }
        }

        if ($isDryRun) {
            $this->warn('DRY-RUN selesai — tidak ada data yang disimpan. Jalankan tanpa --dry-run untuk simpan.');
        } else {
            $this->info('Import selesai.');
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a map of field_name → csv_column_index based on the header row.
     */
    private function buildColumnIndex(array $header): array
    {
        $index = [];

        foreach ($header as $i => $h) {
            $normalized = strtolower(trim($h));
            if (isset(self::COL_MAP[$normalized])) {
                $fieldName = self::COL_MAP[$normalized];
                // First occurrence wins
                if (! isset($index[$fieldName])) {
                    $index[$fieldName] = $i;
                }
            }
        }

        return $index;
    }

    /**
     * Map a CSV data row into a field => value array using the column index.
     */
    private function mapRow(array $row, array $colIndex): array
    {
        $result = [];
        foreach ($colIndex as $field => $colNum) {
            $result[$field] = isset($row[$colNum]) ? trim($row[$colNum]) : null;
        }
        return $result;
    }

    /**
     * Parse boolean-ish CSV values.
     */
    private function parseBool(?string $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return ! in_array(strtolower($value), ['0', 'false', 'no', 'tidak', 'nonaktif'], true);
    }
}

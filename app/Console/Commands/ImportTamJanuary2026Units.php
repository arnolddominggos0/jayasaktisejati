<?php

namespace App\Console\Commands;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\Unit;
use App\Models\Vessel;
use App\Models\Voyage;
use App\Services\ShipmentKpiEvaluator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTamJanuary2026Units extends Command
{
    protected $signature = 'import:tam-january-2026-units
                            {--dry-run : Validasi tanpa menyimpan ke database}';

    protected $description = 'Import TAM January 2026 Units dari JSON';

    // Keyword dalam Nama Kapal → vessel_id di DB
    private const VESSEL_MAP = [
        'TANTO SEJAHTERA' => 5,
        'TANTO TANGGUH'   => 4,
        'TANTO SALAM'     => 3,
        'TANTO JAYA'      => 1,
    ];

    public function handle(): int
    {
        $path = storage_path('imports/tam/january2026/units.json');

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        $rows = json_decode(file_get_contents($path), true);

        if (! is_array($rows)) {
            $this->error('File JSON tidak valid atau tidak bisa di-parse.');
            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');

        // ─── Phase 1: Mapping & Validasi ────────────────────────────────────

        $valid        = [];
        $duplicates   = [];   // duplikat chassis (dalam file atau DB)
        $invalidDates = [];   // urutan tanggal tidak valid
        $seenChassis  = [];

        foreach ($rows as $i => $row) {
            $lineNo  = $i + 1;
            $chassis = trim($row['No Rangka'] ?? '');

            if ($chassis === '') {
                continue;
            }

            // ── Konversi tanggal ──────────────────────────────────────────
            try {
                $portIn = $this->parseDate($row['Tanggal Masuk Pelabuhan'] ?? '');
                $atd    = $this->parseDate($row['Tanggal Kapal Berangkat (ATD)'] ?? '');
                $ata    = $this->parseDate($row['Tanggal Kapal Sandar (ATA)'] ?? '');
                $rvdc   = $this->parseDate(
                    $row['Tanggal Masuk RVDC/Cabang Tujuan']
                    ?? $row['Tanggal Masuk RVDC\/Cabang Tujuan']
                    ?? ''
                );
            } catch (\Exception $e) {
                $invalidDates[] = [
                    'line'    => $lineNo,
                    'chassis' => $chassis,
                    'reason'  => 'Parse error: ' . $e->getMessage(),
                ];
                continue;
            }

            // ── Validasi urutan tanggal ──────────────────────────────────
            $orderErrors = $this->validateDateOrder($portIn, $atd, $ata, $rvdc);
            if (! empty($orderErrors)) {
                $invalidDates[] = [
                    'line'    => $lineNo,
                    'chassis' => $chassis,
                    'reason'  => implode('; ', $orderErrors),
                ];
                continue;
            }

            // ── Duplikat dalam file ──────────────────────────────────────
            if (isset($seenChassis[$chassis])) {
                $duplicates[] = [
                    'line'    => $lineNo,
                    'chassis' => $chassis,
                    'reason'  => "Duplikat dalam file (baris {$seenChassis[$chassis]})",
                ];
                continue;
            }
            $seenChassis[$chassis] = $lineNo;

            $vesselInfo = $this->parseVesselName($row['Nama Kapal'] ?? '');

            $valid[] = [
                'line'        => $lineNo,
                'chassis'     => $chassis,
                'engine'      => trim($row['No Mesin'] ?? ''),
                'model'       => trim($row['Model'] ?? ''),
                'destination' => trim($row['Tujuan'] ?? 'Manado'),
                'vessel_raw'  => trim($row['Nama Kapal'] ?? ''),
                'vessel_id'   => $vesselInfo['vessel_id'],
                'voyage_no'   => $vesselInfo['voyage_no'],
                'port_in'     => $portIn,
                'atd'         => $atd,
                'ata'         => $ata,
                'rvdc'        => $rvdc,
            ];
        }

        // ── Duplikat terhadap DB ─────────────────────────────────────────────
        $chassisList  = array_column($valid, 'chassis');
        $dbDuplicates = Unit::whereIn('chassis_no', $chassisList)
            ->pluck('chassis_no')
            ->flip()
            ->all();

        $readyToImport = [];
        foreach ($valid as $entry) {
            if (isset($dbDuplicates[$entry['chassis']])) {
                $duplicates[] = [
                    'line'    => $entry['line'],
                    'chassis' => $entry['chassis'],
                    'reason'  => 'Sudah ada di database',
                ];
            } else {
                $readyToImport[] = $entry;
            }
        }

        // ─── Tampilkan ringkasan validasi ────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan>============================================</>');
        $this->line('<fg=cyan>  VALIDASI IMPORT UNIT TAM JANUARI 2026   </>');
        $this->line('<fg=cyan>============================================</>');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total baris di file',   count($rows)],
                ['Siap diimport',         count($readyToImport)],
                ['Duplikat chassis',       count($duplicates)],
                ['Tanggal tidak valid',   count($invalidDates)],
            ]
        );

        if (! empty($duplicates)) {
            $this->warn('Duplikat chassis:');
            foreach ($duplicates as $d) {
                $this->line("  Baris {$d['line']} | {$d['chassis']} → {$d['reason']}");
            }
        }

        if (! empty($invalidDates)) {
            $this->warn('Tanggal tidak valid:');
            foreach ($invalidDates as $d) {
                $this->line("  Baris {$d['line']} | {$d['chassis']} → {$d['reason']}");
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('[DRY-RUN] Tidak ada data yang disimpan.');
            return self::SUCCESS;
        }

        if (empty($readyToImport)) {
            $this->newLine();
            $this->warn('Tidak ada baris yang dapat diimport.');
            return self::SUCCESS;
        }

        // ─── Phase 2: Insert ─────────────────────────────────────────────────
        $successCount  = 0;
        $failCount     = 0;
        $failedRows    = [];
        $importedIds   = [];   // shipment_id untuk kalkulasi KPI
        $voyageCounters = [];

        DB::transaction(function () use (
            $readyToImport,
            &$successCount,
            &$failCount,
            &$failedRows,
            &$importedIds,
            &$voyageCounters
        ) {
            foreach ($readyToImport as $entry) {
                try {
                    $voyage = $this->findOrCreateVoyage(
                        $entry['vessel_id'],
                        $entry['voyage_no'],
                        $entry['vessel_raw'],
                        $entry['atd'],
                        $entry['ata']
                    );

                    $vKey = $entry['voyage_no'];
                    if (! isset($voyageCounters[$vKey])) {
                        $voyageCounters[$vKey] = Shipment::where('voyage_id', $voyage->id)->count();
                    }
                    $voyageCounters[$vKey]++;

                    $shipmentCode = sprintf(
                        'JAN2026V%sMND-%03d',
                        $entry['voyage_no'],
                        $voyageCounters[$vKey]
                    );

                    $shipment = Shipment::updateOrCreate(
                        ['code' => $shipmentCode],
                        [
                            'customer_id'    => 1,
                            'receiver_id'    => 2,
                            'branch_id'      => 1,
                            'mode'           => ShipmentMode::Sea->value,
                            'status'         => ShipmentStatus::Delivered->value,
                            'request_type'   => 'sppb_do',
                            'route_from'     => 'Jakarta',
                            'route_to'       => $entry['destination'],
                            'route_summary'  => 'Jakarta → ' . $entry['destination'],
                            'service_type'   => 'sea_freight',
                            'service_option' => 'fcl',
                            'cargo_type'     => 'vehicle',
                            'container_qty'  => 1,
                            'packages_total' => 1,
                            'vessel_name'    => $voyage->vessel->name ?? $entry['vessel_raw'],
                            'voyage'         => $voyage->voyage_no,
                            'voyage_id'      => $voyage->id,
                            'pol'            => 'Tanjung Priok',
                            'pod'            => 'Bitung',
                            'pol_id'         => 1,
                            'pod_id'         => 2,
                            'etd'            => Carbon::parse($entry['atd']),
                            'eta'            => Carbon::parse($entry['ata']),
                            'requested_at'   => Carbon::parse($entry['port_in']),
                            'delivered_at'   => Carbon::parse($entry['rvdc']),
                            'confirm_is_true' => true,
                            'notes'          => sprintf(
                                'VIN %s / Engine %s / Model %s',
                                $entry['chassis'],
                                $entry['engine'],
                                $entry['model']
                            ),
                        ]
                    );

                    Unit::updateOrCreate(
                        ['shipment_id' => $shipment->id],
                        [
                            'model_no'   => $entry['model'],
                            'chassis_no' => $entry['chassis'],
                            'engine_no'  => $entry['engine'],
                            'qty'        => 1,
                        ]
                    );

                    // Hapus tracks lama → buat ulang
                    $shipment->tracks()->delete();

                    foreach ($this->buildTracks($entry) as [$status, $date, $note]) {
                        ShipmentTrack::create([
                            'shipment_id' => $shipment->id,
                            'status'      => $status,
                            'tracked_at'  => Carbon::parse($date),
                            'note'        => $note,
                        ]);
                    }

                    $importedIds[] = $shipment->id;
                    $successCount++;

                    $this->line(sprintf(
                        '  <fg=green>✓</> %s  %-18s  %s',
                        $entry['chassis'],
                        $entry['model'],
                        $shipmentCode
                    ));

                } catch (\Exception $e) {
                    $failCount++;
                    $failedRows[] = $entry['chassis'];
                    $this->error("  ✗ {$entry['chassis']}: " . $e->getMessage());
                }
            }
        });

        // ─── Phase 3: Hitung Leadtime & Status (dari rules sistem) ───────────
        if (! empty($importedIds)) {
            $this->newLine();
            $this->line('<fg=cyan>========================================</>');
            $this->line('<fg=cyan>  LEADTIME & STATUS (Rule Sistem)       </>');
            $this->line('<fg=cyan>========================================</>');

            $evaluator = new ShipmentKpiEvaluator;
            $kpiRows   = [];

            $shipments = Shipment::with('tracks')
                ->whereIn('id', $importedIds)
                ->get();

            foreach ($shipments as $s) {
                $ms = $evaluator->getMilestoneTimes($s);
                $dw = $evaluator->diffDaysNullable($ms['pickup'], $ms['onboard']);
                $sa = $evaluator->diffDaysNullable($ms['onboard'], $ms['arrived']);
                $dr = $evaluator->diffDaysNullable($ms['arrived'], $ms['deliv']);
                $tt = ($dw !== null && $sa !== null && $dr !== null)
                    ? $dw + $sa + $dr
                    : null;

                $t = $evaluator->getManadoThresholds();

                $statusDw = $this->kpiStatus($dw, $t['dwelling_days'] ?? null);
                $statusSa = $this->kpiStatus($sa, $t['sailing_days']  ?? null);
                $statusDr = $this->kpiStatus($dr, $t['dooring_days']  ?? null);
                $statusTt = $this->kpiStatus($tt, $t['total_days']['normal'] ?? null);

                $unit = $s->units()->first();

                $kpiRows[] = [
                    $unit?->chassis_no ?? '-',
                    $unit?->model_no   ?? '-',
                    $dw !== null ? "{$dw}/{$t['dwelling_days']}" : '-',
                    $sa !== null ? "{$sa}/{$t['sailing_days']}"  : '-',
                    $dr !== null ? "{$dr}/{$t['dooring_days']}"  : '-',
                    $tt !== null ? "{$tt}/{$t['total_days']['normal']}" : '-',
                    $statusTt,
                ];
            }

            $this->table(
                ['Chassis', 'Model', 'Dwelling', 'Sailing', 'Dooring', 'Total', 'Status'],
                $kpiRows
            );
        }

        // ─── Phase 4: Summary Akhir ──────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan>============================</>');
        $this->line('<fg=cyan>  SUMMARY IMPORT            </>');
        $this->line('<fg=cyan>============================</>');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total baris di file',   count($rows)],
                ['Berhasil diimport',     $successCount],
                ['Duplikat chassis',      count($duplicates)],
                ['Tanggal tidak valid',   count($invalidDates)],
                ['Gagal import',          $failCount],
            ]
        );

        if ($failCount > 0) {
            $this->warn('Chassis gagal: ' . implode(', ', $failedRows));
        }

        $this->info('Import TAM January 2026 selesai.');

        return self::SUCCESS;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Parse tanggal dari format "07-Jan" (tanpa tahun) atau "07-Jan-2026".
     * Semua tanggal diasumsikan tahun 2026.
     */
    private function parseDate(string $val): ?string
    {
        $val = trim($val);

        if ($val === '' || $val === '-') {
            return null;
        }

        // "07-Jan" atau "07-Jan-2026"
        if (preg_match('/^(\d{1,2})-([A-Za-z]{3})(?:-(\d{4}))?$/', $val, $m)) {
            $year = $m[3] ?? '2026';
            return Carbon::createFromFormat('d-M-Y', "{$m[1]}-{$m[2]}-{$year}")
                ->format('Y-m-d');
        }

        // Fallback: Carbon parse umum
        return Carbon::parse($val)->format('Y-m-d');
    }

    /**
     * Validasi urutan: port_in ≤ atd ≤ ata ≤ rvdc.
     */
    private function validateDateOrder(
        ?string $portIn,
        ?string $atd,
        ?string $ata,
        ?string $rvdc
    ): array {
        $errors = [];

        if ($portIn && $atd && $portIn > $atd) {
            $errors[] = "port_in ({$portIn}) > atd ({$atd})";
        }
        if ($atd && $ata && $atd > $ata) {
            $errors[] = "atd ({$atd}) > ata ({$ata})";
        }
        if ($ata && $rvdc && $ata > $rvdc) {
            $errors[] = "ata ({$ata}) > rvdc ({$rvdc})";
        }

        return $errors;
    }

    /**
     * Ekstrak vessel_id dan voyage_no dari string "Nama Kapal".
     * Contoh: "KM. TANTO SEJAHTERA V. 149" → [vessel_id: 5, voyage_no: "149"]
     */
    private function parseVesselName(string $raw): array
    {
        $upper    = strtoupper($raw);
        $vesselId = null;

        foreach (self::VESSEL_MAP as $keyword => $id) {
            if (str_contains($upper, $keyword)) {
                $vesselId = $id;
                break;
            }
        }

        $voyageNo = '000';
        if (preg_match('/V[.\s]+(\d+)/i', $raw, $m)) {
            $voyageNo = ltrim($m[1], '0') ?: '0';
        }

        return [
            'vessel_id' => $vesselId,
            'voyage_no' => $voyageNo,
        ];
    }

    /**
     * Cari Voyage berdasarkan vessel_id + voyage_no.
     * Buat baru jika belum ada (auto-create dengan ETD/ETA dari data unit).
     */
    private function findOrCreateVoyage(
        ?int   $vesselId,
        string $voyageNo,
        string $vesselRaw,
        ?string $etd,
        ?string $eta
    ): Voyage {
        if (! $vesselId) {
            throw new \RuntimeException(
                "Vessel tidak dikenali dari nama: \"{$vesselRaw}\""
            );
        }

        $vessel = Vessel::findOrFail($vesselId);

        return Voyage::firstOrCreate(
            [
                'vessel_id'  => $vesselId,
                'voyage_no'  => $voyageNo,
            ],
            [
                'shipping_line_id' => $vessel->shipping_line_id,
                'pol_id'           => 1,
                'pod_id'           => 2,
                'period_month'     => '2026-01-01',
                'etd'              => $etd ? Carbon::parse($etd) : null,
                'eta'              => $eta ? Carbon::parse($eta) : null,
            ]
        );
    }

    /**
     * Bangun array tracks untuk satu unit.
     * Mapping field JSON → TrackStatus → milestone leadtime:
     *   port_in  → DeliveryToPort  (pickup milestone)
     *   atd      → VesselDepart    (onboard milestone)
     *   ata      → VesselArrival   (arrived milestone)
     *   rvdc     → Delivered       (deliv milestone)
     */
    private function buildTracks(array $entry): array
    {
        return array_filter([
            $entry['port_in'] ? [TrackStatus::DeliveryToPort->value, $entry['port_in'], 'Import TAM Jan 2026'] : null,
            $entry['atd']     ? [TrackStatus::VesselDepart->value,   $entry['atd'],     'Import TAM Jan 2026'] : null,
            $entry['ata']     ? [TrackStatus::VesselArrival->value,  $entry['ata'],     'Import TAM Jan 2026'] : null,
            $entry['rvdc']    ? [TrackStatus::Delivered->value,      $entry['rvdc'],    'Import TAM Jan 2026'] : null,
        ]);
    }

    /**
     * Evaluasi OK / NG / PENDING berdasarkan nilai aktual vs threshold.
     */
    private function kpiStatus(?int $actual, ?int $limit): string
    {
        if ($actual === null) {
            return 'PENDING';
        }

        return $actual <= $limit ? 'OK' : 'NG';
    }
}

<?php

namespace App\Services;

use App\Enums\DeliveryScope;
use App\Enums\RequestType;
use App\Models\City;
use App\Models\Customer;
use App\Support\Intake\IntakePrefill;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class SppbAssistService
{
    protected float $confidenceThreshold = 0.70;


    protected const HEADER_LABELS = [
        'doc_number' => [
            'no\.?\s*(sppb|dokumen|surat|do)',
            'nomor',
            'no\.',
            'number',
        ],
        'requested_at' => [
            'tanggal',
            'date',
            'tgl',
        ],
        'sender' => [
            'pengirim',
            'dari',
            'sender',
            'dari\s*:',
        ],
        'receiver' => [
            'penerima',
            'kepada',
            'receiver',
            'to\s*:',
            'tujuan',
        ],
        'destination' => [
            'tujuan',
            'destination',
            'ke\s*:',
            'kota',
        ],
        'delivery_scope' => [
            'syarat\s*kirim', // OCR-01E — label yang dipakai SPPB Hasjrat
            'coverage',
            'cakupan',
            'layanan',
            'service',
            'scope',
        ],
        'pickup_location' => [ // OCR-01E — lokasi jemput unit
            'lokasi\s*unit',
            'lokasi\s*pickup',
            'lokasi\s*penjemputan',
        ],
        'notes' => [
            'catatan',
            'keterangan',
            'note',
            'remark',
            'urgent',
            'instructions',
        ],
    ];


    public function extract(array|string $filePaths): IntakePrefill
    {
        $paths = (array) $filePaths;
        $validPaths = array_values(array_filter($paths, fn ($p) => ! empty($p)));

        if (empty($validPaths)) {
            Log::info('SPPB AUDIT extract() SKIP', [
                'stage' => 'extract',
                'reason' => 'no valid file paths',
            ]);
            return IntakePrefill::empty(RequestType::SPPB_DO->value);
        }

        $artifacts = $this->artifactNames($validPaths);

        $text = $this->extractTextFromFiles($validPaths);

        Log::info('SPPB AUDIT extract() text result', [
            'stage' => 'extract',
            'text_length' => strlen($text),
            'is_valid_text' => $this->isValidText($text),
        ]);

        if (! $this->isValidText($text)) {
            Log::info('SPPB AUDIT extract() STOP — extracted text is empty/invalid', [
                'stage' => 'extract',
            ]);

            return new IntakePrefill(
                source: $this->sourceMeta($artifacts),
                document: ['number' => null, 'date' => null, 'confidence' => []],
                copyFields: [],
                manifest: ['detected_count' => 0, 'claimed_count' => null, 'units' => []],
                suggestions: [],
                warnings: [[
                    'code'    => 'document_unreadable',
                    'message' => 'Dokumen tidak dapat dibaca otomatis — lanjutkan pengisian manual.',
                ]],
            );
        }

        return $this->buildPrefillFromText($artifacts, $text);
    }

    /*
    |--------------------------------------------------------------------------
    | OCR-01E — DOMAIN EXTRACTION (Text → IntakePrefill)
    |
    | Ekstraksi dipecah per tanggung jawab domain:
    |   DocumentExtractor    → document.number / document.date
    |   PartyExtractor       → customer_text / receiver_text / pic / email
    |   ShipmentExtractor    → destination / pickup_location / scope / notes
    |   VoyageHintExtractor  → vessel_name / document_etd (hint, BUKAN field)
    |   ManifestExtractor    → units[] ber-anchor VIN + claimed_count
    | Resolusi entity (customer/receiver/city) tetap di layer suggestion.
    |--------------------------------------------------------------------------
    */

    /** Orkestrator: teks parser → envelope IntakePrefill lengkap. */
    protected function buildPrefillFromText(array $artifacts, string $text): IntakePrefill
    {
        $document    = $this->extractDocument($text);
        $parties     = $this->extractParties($text);
        $claims      = $this->extractShipmentClaims($text);
        $voyageHints = $this->extractVoyageHints($text);
        $manifest    = $this->extractManifest($text);
        $suggestions = $this->resolveSuggestions($parties, $claims);
        $copyFields  = $this->buildCopyFields($claims);

        $warnings = $this->collectWarnings($document, $copyFields, $suggestions, $parties, $voyageHints, $manifest);

        // vin_invalid — ada baris indeks unit ("1.", "2.", …) yang tidak
        // menghasilkan row ber-VIN valid.
        $indexRows = preg_match_all('/^\s*\d+\.\s*$/m', $text);
        if ($indexRows > ($manifest['detected_count'] ?? 0)) {
            $warnings[] = [
                'code'    => 'vin_invalid',
                'message' => 'Sebagian baris unit tidak memiliki VIN 17 karakter yang valid — periksa tabel unit.',
            ];
        }

        Log::info('SPPB AUDIT domain extraction', [
            'stage'          => 'domain-extraction',
            'document'       => $document['number'] !== null || $document['date'] !== null,
            'parties'        => array_keys(array_filter($parties)),
            'copy_fields'    => array_keys($copyFields),
            'voyage_hints'   => array_keys(array_filter($voyageHints)),
            'suggestions'    => array_keys($suggestions),
            'manifest_rows'  => $manifest['detected_count'],
            'claimed_count'  => $manifest['claimed_count'],
            'warning_codes'  => array_column($warnings, 'code'),
        ]);

        return new IntakePrefill(
            source: $this->sourceMeta($artifacts),
            document: $document,
            copyFields: $copyFields,
            manifest: $manifest,
            suggestions: $suggestions,
            warnings: $warnings,
            parties: $parties,
            voyageHints: $voyageHints,
        );
    }

    /** DocumentExtractor — identitas artefak. */
    protected function extractDocument(string $text): array
    {
        $number = $this->extractDocNumber($text);
        $date   = $this->extractDate($text);

        $confidence = [];
        if ($number !== null) {
            $confidence['number'] = 0.95;
        }
        if ($date !== null) {
            $confidence['date'] = 0.85;
        }

        return ['number' => $number, 'date' => $date, 'confidence' => $confidence];
    }

    /** PartyExtractor — klaim TEKS pihak; resolusi entity bukan di sini. */
    protected function extractParties(string $text): array
    {
        // Customer = badan usaha pertama di kepala dokumen (penerbit SPPB).
        $customer = null;
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text) ?: [])));
        foreach (array_slice($lines, 0, 6) as $line) {
            if (preg_match('/^(?:PT|CV|UD|TB)\.?\s+\S+/i', $line) && mb_strlen($line) <= 80) {
                $customer = $line;
                break;
            }
        }

        $pic = null;
        if (preg_match('/^\s*UP\s*[:\-]\s*(.+)$/mi', $text, $m)) {
            $pic = trim($m[1]);
            $pic = ($pic !== '' && mb_strlen($pic) <= 80) ? $pic : null;
        }

        $email = null;
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m)) {
            $email = strtolower($m[0]);
        }

        return [
            'customer_text' => $customer,
            'receiver_text' => $this->extractByLabel($text, self::HEADER_LABELS['receiver']),
            'pic_name'      => $pic,
            'email'         => $email,
        ];
    }

    /** ShipmentExtractor — klaim teks field administratif. */
    protected function extractShipmentClaims(string $text): array
    {
        $destination = $this->extractByLabel($text, self::HEADER_LABELS['destination']);

        return [
            'destination'           => $destination,
            'destination_city_hint' => $this->deriveCityHint($destination),
            'pickup_location'       => $this->extractByLabel($text, self::HEADER_LABELS['pickup_location']),
            'delivery_scope'        => $this->extractByLabel($text, self::HEADER_LABELS['delivery_scope']),
            'notes'                 => $this->extractByLabel($text, self::HEADER_LABELS['notes']),
        ];
    }

    /**
     * OCR-02B — turunkan hint KOTA dari teks tujuan secara GENERIK
     * (tanpa aturan per-kota): buang token prefiks badan usaha
     * (PT/CV/UD/TB) dan token singkatan pendek ber-kapital (≤3 huruf,
     * mis. "HA" = Hasjrat Abadi); sisa teks = kandidat nama kota.
     *
     *   "PT. HA KOTAMOBAGU" → "KOTAMOBAGU"
     *   "PT. HA MANADO"     → "MANADO"
     *   "PT. HASJRAT ABADI MANADO" → "HASJRAT ABADI MANADO"
     *     (multi-kata — lookup unik di sisi Apply yang memutuskan;
     *      gagal unik → dibiarkan kosong untuk admin)
     */
    protected function deriveCityHint(?string $destination): ?string
    {
        if ($destination === null || trim($destination) === '') {
            return null;
        }

        $tokens = preg_split('/\s+/', trim($destination)) ?: [];

        $remaining = array_values(array_filter($tokens, function (string $token): bool {
            if (preg_match('/^(?:PT|CV|UD|TB)\.?,?$/i', $token)) {
                return false; // prefiks badan usaha
            }
            if (preg_match('/^[A-Z]{1,3}\.?$/', $token)) {
                return false; // singkatan pendek (HA, HAB, dst.)
            }

            return true;
        }));

        $hint = trim(implode(' ', $remaining), " \t.,:-");

        return ($hint !== '' && mb_strlen($hint) >= 3) ? $hint : null;
    }

    /**
     * VoyageHintExtractor — hint pencocokan voyage untuk Review UI.
     * TIDAK PERNAH mengisi field Shipment: jadwal milik Voyage (frozen rule).
     */
    protected function extractVoyageHints(string $text): array
    {
        $vessel = null;
        if (preg_match('/nama\s*kapal\s*[:\-]?\s*(.+)$/mi', $text, $m)) {
            $candidate = trim($m[1]);
            $vessel = ($candidate !== '' && mb_strlen($candidate) <= 80) ? $candidate : null;
        }

        $etd = null;
        if (preg_match('/\bETD[^:\n\r]*[:\-]\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{4})/i', $text, $m)) {
            $etd = $this->extractDate($m[1]);
        }

        return ['vessel_name' => $vessel, 'document_etd' => $etd];
    }

    /**
     * ManifestExtractor — tabel unit ber-anchor VIN 17 karakter (tanpa I/O/Q).
     * Tidak bergantung posisi kolom: mendukung layout satu-field-per-baris
     * (output parser saat ini) dan baris inline (fallback spasi berbeda).
     */
    protected function extractManifest(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $text) ?: []),
            fn ($l) => $l !== ''
        ));

        $units = [];

        foreach ($lines as $i => $line) {
            if (! preg_match('/\b([A-HJ-NPR-Z0-9]{17})\b/', $line, $m)) {
                continue;
            }

            $vin = $m[1];
            if (! preg_match('/\d/', $vin)) {
                continue; // hindari "kata" 17 huruf tanpa digit
            }

            $unit = ($line === $vin)
                ? $this->manifestRowFromBlock($lines, $i, $vin)
                : $this->manifestRowFromInline($line, $vin);

            if ($unit !== null) {
                $units[] = $unit;
            }
        }

        $claimed = null;
        if (preg_match('/\bTotal\s*(?:Unit)?\s*[:\-]?\s*(\d{1,4})\b/i', $text, $m)) {
            $claimed = (int) $m[1];
        }

        return [
            'detected_count' => count($units),
            'claimed_count'  => $claimed,
            'units'          => $units,
        ];
    }

    /** Row manifest dari layout satu-field-per-baris (VIN berdiri sendiri). */
    protected function manifestRowFromBlock(array $lines, int $vinIndex, string $vin): ?array
    {
        $reg   = $lines[$vinIndex - 1] ?? null;
        $model = $lines[$vinIndex - 2] ?? null;

        // Baris indeks unit ("1.") bukan model/reg.
        if ($model !== null && preg_match('/^\d+\.?$/', $model)) {
            $model = null;
        }
        if ($reg !== null && preg_match('/^\d+\.?$/', $reg)) {
            $reg = null;
        }
        // Reg selalu mengandung digit; jika tidak, baris itu sebenarnya model.
        if ($reg !== null && ! preg_match('/\d/', $reg)) {
            $model = $reg;
            $reg   = null;
        }

        $engine = $lines[$vinIndex + 1] ?? null;
        if ($engine !== null && (! preg_match('/\d/', $engine) || mb_strlen($engine) > 20)) {
            $engine = null;
        }

        $color = $lines[$vinIndex + 2] ?? null;
        if ($color !== null && ! preg_match('/^[A-Z][A-Z ]{2,30}$/', $color)) {
            $color = null;
        }

        $do = $lines[$vinIndex + 3] ?? null;
        if ($do !== null && ! str_contains($do, '/')) {
            $do = null;
        }

        $qtyLine = $lines[$vinIndex + 4] ?? null;
        $qty = ($qtyLine !== null && preg_match('/^\d{1,3}$/', $qtyLine)) ? (int) $qtyLine : 1;

        return [
            'model'     => $model,
            'vin'       => $vin,
            'reg_no'    => $reg,
            'engine'    => $engine,
            'color'     => $color,
            'do_number' => $do,
            'qty'       => $qty,
        ];
    }

    /** Row manifest dari satu baris inline (fallback — tahan spasi berbeda). */
    protected function manifestRowFromInline(string $line, string $vin): ?array
    {
        $parts = preg_split('/\s+/', trim($line)) ?: [];
        $vinIdx = array_search($vin, $parts, true);
        if ($vinIdx === false) {
            return null;
        }

        $before = array_slice($parts, 0, $vinIdx);
        $after  = array_values(array_slice($parts, $vinIdx + 1));

        if (isset($before[0]) && preg_match('/^\d+\.?$/', $before[0])) {
            array_shift($before); // buang nomor urut
        }

        $reg = null;
        if ($before !== [] && preg_match('/\d/', (string) end($before))) {
            $reg = array_pop($before);
        }
        $model = $before !== [] ? implode(' ', $before) : null;

        $engine = $after[0] ?? null;

        $do = null;
        $doIdx = null;
        foreach ($after as $idx => $tok) {
            if ($idx === 0) {
                continue;
            }
            if (str_contains($tok, '/')) {
                $do = $tok;
                $doIdx = $idx;
                break;
            }
        }

        $qty = 1;
        if ($doIdx !== null && isset($after[$doIdx + 1]) && preg_match('/^\d{1,3}$/', $after[$doIdx + 1])) {
            $qty = (int) $after[$doIdx + 1];
        }

        $colorTokens = $doIdx !== null ? array_slice($after, 1, $doIdx - 1) : array_slice($after, 1);
        $color = $colorTokens !== [] ? implode(' ', $colorTokens) : null;

        return [
            'model'     => $model,
            'vin'       => $vin,
            'reg_no'    => $reg,
            'engine'    => $engine,
            'color'     => $color,
            'do_number' => $do,
            'qty'       => $qty,
        ];
    }

    /** Layer suggestion (existing matchers) — resolusi teks → master data. */
    protected function resolveSuggestions(array $parties, array $claims): array
    {
        $suggestions = [];

        if ($parties['customer_text'] !== null && ($match = $this->matchCustomer($parties['customer_text'])) !== null) {
            $suggestions['customer_id'] = $match;
        }

        if ($parties['receiver_text'] !== null && ($match = $this->matchReceiver($parties['receiver_text'])) !== null) {
            $suggestions['receiver_id'] = $match;
        }

        if ($claims['destination'] !== null && ($match = $this->matchCity($claims['destination'])) !== null) {
            $suggestions['destination_city_id'] = $match;
        }

        return $suggestions;
    }

    /** Klaim scalar yang siap di-Apply (OCR-03) — nilai teks/enum. */
    protected function buildCopyFields(array $claims): array
    {
        $copy = [];

        if ($claims['destination'] !== null) {
            $copy['destination'] = ['value' => $claims['destination'], 'confidence' => 0.75];
        }
        if (($claims['destination_city_hint'] ?? null) !== null) {
            // OCR-02B — hint kota untuk lookup Master City di sisi Apply.
            $copy['destination_city_hint'] = ['value' => $claims['destination_city_hint'], 'confidence' => 0.70];
        }
        if ($claims['pickup_location'] !== null) {
            $copy['pickup_location'] = ['value' => $claims['pickup_location'], 'confidence' => 0.75];
        }
        if ($claims['notes'] !== null) {
            $copy['notes'] = ['value' => $claims['notes'], 'confidence' => 0.75];
        }
        if ($claims['delivery_scope'] !== null && ($scope = $this->matchDeliveryScope($claims['delivery_scope'])) !== null) {
            $copy['delivery_scope'] = $scope;
        }

        return $copy;
    }

    /** Warnings OCR-01E — gap jujur, bukan exception. */
    protected function collectWarnings(
        array $document,
        array $copyFields,
        array $suggestions,
        array $parties,
        array $voyageHints,
        array $manifest,
    ): array {
        $nothingDetected = $document['number'] === null
            && $document['date'] === null
            && $copyFields === []
            && $suggestions === []
            && array_filter($parties) === []
            && array_filter($voyageHints) === []
            && ($manifest['detected_count'] ?? 0) === 0;

        if ($nothingDetected) {
            return [[
                'code'    => 'no_fields_detected',
                'message' => 'Tidak ada field yang terdeteksi dari dokumen — isi formulir secara manual.',
            ]];
        }

        $warnings = [];

        if (! isset($copyFields['delivery_scope'])) {
            $warnings[] = [
                'code'    => 'delivery_scope_missing',
                'message' => 'Syarat kirim tidak terdeteksi dari dokumen — pilih cakupan layanan secara manual.',
            ];
        }

        if (($voyageHints['vessel_name'] ?? null) === null) {
            $warnings[] = [
                'code'    => 'vessel_not_found',
                'message' => 'Nama kapal tidak ditemukan di dokumen — pilih voyage tanpa hint.',
            ];
        }

        $detected = (int) ($manifest['detected_count'] ?? 0);
        $claimed  = $manifest['claimed_count'] ?? null;

        if ($detected === 0) {
            $warnings[] = [
                'code'    => 'manifest_empty',
                'message' => 'Tabel unit tidak terdeteksi — masukkan unit secara manual.',
            ];
        } else {
            if ($claimed !== null && (int) $claimed !== $detected) {
                $warnings[] = [
                    'code'    => 'unit_count_mismatch',
                    'message' => "Jumlah unit terdeteksi ({$detected}) berbeda dengan total di dokumen ({$claimed}) — periksa tabel unit.",
                ];
            }

            foreach ($manifest['units'] as $idx => $unit) {
                if (($unit['do_number'] ?? null) === null) {
                    $no = $idx + 1;
                    $warnings[] = [
                        'code'    => 'do_missing',
                        'message' => "Unit #{$no}: nomor DO tidak terbaca dari dokumen.",
                    ];
                }
            }
        }

        return $warnings;
    }

    protected function sourceMeta(array $artifacts): array
    {
        return [
            'channel'     => RequestType::SPPB_DO->value,
            'artifacts'   => $artifacts,
            'received_at' => now()->toIso8601String(),
        ];
    }

    protected function artifactNames(array $paths): array
    {
        return array_values(array_map(function ($p) {
            if (is_string($p)) {
                return $p;
            }
            if (is_object($p) && method_exists($p, 'getClientOriginalName')) {
                return (string) $p->getClientOriginalName();
            }
            return (string) $p;
        }, $paths));
    }

    protected function extractDocNumber(string $text): ?string
    {
        $patterns = [
            '/no\.?\s*(?:sppb|dokumen|surat|do)\s*[:\-]?\s*([A-Z0-9\/\-\.]{6,40})/i',
            '/nomor\s*[:\-]?\s*([A-Z0-9\/\-\.]{6,40})/i',
            '/\b(\d{2,4}\/[A-Z]{2,5}[-]?[A-Z]*\/\d{2}\/\d{4})\b/i',
            '/\b(\d{2,4}\/[A-Z]{2,5}[-]?[A-Z]*\/\d{2,4})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $val = trim($m[1]);
                if (mb_strlen($val) >= 5) {
                    return $val;
                }
            }
        }

        return null;
    }

    protected function extractDate(string $text): ?string
    {
        $patterns = [
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/',
            '/(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
                $day = str_pad($m[1][0], 2, '0', STR_PAD_LEFT);
                $month = $m[2][0];
                $year = $m[3][0];

                if (is_numeric($month)) {
                    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
                } else {
                    $monthMap = [
                        'januari' => '01', 'februari' => '02', 'maret' => '03',
                        'april' => '04', 'mei' => '05', 'juni' => '06',
                        'juli' => '07', 'agustus' => '08', 'september' => '09',
                        'oktober' => '10', 'november' => '11', 'desember' => '12',
                    ];
                    $month = $monthMap[strtolower($month)] ?? null;
                    if ($month === null) {
                        continue;
                    }
                }

                return "{$year}-{$month}-{$day}";
            }
        }

        return null;
    }

    protected function extractByLabel(string $text, array $labels): ?string
    {
        $lines = preg_split('/\r?\n/', $text);
        if ($lines === false) {
            return null;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            foreach ($labels as $label) {
                $pattern = '/(?:^|\s)(' . $label . ')\s*[:\-]?\s*(.+)$/iu';
                if (preg_match($pattern, $line, $m)) {
                    $value = trim($m[2]);
                    $value = trim($value, " \t\n\r\0\x0B:|-,");
                    if ($value !== '' && mb_strlen($value) >= 2 && mb_strlen($value) <= 200) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    protected function extractTextFromFiles(array $paths): string
    {
        $parts = [];

        foreach ($paths as $path) {
            $content = $this->getFileContent($path);
            if ($content === null) {
                Log::info('SPPB AUDIT extractTextFromFiles() SKIP', [
                    'stage' => 'extractTextFromFiles',
                    'path' => $path,
                    'reason' => 'file content is null (file not found or unreadable)',
                ]);
                continue;
            }

            $mime = $this->getMimeType($path);
            $parser = match (true) {
                str_contains($mime, 'text/') => 'text',
                str_contains($mime, 'application/pdf') => 'pdf',
                default => 'none',
            };

            $text = match (true) {
                str_contains($mime, 'text/') => $content,
                str_contains($mime, 'application/pdf') => $this->extractPdfText($content),
                default => '',
            };

            Log::info('SPPB AUDIT extractTextFromFiles() per-file', [
                'stage' => 'extractTextFromFiles',
                'path' => $path,
                'mime' => $mime,
                'parser' => $parser,
                'raw_content_length' => strlen($content),
                'extracted_text_length' => strlen($text),
                'extracted_text_preview' => mb_substr($text, 0, 500),
            ]);

            $logFile = 'sppb-extracted-text-' . now()->format('Ymd-His') . '-' . Str::slug(str_replace(['/', '\\'], '-', $path)) . '.txt';
            try {
                file_put_contents(storage_path('logs/' . $logFile), $text);
                Log::info('SPPB AUDIT extractTextFromFiles() full text saved', [
                    'stage' => 'extractTextFromFiles',
                    'path' => $path,
                    'log_file' => storage_path('logs/' . $logFile),
                    'text_length' => strlen($text),
                ]);
            } catch (\Throwable $e) {
                Log::info('SPPB AUDIT extractTextFromFiles() failed to save text', [
                    'stage' => 'extractTextFromFiles',
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($this->isValidText($text)) {
                $parts[] = $text;
            }
        }

        $combined = implode("\n", $parts);

        Log::info('SPPB AUDIT extractTextFromFiles() combined', [
            'stage' => 'extractTextFromFiles',
            'file_count' => count($paths),
            'combined_text_length' => strlen($combined),
        ]);

        return $combined;
    }
    
    protected function extractPdfText(string $pdfContent): string
    {
        $text = '';

        preg_match_all('/(?<!end)stream\r?\n?(.*?)endstream/s', $pdfContent, $streamMatches);
        $streams = $streamMatches[1];

        $inflatedStreams = 0;

        foreach ($streams as $stream) {
            if (function_exists('gzuncompress')) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed === false) {
                    $uncompressed = @gzinflate($stream);
                }
                if ($uncompressed !== false) {
                    $stream = $uncompressed;
                    $inflatedStreams++;
                }
            }

            preg_match_all(
                '/\(([^)]*)\)\s*Tj|\[(.*?)\]\s*TJ|\b(Td|TD|ET)\b|(T\*)/s',
                $stream,
                $tokens,
                PREG_SET_ORDER
            );

            foreach ($tokens as $token) {
                if ((isset($token[3]) && $token[3] !== '') || (isset($token[4]) && $token[4] !== '')) {
                    // Operator perpindahan baris → newline logis.
                    $text .= "\n";
                    continue;
                }

                if (isset($token[2]) && $token[2] !== '') {
                    // [..]TJ — gabung potongan string di dalam array.
                    preg_match_all('/\(([^)]*)\)/', $token[2], $inner);
                    foreach ($inner[1] as $str) {
                        $decoded = $this->decodePdfString($str);
                        if ($decoded !== '') {
                            $text .= $decoded . ' ';
                        }
                    }
                    continue;
                }

                $decoded = $this->decodePdfString($token[1]);
                if ($decoded !== '') {
                    $text .= $decoded . ' ';
                }
            }
        }

        if (trim($text) === '') {
            preg_match_all('/\/Title\s*\(([^)]*)\)/', $pdfContent, $meta);
            foreach ($meta[1] as $str) {
                $text .= $this->decodePdfString($str) . "\n";
            }
        }

        // Rapikan: buang spasi di tepi baris, padatkan newline & spasi ganda.
        $text = preg_replace('/[ \t]*\n[ \t]*/', "\n", $text);
        $text = preg_replace('/\n{2,}/', "\n", $text);
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        $text = trim($text);

        // ── TEMPORARY — OCR-01D parser verification. HAPUS setelah stabil. ─
        $pageCount = preg_match_all('/\/Type\s*\/Page[^s]/', $pdfContent);
        Log::info('OCR-01D PARSER', [
            'parser'                  => 'lightweight-regex (built-in, boundary+line fix)',
            'pdf_bytes'               => strlen($pdfContent),
            'stream_count'            => count($streams),
            'inflated_streams'        => $inflatedStreams,
            'page_count'              => $pageCount,
            'text_length'             => strlen($text),
            'preview_first_300_chars' => mb_substr($text, 0, 300),
            'preview_last_300_chars'  => mb_substr($text, -300),
        ]);

        return $text;
    }

    protected function decodePdfString(string $str): string
    {
        $str = str_replace(
            ['\\n', '\\r', '\\t', '\\\\', '\\(', '\\)'],
            ["\n", "\r", "\t", '\\', '(', ')'],
            $str
        );

        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);

        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($str, 'UTF-8', 'ASCII,UTF-8,ISO-8859-1,Windows-1252');
            if ($converted !== false) {
                return $converted;
            }
        }

        return $str;
    }

    public function matchCustomer(?string $name): ?array
    {
        if (! $this->isValidText($name)) {
            return null;
        }

        $query = strtolower(trim($name));

        $customer = Customer::query()
            ->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
            ->select(['id', 'name'])
            ->first();

        if (! $customer) {
            $reverse = Customer::query()
                ->select(['id', 'name'])
                ->get()
                ->first(function ($c) use ($query) {
                    return str_contains(strtolower($c->name), $query);
                });

            if ($reverse) {
                $customer = $reverse;
            }
        }

        if (! $customer) {
            return null;
        }

        return [
            'value'      => $customer->id,
            'confidence' => 0.85,
            'match'      => $customer->name,
        ];
    }

    public function matchReceiver(?string $name): ?array
    {
        if (! $this->isValidText($name)) {
            return null;
        }

        $query = strtolower(trim($name));

        $customer = Customer::query()
            ->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
            ->select(['id', 'name'])
            ->first();

        if (! $customer) {
            $reverse = Customer::query()
                ->select(['id', 'name'])
                ->get()
                ->first(function ($c) use ($query) {
                    return str_contains(strtolower($c->name), $query);
                });

            if ($reverse) {
                $customer = $reverse;
            }
        }

        if (! $customer) {
            return null;
        }

        return [
            'value'      => $customer->id,
            'confidence' => 0.80,
            'match'      => $customer->name,
        ];
    }

    public function matchCity(?string $name): ?array
    {
        if (! $this->isValidText($name)) {
            return null;
        }

        $query = strtolower(trim($name));

        $exact = City::query()
            ->whereRaw('LOWER(name) = ?', [$query])
            ->where('is_active', true)
            ->select(['id', 'name'])
            ->first();

        if ($exact) {
            return [
                'value'      => $exact->id,
                'confidence' => 0.95,
                'match'      => $exact->name,
            ];
        }

        $city = City::query()
            ->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
            ->where('is_active', true)
            ->select(['id', 'name'])
            ->first();

        if ($city) {
            return [
                'value'      => $city->id,
                'confidence' => 0.85,
                'match'      => $city->name,
            ];
        }

        return null;
    }

    public function matchDeliveryScope(?string $text): ?array
    {
        if (! $this->isValidText($text)) {
            return null;
        }

        $lower = strtolower(trim($text));

        $map = [
            'door to door' => DeliveryScope::DoorToDoor->value,
            'd2d'          => DeliveryScope::DoorToDoor->value,
            'port to port' => DeliveryScope::PortToPort->value,
            'p2p'          => DeliveryScope::PortToPort->value,
            'door to port' => DeliveryScope::DoorToPort->value,
            'd2p'          => DeliveryScope::DoorToPort->value,
            'port to door' => DeliveryScope::PortToDoor->value,
            'p2d'          => DeliveryScope::PortToDoor->value,
        ];

        foreach ($map as $pattern => $scope) {
            if (str_contains($lower, $pattern)) {
                return [
                    'value'      => $scope,
                    'confidence' => 0.80,
                ];
            }
        }

        return null;
    }

    public function suggest(string $field, mixed $value, float $confidence = 0.75): array
    {
        return [$field => ['value' => $value, 'confidence' => $confidence]];
    }

    public function apply(array $suggestions, callable $get, callable $set): array
    {
        $filled = [];

        Log::info('SPPB AUDIT apply() entered', [
            'stage' => 'apply',
            'suggestion_count' => count($suggestions),
            'threshold' => $this->confidenceThreshold,
        ]);

        foreach ($suggestions as $field => $data) {
            $confidence = $data['confidence'] ?? 0;

            if ($confidence < $this->confidenceThreshold) {
                Log::info('SPPB AUDIT apply() field SKIP', [
                    'stage' => 'apply',
                    'field' => $field,
                    'disposition' => 'skipped_confidence',
                    'confidence' => $confidence,
                    'threshold' => $this->confidenceThreshold,
                    'value' => $data['value'] ?? null,
                ]);
                continue;
            }

            $current = $get($field);
            if (! empty($current)) {
                Log::info('SPPB AUDIT apply() field SKIP', [
                    'stage' => 'apply',
                    'field' => $field,
                    'disposition' => 'skipped_has_value',
                    'current_value' => $current,
                    'suggested_value' => $data['value'] ?? null,
                ]);
                continue;
            }

            $set($field, $data['value']);
            $filled[] = $field;

            Log::info('SPPB AUDIT apply() field APPLIED', [
                'stage' => 'apply',
                'field' => $field,
                'disposition' => 'applied',
                'value' => $data['value'] ?? null,
                'confidence' => $confidence,
            ]);
        }

        Log::info('SPPB AUDIT apply() done', [
            'stage' => 'apply',
            'filled_count' => count($filled),
            'filled' => $filled,
        ]);

        return $filled;
    }

    public function assist(array|string $filePaths): IntakePrefill
    {
        $paths = (array) $filePaths;
        $validPaths = array_filter($paths, fn ($p) => ! empty($p));

        Log::info('SPPB AUDIT assist() entered', [
            'stage' => 'assist',
            'file_count' => count($validPaths),
        ]);

        $prefill = $this->extract($filePaths);

        Log::info('SPPB AUDIT assist() extract() result', [
            'stage' => 'assist',
            'detected_field_count' => $prefill->detectedFieldCount(),
            'unit_count' => $prefill->unitCount(),
            'warning_count' => count($prefill->warnings),
        ]);

        return $prefill;
    }

    /**
     * OCR-01A — baca konten file dari semua bentuk yang mungkin dikirim wizard:
     * 1. Livewire TemporaryUploadedFile / objek upload lain (getRealPath + file_get_contents)
     * 2. Path relatif Storage disk 'public'
     * 3. Absolute path / string path biasa di filesystem
     * Mengembalikan null bila tidak terbaca. Logging: tipe, real path, mime,
     * ukuran, dan status keberhasilan baca.
     */
    public function getFileContent(mixed $file): ?string
    {
        // 1) Objek upload (TemporaryUploadedFile, UploadedFile, dsb.)
        if (is_object($file) && method_exists($file, 'getRealPath')) {
            $real    = $file->getRealPath();
            $content = (is_string($real) && $real !== '' && is_readable($real))
                ? @file_get_contents($real)
                : false;
            $via = 'getRealPath';

            // Fallback: file di livewire-tmp bisa saja tidak resolvable sebagai
            // real path lokal — coba pembacaan via storage milik objeknya.
            if ($content === false && method_exists($file, 'get')) {
                try {
                    $fallback = $file->get();
                    if (is_string($fallback)) {
                        $content = $fallback;
                        $via     = 'object->get()';
                    }
                } catch (\Throwable) {
                    // tetap false — dilog di bawah
                }
            }

            Log::info('SPPB AUDIT getFileContent()', [
                'stage'     => 'getFileContent',
                'type'      => get_class($file),
                'real_path' => $real,
                'via'       => $via,
                'mime'      => $this->getMimeType($file),
                'size'      => $content === false ? null : strlen($content),
                'readable'  => $content !== false,
            ]);

            return $content === false ? null : $content;
        }

        if (! is_string($file)) {
            Log::info('SPPB AUDIT getFileContent() UNSUPPORTED', [
                'stage' => 'getFileContent',
                'type'  => get_debug_type($file),
            ]);
            return null;
        }

        // 2) Path relatif pada Storage disk 'public'
        try {
            if (Storage::disk('public')->exists($file)) {
                $content = Storage::disk('public')->get($file);

                Log::info('SPPB AUDIT getFileContent()', [
                    'stage'     => 'getFileContent',
                    'type'      => 'storage_public_path',
                    'real_path' => Storage::disk('public')->path($file),
                    'mime'      => $this->getMimeType($file),
                    'size'      => strlen($content),
                    'readable'  => true,
                ]);

                return $content;
            }
        } catch (\Throwable) {
            // lanjut ke percobaan filesystem langsung
        }

        // 3) Absolute path / string path biasa
        if (is_file($file) && is_readable($file)) {
            $content = @file_get_contents($file);

            Log::info('SPPB AUDIT getFileContent()', [
                'stage'     => 'getFileContent',
                'type'      => 'filesystem_path',
                'real_path' => $file,
                'mime'      => $this->getMimeType($file),
                'size'      => $content === false ? null : strlen($content),
                'readable'  => $content !== false,
            ]);

            return $content === false ? null : $content;
        }

        Log::info('SPPB AUDIT getFileContent() UNREADABLE', [
            'stage'     => 'getFileContent',
            'type'      => 'string_path',
            'real_path' => $file,
            'readable'  => false,
        ]);

        return null;
    }

    /**
     * OCR-01A — deteksi mime mengikuti bentuk input yang sama dengan
     * getFileContent(): objek upload → getMimeType() bawaannya; storage
     * public path → Storage::mimeType(); absolute path → mime_content_type().
     */
    protected function getMimeType(mixed $file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getMimeType')) {
                return (string) ($file->getMimeType() ?? '');
            }

            if (! is_string($file)) {
                return '';
            }

            if (Storage::disk('public')->exists($file)) {
                return (string) (Storage::disk('public')->mimeType($file) ?: '');
            }

            if (is_file($file)) {
                return (string) (@mime_content_type($file) ?: '');
            }
        } catch (\Throwable) {
            // fall through
        }

        return '';
    }

    protected function isValidText(?string $text): bool
    {
        return $text !== null
            && trim($text) !== ''
            && mb_strlen(trim($text)) >= 2;
    }
}
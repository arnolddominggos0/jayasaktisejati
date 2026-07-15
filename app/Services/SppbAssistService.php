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

/**
 * SPPB Document Assist Service
 *
 * Processes uploaded SPPB/DO documents and produces an IntakePrefill —
 * the channel-neutral extraction envelope. Extraction is an ASSISTANT:
 * it never writes into form/Livewire state. Review (OCR-02) and Apply
 * (OCR-03) are separate, explicit steps owned by the Office Admin.
 *
 * Sprint 1.5: Infrastructure foundation (apply/assist pipeline, match helpers).
 * Sprint 1.6: Header extraction (doc_number, date, sender, receiver, destination, coverage, notes).
 *   - Lightweight PDF text-stream extraction (no external package).
 *   - Regex-based key-value pair parsing.
 *   - Match helpers resolve raw text to master data IDs.
 * Sprint OCR-01: extract()/assist() return IntakePrefill; apply() is no
 *   longer called automatically (kept for the explicit Apply in OCR-03).
 * Sprint 1.7 (future): Unit table extraction (model, VIN, engine, color, qty, DO, police).
 */
class SppbAssistService
{
    protected float $confidenceThreshold = 0.70;

    /**
     * Label keywords used to locate header fields in the document text.
     * Each field maps to an ordered list of possible label patterns.
     */
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
            'coverage',
            'cakupan',
            'layanan',
            'service',
            'scope',
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

    /**
     * Raw suggestion keys that are ENTITY RESOLUTIONS (name → master-data id).
     * These land in IntakePrefill->suggestions and are never auto-applied
     * as links — the admin confirms the match (intake architecture review §4).
     */
    protected const RESOLUTION_FIELDS = [
        'customer_id',
        'receiver_id',
        'destination_city_id',
    ];

    /**
     * Process uploaded SPPB/DO document(s) into an IntakePrefill envelope.
     *
     * OCR-01: this method no longer returns form fields — it returns the
     * intermediate extraction result. Nothing here touches Livewire state.
     *
     * @param array|string $filePaths  File path(s) relative to the upload disk.
     */
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
                manifest: ['detected_count' => 0, 'units' => []],
                suggestions: [],
                warnings: [[
                    'code'    => 'document_unreadable',
                    'message' => 'Dokumen tidak dapat dibaca otomatis — lanjutkan pengisian manual.',
                ]],
            );
        }

        $raw = $this->extractSuggestionsFromText($text);

        return $this->buildPrefill($artifacts, $raw);
    }

    /**
     * Classify raw suggestions into the envelope's field species.
     * doc_number/requested_at → document; RESOLUTION_FIELDS → suggestions;
     * everything else (scalar values) → copyFields.
     */
    protected function buildPrefill(array $artifacts, array $raw): IntakePrefill
    {
        $document    = ['number' => null, 'date' => null, 'confidence' => []];
        $copyFields  = [];
        $suggestions = [];

        foreach ($raw as $field => $data) {
            if ($field === 'doc_number') {
                $document['number']               = $data['value'];
                $document['confidence']['number'] = $data['confidence'];
                continue;
            }

            if ($field === 'requested_at') {
                $document['date']               = $data['value'];
                $document['confidence']['date'] = $data['confidence'];
                continue;
            }

            if (in_array($field, self::RESOLUTION_FIELDS, true)) {
                $suggestions[$field] = [
                    'value'      => $data['value'],
                    'confidence' => $data['confidence'],
                    'match'      => $data['match'] ?? null,
                ];
                continue;
            }

            $copyFields[$field] = [
                'value'      => $data['value'],
                'confidence' => $data['confidence'],
            ];
        }

        $warnings = [];

        $nothingDetected = $document['number'] === null
            && $document['date'] === null
            && $copyFields === []
            && $suggestions === [];

        if ($nothingDetected) {
            $warnings[] = [
                'code'    => 'no_fields_detected',
                'message' => 'Tidak ada field yang terdeteksi dari dokumen — isi formulir secara manual.',
            ];
        } else {
            // Unit-table extraction belum tersedia (Sprint 1.7) — nyatakan
            // jujur supaya admin tahu manifest tetap diinput manual.
            $warnings[] = [
                'code'    => 'manifest_not_extracted',
                'message' => 'Tabel unit belum terbaca otomatis — masukkan unit secara manual.',
            ];
        }

        return new IntakePrefill(
            source: $this->sourceMeta($artifacts),
            document: $document,
            copyFields: $copyFields,
            manifest: ['detected_count' => 0, 'units' => []],
            suggestions: $suggestions,
            warnings: $warnings,
        );
    }

    /** Source block for the envelope (channel + artifact names). */
    protected function sourceMeta(array $artifacts): array
    {
        return [
            'channel'     => RequestType::SPPB_DO->value,
            'artifacts'   => $artifacts,
            'received_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Normalize upload state entries to serializable artifact names —
     * the envelope crosses the Livewire wire, so objects must not leak in.
     */
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

    /**
     * Regex + match phases over already-extracted text. Body unchanged from
     * the pre-OCR-01 extract() — same patterns, same confidences, same logs.
     *
     * @return array  ['field_name' => ['value' => mixed, 'confidence' => float, ...], ...]
     */
    protected function extractSuggestionsFromText(string $text): array
    {
        $suggestions = [];

        // --- Regex phase (Task 4) ---

        $docNumber = $this->extractDocNumber($text);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'doc_number',
            'result' => $docNumber,
        ]);
        if ($docNumber !== null) {
            $suggestions['doc_number'] = ['value' => $docNumber, 'confidence' => 0.95];
        }

        $date = $this->extractDate($text);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'date',
            'result' => $date,
        ]);
        if ($date !== null) {
            $suggestions['requested_at'] = ['value' => $date, 'confidence' => 0.85];
        }

        $senderName = $this->extractByLabel($text, self::HEADER_LABELS['sender']);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'sender',
            'result' => $senderName,
        ]);

        $receiverName = $this->extractByLabel($text, self::HEADER_LABELS['receiver']);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'receiver',
            'result' => $receiverName,
        ]);

        $destName = $this->extractByLabel($text, self::HEADER_LABELS['destination']);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'destination',
            'result' => $destName,
        ]);

        $scopeText = $this->extractByLabel($text, self::HEADER_LABELS['delivery_scope']);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'coverage',
            'result' => $scopeText,
        ]);

        $notesText = $this->extractByLabel($text, self::HEADER_LABELS['notes']);
        Log::info('SPPB AUDIT extract() regex', [
            'stage' => 'extract(regex)',
            'field' => 'notes',
            'result' => $notesText,
        ]);

        // --- Match phase (Task 5) ---

        if ($senderName !== null) {
            $match = $this->matchCustomer($senderName);
            Log::info('SPPB AUDIT extract() match', [
                'stage' => 'extract(match)',
                'field' => 'customer',
                'input' => $senderName,
                'result' => $match,
            ]);
            if ($match !== null) {
                $suggestions['customer_id'] = $match;
            }
        }

        if ($receiverName !== null) {
            $match = $this->matchReceiver($receiverName);
            Log::info('SPPB AUDIT extract() match', [
                'stage' => 'extract(match)',
                'field' => 'receiver',
                'input' => $receiverName,
                'result' => $match,
            ]);
            if ($match !== null) {
                $suggestions['receiver_id'] = $match;
            }
        }

        if ($destName !== null) {
            $match = $this->matchCity($destName);
            Log::info('SPPB AUDIT extract() match', [
                'stage' => 'extract(match)',
                'field' => 'city',
                'input' => $destName,
                'result' => $match,
            ]);
            if ($match !== null) {
                $suggestions['destination_city_id'] = $match;
            }
        }

        if ($scopeText !== null) {
            $match = $this->matchDeliveryScope($scopeText);
            Log::info('SPPB AUDIT extract() match', [
                'stage' => 'extract(match)',
                'field' => 'coverage',
                'input' => $scopeText,
                'result' => $match,
            ]);
            if ($match !== null) {
                $suggestions['delivery_scope'] = $match;
            }
        }

        if ($notesText !== null) {
            $suggestions['notes'] = ['value' => $notesText, 'confidence' => 0.75];
        }

        Log::info('SPPB AUDIT extract() final suggestions', [
            'stage' => 'extract',
            'suggestion_count' => count($suggestions),
            'suggestions' => $suggestions,
        ]);

        return $suggestions;
    }

    /**
     * Extract document number (e.g., "0627/LOG-SBR/07/2026").
     * These follow a consistent pattern of digits + slashes + alpha codes + year.
     */
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

    /**
     * Extract a date in dd/mm/yyyy or dd-mm-yyyy format.
     */
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

    /**
     * Extract a value following any of the given label keywords.
     * Reads the line where the label appears and returns the remainder
     * after the label (and optional colon/dash separator).
     */
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

    /**
     * Read text content from all uploaded files.
     * Supports: plain text, PDF (lightweight text-stream extraction).
     * Images and other binaries return empty — future sprint adds OCR.
     */
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

    /**
     * Lightweight PDF text extraction.
     * Extracts text from PDF content streams using regex — no external package.
     * Handles uncompressed text streams (common in digitally-generated PDFs).
     */
    protected function extractPdfText(string $pdfContent): string
    {
        $text = '';

        $chunks = preg_split('/stream\s*/', $pdfContent);
        if ($chunks === false) {
            return '';
        }

        foreach ($chunks as $chunk) {
            $endPos = strpos($chunk, 'endstream');
            if ($endPos === false) {
                continue;
            }

            $stream = substr($chunk, 0, $endPos);

            if (function_exists('gzuncompress')) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed !== false) {
                    $stream = $uncompressed;
                }
            }

            preg_match_all('/\(([^)]*)\)\s*Tj/', $stream, $tjMatches);
            foreach ($tjMatches[1] as $str) {
                $decoded = $this->decodePdfString($str);
                if ($decoded !== '') {
                    $text .= $decoded . ' ';
                }
            }

            preg_match_all('/\[(.*?)\]\s*TJ/', $stream, $tjArrayMatches);
            foreach ($tjArrayMatches[1] as $arr) {
                preg_match_all('/\(([^)]*)\)/', $arr, $inner);
                foreach ($inner[1] as $str) {
                    $decoded = $this->decodePdfString($str);
                    if ($decoded !== '') {
                        $text .= $decoded . ' ';
                    }
                }
            }
        }

        if ($text === '') {
            preg_match_all('/\/Title\s*\(([^)]*)\)/', $pdfContent, $meta);
            foreach ($meta[1] as $str) {
                $text .= $this->decodePdfString($str) . "\n";
            }
        }

        return trim($text);
    }

    /**
     * Decode a PDF text string (handles escape sequences).
     */
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

    /**
     * Match an extracted customer/sender name against master data.
     */
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

    /**
     * Match an extracted receiver name against master data.
     * Receivers are Customer records linked via receiver_id FK.
     */
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

    /**
     * Match an extracted destination city name against active cities.
     */
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

    /**
     * Match delivery scope from text (e.g., "door to door", "port to port").
     */
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

    /**
     * Build a direct suggestion entry (for fields extracted with known values).
     */
    public function suggest(string $field, mixed $value, float $confidence = 0.75): array
    {
        return [$field => ['value' => $value, 'confidence' => $confidence]];
    }

    /**
     * Apply extracted suggestions to the form.
     *
     * OCR-01: NOT called automatically anymore. Retained for OCR-03, where
     * the Office Admin's explicit "Terapkan" action will invoke it with the
     * envelope's applicable fields. Do not wire this back into assist().
     *
     * Rules:
     * - Only fill fields with confidence >= threshold.
     * - Never overwrite fields that already have a value (respect admin's manual entry).
     * - Returns list of field names that were filled.
     *
     * @param array $suggestions
     * @param callable $get  Filament Get
     * @param callable $set  Filament Set
     * @return array  Field names that were auto-filled
     */
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

    /**
     * OCR-01 pipeline: extract from files → return IntakePrefill → (wait).
     *
     * Apply is NO LONGER automatic. The envelope is held by the Livewire
     * page ($livewire->intakePrefill) until the admin explicitly reviews
     * (OCR-02) and applies (OCR-03). This method never touches form state.
     */
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
     * Get the file content for parsing.
     * Returns raw content or null if file cannot be read.
     */
    public function getFileContent(string $path): ?string
    {
        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            return Storage::disk('public')->get($path);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the MIME type of a file.
     */
    protected function getMimeType(string $path): string
    {
        try {
            return Storage::disk('public')->mimeType($path);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Check if extracted text is valid for matching.
     */
    protected function isValidText(?string $text): bool
    {
        return $text !== null
            && trim($text) !== ''
            && mb_strlen(trim($text)) >= 2;
    }
}
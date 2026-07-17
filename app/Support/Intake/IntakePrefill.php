<?php

namespace App\Support\Intake;

use Livewire\Wireable;

/**
 * IntakePrefill — channel-neutral extraction envelope (Sprint OCR-01).
 *
 * The single boundary object between "what an intake artifact CLAIMS"
 * and "what the Office Admin ASSERTS into the Shipment form".
 *
 *   Upload → Extract → IntakePrefill → Review (OCR-02) → Apply (OCR-03) → Form
 *
 * Frozen architecture rules this object enforces:
 * - Extraction is an ASSISTANT, never a creator: nothing in this object
 *   touches Livewire/form state. Applying is a separate, explicit act.
 * - Channel-neutral: SPPB/OCR is only the first producer. Manual entry,
 *   Excel import, API, and Customer Portal must be able to produce this
 *   exact same shape. No producer-specific naming in the structure.
 * - Immutable: producers build it once; consumers only read it.
 *
 * Field species (see SPPB Intake architecture review):
 * - document:    identity of the source artifact (number, date).
 * - copyFields:  scalar values copied from the artifact (safe to prefill).
 * - manifest:    claimed cargo rows + claimed count (cardinality check!).
 * - suggestions: ENTITY RESOLUTION proposals (customer/receiver/city/...).
 *                Never auto-applied as links — admin confirms the match.
 * - warnings:    honest gaps (unreadable doc, partial reads, missing units).
 */
final class IntakePrefill implements Wireable
{
    /**
     * @param array{channel: string, artifacts: array<int, string>, received_at: ?string} $source
     * @param array{number: ?string, date: ?string, confidence: array<string, float>} $document
     * @param array<string, array{value: mixed, confidence: float}> $copyFields
     * @param array{detected_count: int, claimed_count: ?int, units: array<int, array<string, mixed>>} $manifest
     * @param array<string, array{value: mixed, confidence: float, match: ?string}> $suggestions
     * @param array<int, array{code: string, message: string}> $warnings
     * @param array{customer_text: ?string, receiver_text: ?string, pic_name: ?string, email: ?string} $parties
     *        OCR-01E — klaim TEKS pihak-pihak dari dokumen. Belum di-resolve
     *        ke entity; resolusi tetap milik blok suggestions.
     * @param array{vessel_name: ?string, document_etd: ?string} $voyageHints
     *        OCR-01E — hint pencocokan voyage. TIDAK PERNAH ditulis ke
     *        Shipment (jadwal milik Voyage); hanya konteks untuk Review UI.
     */
    public function __construct(
        public readonly array $source,
        public readonly array $document,
        public readonly array $copyFields,
        public readonly array $manifest,
        public readonly array $suggestions,
        public readonly array $warnings,
        public readonly array $parties = ['dealer_name' => null, 'customer_text' => null, 'receiver_text' => null, 'pic_name' => null, 'email' => null],
        public readonly array $voyageHints = ['vessel_name' => null, 'document_etd' => null],
    ) {
    }

    /**
     * Empty envelope — the manual-entry baseline. With an empty prefill the
     * wizard behaves exactly as if no extraction ever happened (OCR-01
     * backward-compatibility contract).
     */
    public static function empty(string $channel = 'manual', array $artifacts = []): self
    {
        return new self(
            source: [
                'channel'     => $channel,
                'artifacts'   => array_values($artifacts),
                // Plain PHP, bukan now(): DTO ini bebas dependensi framework
                // (dipakai juga di unit test tanpa boot aplikasi).
                'received_at' => date('c'),
            ],
            document: ['number' => null, 'date' => null, 'confidence' => []],
            copyFields: [],
            manifest: ['detected_count' => 0, 'claimed_count' => null, 'units' => []],
            suggestions: [],
            warnings: [],
        );
    }

    public function isEmpty(): bool
    {
        return $this->document['number'] === null
            && $this->document['date'] === null
            && $this->copyFields === []
            && ($this->manifest['detected_count'] ?? 0) === 0
            && $this->suggestions === []
            && array_filter($this->parties) === []
            && array_filter($this->voyageHints) === [];
    }

    public function detectedFieldCount(): int
    {
        return count($this->copyFields)
            + count($this->suggestions)
            + count(array_filter($this->parties, fn ($v) => $v !== null))
            + count(array_filter($this->voyageHints, fn ($v) => $v !== null))
            + ($this->document['number'] !== null ? 1 : 0)
            + ($this->document['date'] !== null ? 1 : 0);
    }

    public function claimedUnitCount(): ?int
    {
        $claimed = $this->manifest['claimed_count'] ?? null;

        return $claimed === null ? null : (int) $claimed;
    }

    public function unitCount(): int
    {
        return (int) ($this->manifest['detected_count'] ?? 0);
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /**
     * Suggestion accessor: ['value' => id, 'confidence' => float, 'match' => label]
     * or null when the field was not resolved.
     */
    public function suggestionFor(string $field): ?array
    {
        return $this->suggestions[$field] ?? null;
    }

    /**
     * Pre-computed data for the Review Summary (rendered in OCR-02 — no
     * second extraction needed). Each item:
     *   ['status' => 'detected'|'warning', 'label' => string]
     */
    public function summaryItems(): array
    {
        $items = [];

        if ($this->document['number'] !== null) {
            $items[] = ['status' => 'detected', 'label' => 'Nomor dokumen terbaca: ' . $this->document['number']];
        }

        if ($this->document['date'] !== null) {
            $items[] = ['status' => 'detected', 'label' => 'Tanggal dokumen terbaca: ' . $this->document['date']];
        }

        foreach ($this->suggestions as $field => $suggestion) {
            $label = $suggestion['match'] ?? (string) ($suggestion['value'] ?? '');
            $pct   = (int) round(($suggestion['confidence'] ?? 0) * 100);

            $items[] = [
                'status' => 'detected',
                'label'  => sprintf('%s terdeteksi: %s (%d%%)', self::fieldLabel($field), $label, $pct),
            ];
        }

        foreach ($this->copyFields as $field => $data) {
            $items[] = [
                'status' => 'detected',
                'label'  => self::fieldLabel($field) . ' terbaca dari dokumen',
            ];
        }

        // DOMAIN-02: kop dokumen = Dealer; customer_text di-skip agar tidak
        // duplikat dengan dealer_name (nilai sama, makna sudah diluruskan).
        foreach (['dealer_name' => 'Dealer (teks)', 'receiver_text' => 'Penerima (teks)', 'pic_name' => 'PIC', 'email' => 'Email'] as $key => $label) {
            if (($this->parties[$key] ?? null) !== null) {
                $items[] = ['status' => 'detected', 'label' => $label . ' terbaca: ' . $this->parties[$key]];
            }
        }

        if (($this->voyageHints['vessel_name'] ?? null) !== null) {
            $etd = $this->voyageHints['document_etd'] ?? null;
            $items[] = [
                'status' => 'detected',
                'label'  => 'Kapal disebut di dokumen: ' . $this->voyageHints['vessel_name']
                    . ($etd !== null ? " (ETD dokumen: {$etd})" : ''),
            ];
        }

        if ($this->unitCount() > 0) {
            $claimed = $this->claimedUnitCount();
            $items[] = [
                'status' => 'detected',
                'label'  => $this->unitCount() . ' unit terdeteksi'
                    . ($claimed !== null ? " (dokumen menyatakan total {$claimed})" : ''),
            ];
        }

        foreach ($this->warnings as $warning) {
            $items[] = ['status' => 'warning', 'label' => $warning['message']];
        }

        return $items;
    }

    /**
     * Human label for a form field key — presentation helper only,
     * intentionally generic (channel-neutral).
     */
    protected static function fieldLabel(string $field): string
    {
        return match ($field) {
            'customer_id'         => 'Customer',
            'dealer_id'           => 'Dealer',
            'receiver_id'         => 'Penerima',
            'destination_city_id' => 'Kota tujuan',
            'delivery_scope'      => 'Cakupan layanan',
            'notes'               => 'Catatan',
            'destination'           => 'Tujuan',
            'destination_city_hint' => 'Kota tujuan (hint)',
            'pickup_location'       => 'Lokasi jemput unit',
            default               => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    // ── Livewire round-trip (page holds $this->intakePrefill) ───────────────

    public function toLivewire(): array
    {
        return [
            'source'      => $this->source,
            'document'    => $this->document,
            'copyFields'  => $this->copyFields,
            'manifest'    => $this->manifest,
            'suggestions' => $this->suggestions,
            'warnings'    => $this->warnings,
            'parties'     => $this->parties,
            'voyageHints' => $this->voyageHints,
        ];
    }

    public static function fromLivewire($value): static
    {
        return new self(
            source: $value['source'] ?? ['channel' => 'manual', 'artifacts' => [], 'received_at' => null],
            document: $value['document'] ?? ['number' => null, 'date' => null, 'confidence' => []],
            copyFields: $value['copyFields'] ?? [],
            manifest: $value['manifest'] ?? ['detected_count' => 0, 'claimed_count' => null, 'units' => []],
            suggestions: $value['suggestions'] ?? [],
            warnings: $value['warnings'] ?? [],
            parties: $value['parties'] ?? ['dealer_name' => null, 'customer_text' => null, 'receiver_text' => null, 'pic_name' => null, 'email' => null],
            voyageHints: $value['voyageHints'] ?? ['vessel_name' => null, 'document_etd' => null],
        );
    }
}

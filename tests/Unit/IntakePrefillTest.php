<?php

namespace Tests\Unit;

use App\Support\Intake\IntakePrefill;
use PHPUnit\Framework\TestCase;

/**
 * OCR-01 — IntakePrefill envelope contract.
 * Pure unit test: no app boot, no DB.
 */
class IntakePrefillTest extends TestCase
{
    private function samplePrefill(): IntakePrefill
    {
        return new IntakePrefill(
            source: ['channel' => 'sppb_do', 'artifacts' => ['shipments/2026/07/sppb.pdf'], 'received_at' => '2026-07-15T08:00:00+08:00'],
            document: ['number' => '0627/LOG-SBR/07/2026', 'date' => '2026-07-12', 'confidence' => ['number' => 0.95, 'date' => 0.85]],
            copyFields: ['delivery_scope' => ['value' => 'door_to_door', 'confidence' => 0.80]],
            manifest: ['detected_count' => 3, 'units' => [['model' => 'Avanza'], ['model' => 'Fortuner'], ['model' => 'Hilux']]],
            suggestions: ['customer_id' => ['value' => 7, 'confidence' => 0.85, 'match' => 'PT Hasjrat Abadi']],
            warnings: [['code' => 'partial_extraction', 'message' => 'Sebagian dokumen tidak terbaca.']],
        );
    }

    public function test_empty_envelope_is_empty_and_channel_neutral(): void
    {
        $prefill = IntakePrefill::empty('manual');

        $this->assertTrue($prefill->isEmpty());
        $this->assertSame('manual', $prefill->source['channel']);
        $this->assertSame(0, $prefill->detectedFieldCount());
        $this->assertSame(0, $prefill->unitCount());
        $this->assertFalse($prefill->hasWarnings());
        $this->assertSame([], $prefill->summaryItems());
    }

    public function test_populated_envelope_reports_detections(): void
    {
        $prefill = $this->samplePrefill();

        $this->assertFalse($prefill->isEmpty());
        // document number + date + 1 copy field + 1 suggestion
        $this->assertSame(4, $prefill->detectedFieldCount());
        $this->assertSame(3, $prefill->unitCount());
        $this->assertTrue($prefill->hasWarnings());
        $this->assertSame(7, $prefill->suggestionFor('customer_id')['value']);
        $this->assertNull($prefill->suggestionFor('voyage_id'));
    }

    public function test_summary_items_cover_all_species_without_reextraction(): void
    {
        $items  = $this->samplePrefill()->summaryItems();
        $labels = array_column($items, 'label');
        $joined = implode(' | ', $labels);

        // Part 5: enough data to render the Review Summary later.
        $this->assertStringContainsString('0627/LOG-SBR/07/2026', $joined);
        $this->assertStringContainsString('Customer terdeteksi: PT Hasjrat Abadi (85%)', $joined);
        $this->assertStringContainsString('3 unit terdeteksi', $joined);
        $this->assertStringContainsString('Sebagian dokumen tidak terbaca.', $joined);

        $statuses = array_unique(array_column($items, 'status'));
        sort($statuses);
        $this->assertSame(['detected', 'warning'], $statuses);
    }

    public function test_livewire_round_trip_preserves_everything(): void
    {
        $original = $this->samplePrefill();

        $restored = IntakePrefill::fromLivewire($original->toLivewire());

        $this->assertSame($original->source, $restored->source);
        $this->assertSame($original->document, $restored->document);
        $this->assertSame($original->copyFields, $restored->copyFields);
        $this->assertSame($original->manifest, $restored->manifest);
        $this->assertSame($original->suggestions, $restored->suggestions);
        $this->assertSame($original->warnings, $restored->warnings);
    }
}

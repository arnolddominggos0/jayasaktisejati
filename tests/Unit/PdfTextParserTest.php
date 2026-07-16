<?php

namespace Tests\Unit;

use App\Services\SppbAssistService;
use ReflectionClass;
use Tests\TestCase;

/**
 * OCR-01D — regresi parser PDF internal.
 * Mengunci dua perbaikan: boundary stream…endstream (bug preg_split lama
 * memakan kata "endstream") dan newline logis pada operator pindah baris
 * (Td, TD, T-star, ET). Extends Tests\TestCase (butuh app untuk facade
 * Log), tanpa DB.
 */
class PdfTextParserTest extends TestCase
{
    private function extract(string $pdf): string
    {
        $svc = new SppbAssistService();
        $m   = (new ReflectionClass($svc))->getMethod('extractPdfText');
        $m->setAccessible(true);

        return $m->invoke($svc, $pdf);
    }

    private function wrapStream(string $streamBytes): string
    {
        return "%PDF-1.4\n3 0 obj\n<</Length " . strlen($streamBytes)
            . "/Filter/FlateDecode>>stream\n"
            . $streamBytes
            . "\nendstream\nendobj\ntrailer\n<</Root 1 0 R>>\n%%EOF";
    }

    public function test_flatedecode_stream_boundary_is_parsed(): void
    {
        $content = 'BT /F1 12 Tf 20 700 Td (PT. UJI PARSER) Tj 0 -20 Td (NO DOC: 0123/UJI/07/2026) Tj ET';
        $pdf     = $this->wrapStream(gzcompress($content));

        $text = $this->extract($pdf);

        // Bug lama: text selalu '' karena kata "endstream" termakan preg_split.
        $this->assertNotSame('', $text);
        $this->assertStringContainsString('PT. UJI PARSER', $text);
        $this->assertStringContainsString('NO DOC: 0123/UJI/07/2026', $text);
    }

    public function test_line_moving_operators_produce_logical_newlines(): void
    {
        $content = 'BT (Tanggal: 15/07/2026) Tj 0 -20 Td (Nama Kapal: KM UJI) Tj T* (Baris tiga) Tj ET';
        $pdf     = $this->wrapStream(gzcompress($content));

        $lines = preg_split('/\r?\n/', $this->extract($pdf));

        // Bug lama: seluruh teks jadi SATU baris — extractor label mati.
        $this->assertGreaterThan(1, count($lines));
        $this->assertContains('Tanggal: 15/07/2026', $lines);
        $this->assertContains('Nama Kapal: KM UJI', $lines);
    }

    public function test_uncompressed_stream_still_works(): void
    {
        $content = 'BT (Stream tanpa kompresi) Tj ET';
        $pdf     = "%PDF-1.4\nstream\n{$content}\nendstream\n%%EOF";

        $this->assertStringContainsString('Stream tanpa kompresi', $this->extract($pdf));
    }

    public function test_tj_array_strings_are_captured(): void
    {
        $content = 'BT [(Poto) -250 (ngan) ] TJ ET';
        $pdf     = $this->wrapStream(gzcompress($content));

        $text = $this->extract($pdf);

        $this->assertStringContainsString('Poto', $text);
        $this->assertStringContainsString('ngan', $text);
    }
}

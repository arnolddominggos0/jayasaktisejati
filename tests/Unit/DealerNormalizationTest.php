<?php

namespace Tests\Unit;

use App\Models\Dealer;
use PHPUnit\Framework\TestCase;

/**
 * DOMAIN-02 — normalisasi nama dealer untuk pencocokan OCR.
 * Generik: tanpa aturan per-perusahaan. Murni tanpa DB.
 */
class DealerNormalizationTest extends TestCase
{
    public function test_normalization_is_generic(): void
    {
        $cases = [
            'PT. Hasjrat Abadi'      => 'HASJRAT ABADI',
            'PT HASJRAT ABADI'       => 'HASJRAT ABADI',
            'pt. hasjrat abadi'      => 'HASJRAT ABADI',
            'CV. Sun Star Motor'     => 'SUN STAR MOTOR',
            'UD Bosowa Manado'       => 'BOSOWA MANADO',
            'Honda Surabaya Center'  => 'HONDA SURABAYA CENTER', // tanpa prefiks — utuh
            '  PT.  Hasjrat  Abadi ' => 'HASJRAT ABADI',
            null                     => '',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, Dealer::normalizeName($input === '' ? null : $input), 'input: ' . var_export($input, true));
        }
    }
}

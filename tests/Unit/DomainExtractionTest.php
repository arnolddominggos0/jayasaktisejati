<?php

namespace Tests\Unit;

use App\Services\SppbAssistService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * OCR-01E — domain extraction (Text → blok IntakePrefill).
 * Fixture = output nyata parser OCR-01D untuk SPPB Hasjrat 0669.
 * Menguji extractor murni (tanpa DB, tanpa app boot): parties, shipment
 * claims, voyage hints, manifest ber-anchor VIN, copy fields.
 */
class DomainExtractionTest extends TestCase
{
    private SppbAssistService $svc;
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        $this->svc = new SppbAssistService();
        $this->ref = new ReflectionClass($this->svc);
    }

    private function call(string $method, ...$args)
    {
        $m = $this->ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($this->svc, ...$args);
    }

    private function hasjratText(): string
    {
        return implode("\n", [
            'PT. HASJRAT ABADI',
            'Jl RP Soeroso 38-40',
            'SPPB',
            'TO: SOMBAR SULAWESI',
            'NO DOC: 0669/LOG-SBR/07/2026',
            'UP: BP. SONNY',
            'Tanggal: 15/07/2026',
            'Email: sonny.jayasakti@gmail.com',
            'Keterangan:',
            'Dengan hormat,',
            'Sehubungan dengan rencana pengiriman unit dengan data sebagai berikut:',
            'NO.', 'MODEL', 'NO. REG', 'NO. RANGKA', 'NO. MESIN', 'WARNA', 'NO. DO', 'JML', 'KET',
            '1.',
            'AGYA 1.2 G M/T Lux',
            '2607-0210',
            'MHKAB1BC7TJ089520',
            'WA-A258685',
            'WHITE',
            'JKT/01/26/07/0120',
            '1',
            '2.',
            'CALYA 1.2 G M/T Lux',
            '2607-0196',
            'MHKA6GJ6JTJ231254',
            '3NR-5A04796',
            'SILVER METALLIC',
            'JKT/01/26/07/0120',
            '1',
            '3.',
            'Veloz 1.5 V HV CVT Non Premium Color',
            '2607-0192',
            'MHFA71BY0T0012926',
            '2NR-Y730304',
            'BLACK MICA',
            'JKT/01/26/07/0120',
            '1',
            'Total: 3',
            'Lokasi Unit: SEMPER',
            'JL. KEBANTENAN NO. 20 JAKARTA - UTARA Tlp. 021-70973056',
            'Tujuan: PT. HA KOTAMOBAGU',
            'Nama Kapal: KM TANTO TANGGUH',
            'ETD Jakarta: 22/07/2026',
            'Syarat Kirim: DOOR TO DOOR',
            'Mohon agar Bapak/Ibu/Sdr dapat segera mengambilnya.',
        ]);
    }

    public function test_document_extraction(): void
    {
        $doc = $this->call('extractDocument', $this->hasjratText());

        $this->assertSame('0669/LOG-SBR/07/2026', $doc['number']);
        $this->assertSame('2026-07-15', $doc['date']);
    }

    public function test_party_extraction_text_only(): void
    {
        $parties = $this->call('extractParties', $this->hasjratText());

        $this->assertSame('PT. HASJRAT ABADI', $parties['customer_text']);
        $this->assertSame('SOMBAR SULAWESI', $parties['receiver_text']);
        $this->assertSame('BP. SONNY', $parties['pic_name']);
        $this->assertSame('sonny.jayasakti@gmail.com', $parties['email']);
    }

    public function test_shipment_claims_extraction(): void
    {
        $claims = $this->call('extractShipmentClaims', $this->hasjratText());

        $this->assertSame('PT. HA KOTAMOBAGU', $claims['destination']);
        $this->assertSame('KOTAMOBAGU', $claims['destination_city_hint']); // OCR-02B
        $this->assertSame('SEMPER', $claims['pickup_location']);
        $this->assertSame('DOOR TO DOOR', $claims['delivery_scope']);
        $this->assertNull($claims['notes']); // "Keterangan:" kosong di dokumen
    }

    /** OCR-02B — derivasi hint kota generik, tanpa aturan per-kota. */
    public function test_city_hint_derivation_is_generic(): void
    {
        $cases = [
            'PT. HA KOTAMOBAGU'          => 'KOTAMOBAGU',
            'PT. HA MANADO'              => 'MANADO',
            'PT. HA BITUNG'              => 'BITUNG',
            'PT. HA GORONTALO'           => 'GORONTALO',
            'CV. AB TERNATE'             => 'TERNATE',
            'PT HA LUWUK BANGGAI'        => 'LUWUK BANGGAI',   // multi-kata dipertahankan
            'PT. HASJRAT ABADI MANADO'   => 'HASJRAT ABADI MANADO', // tak dipangkas paksa — Apply yang memutuskan via lookup unik
            'PT. HA'                     => null,               // tak ada sisa kota
            ''                           => null,
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame(
                $expected,
                $this->call('deriveCityHint', $input === '' ? null : $input),
                "input: {$input}"
            );
        }
    }

    public function test_voyage_hints_never_more_than_hints(): void
    {
        $hints = $this->call('extractVoyageHints', $this->hasjratText());

        $this->assertSame('KM TANTO TANGGUH', $hints['vessel_name']);
        $this->assertSame('2026-07-22', $hints['document_etd']);
    }

    public function test_manifest_extraction_vin_anchored(): void
    {
        $manifest = $this->call('extractManifest', $this->hasjratText());

        $this->assertSame(3, $manifest['detected_count']);
        $this->assertSame(3, $manifest['claimed_count']);

        [$u1, $u2, $u3] = $manifest['units'];

        $this->assertSame('AGYA 1.2 G M/T Lux', $u1['model']);
        $this->assertSame('MHKAB1BC7TJ089520', $u1['vin']);
        $this->assertSame('WA-A258685', $u1['engine']);
        $this->assertSame('WHITE', $u1['color']);
        $this->assertSame('JKT/01/26/07/0120', $u1['do_number']);
        $this->assertSame(1, $u1['qty']);

        $this->assertSame('SILVER METALLIC', $u2['color']);
        $this->assertSame('3NR-5A04796', $u2['engine']);

        $this->assertSame('Veloz 1.5 V HV CVT Non Premium Color', $u3['model']);
        $this->assertSame('MHFA71BY0T0012926', $u3['vin']);
    }

    public function test_manifest_inline_row_fallback(): void
    {
        $line = '1. AGYA 1.2 G M/T Lux 2607-0210 MHKAB1BC7TJ089520 WA-A258685 WHITE JKT/01/26/07/0120 1';

        $manifest = $this->call('extractManifest', $line);

        $this->assertSame(1, $manifest['detected_count']);
        $u = $manifest['units'][0];
        $this->assertSame('AGYA 1.2 G M/T Lux', $u['model']);
        $this->assertSame('MHKAB1BC7TJ089520', $u['vin']);
        $this->assertSame('WA-A258685', $u['engine']);
        $this->assertSame('WHITE', $u['color']);
        $this->assertSame('JKT/01/26/07/0120', $u['do_number']);
        $this->assertSame(1, $u['qty']);
    }

    public function test_copy_fields_include_scope_enum(): void
    {
        $claims = $this->call('extractShipmentClaims', $this->hasjratText());
        $copy   = $this->call('buildCopyFields', $claims);

        $this->assertSame('door_to_door', $copy['delivery_scope']['value']);
        $this->assertSame('PT. HA KOTAMOBAGU', $copy['destination']['value']);
        $this->assertSame('SEMPER', $copy['pickup_location']['value']);
        $this->assertArrayNotHasKey('notes', $copy);
    }

    public function test_warnings_on_count_mismatch(): void
    {
        $manifest = ['detected_count' => 2, 'claimed_count' => 3, 'units' => [
            ['do_number' => 'X/1'], ['do_number' => null],
        ]];

        $warnings = $this->call(
            'collectWarnings',
            ['number' => 'X', 'date' => null, 'confidence' => []],
            [],
            [],
            ['customer_text' => null, 'receiver_text' => null, 'pic_name' => null, 'email' => null],
            ['vessel_name' => null, 'document_etd' => null],
            $manifest,
        );

        $codes = array_column($warnings, 'code');
        $this->assertContains('unit_count_mismatch', $codes);
        $this->assertContains('do_missing', $codes);
        $this->assertContains('vessel_not_found', $codes);
        $this->assertContains('delivery_scope_missing', $codes);
    }
}

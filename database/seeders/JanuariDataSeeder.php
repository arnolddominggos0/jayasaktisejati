<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder: Data Real TAM Manado — Januari 2026
 *
 * 5 Voyage | 5 Shipment | 164 Unit
 * Idempotent: skip jika chassis_no sudah ada.
 * Jangan ubah struktur database.
 */
class JanuariDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        // ── 1. Voyage Configurations ──────────────────────────────────────────
        //
        // vessel_id: 1=Tanto Jaya, 3=Tanto Salam, 4=Tanto Tangguh, 5=Tanto Sejahtera
        // pol_id=1 (IDJKT), pod_id=2 (IDBTG), shipping_line_id=1 (Tanto Intim Line)
        //
        $voyageDefs = [
            'V149' => [
                'vessel_id'           => 5,
                'voyage_no'           => '149',
                'etd'                 => '2026-01-09 00:00:00',
                'eta'                 => '2026-01-19 00:00:00',
                'atd_at'              => '2026-01-09 00:00:00',
                'ata_at'              => '2026-01-19 00:00:00',
                'period_month'        => '2026-01-01',
                'actual_sailing_days' => 10.00,
                'cargo_actual'        => 1,
            ],
            'V305' => [
                'vessel_id'           => 1,
                'voyage_no'           => '305',
                'etd'                 => '2026-01-14 00:00:00',
                'eta'                 => '2026-01-26 00:00:00',
                'atd_at'              => '2026-01-14 00:00:00',
                'ata_at'              => '2026-01-26 00:00:00',
                'period_month'        => '2026-01-01',
                'actual_sailing_days' => 12.00,
                'cargo_actual'        => 40,
            ],
            'V243' => [
                'vessel_id'           => 4,
                'voyage_no'           => '243',
                'etd'                 => '2026-01-19 00:00:00',
                'eta'                 => '2026-01-31 00:00:00',
                'atd_at'              => '2026-01-19 00:00:00',
                'ata_at'              => '2026-01-31 00:00:00',
                'period_month'        => '2026-01-01',
                'actual_sailing_days' => 12.00,
                'cargo_actual'        => 57,
            ],
            'V156' => [
                'vessel_id'           => 3,
                'voyage_no'           => '156',
                'etd'                 => '2026-01-25 00:00:00',
                'eta'                 => '2026-02-04 00:00:00',
                'atd_at'              => '2026-01-25 00:00:00',
                'ata_at'              => '2026-02-04 00:00:00',
                'period_month'        => '2026-01-01',
                'actual_sailing_days' => 10.00,
                'cargo_actual'        => 49,
            ],
            'V150' => [
                'vessel_id'           => 5,
                'voyage_no'           => '150',
                'etd'                 => '2026-01-31 00:00:00',
                'eta'                 => '2026-02-10 00:00:00',
                'atd_at'              => '2026-01-31 00:00:00',
                'ata_at'              => '2026-02-10 00:00:00',
                'period_month'        => '2026-01-01',
                'actual_sailing_days' => 10.00,
                'cargo_actual'        => 17,
            ],
        ];

        // ── 2. Shipment Configurations ────────────────────────────────────────
        //
        // customer_id=1 (Toyota Astra Motor), receiver_id=2 (Hasjrat Abadi)
        // branch_id=1 (Jakarta), origin_city_id=3 (JAKARTA), destination_city_id=1 (MANADO)
        // assigned_depot_id=1 (Depo PDI Jakarta)
        //
        $shipmentDefs = [
            'V149' => [
                'voyage_key'     => 'V149',
                'code'           => 'JSS0126SH0001',
                'vessel_name'    => 'Tanto Sejahtera',
                'voyage'         => '149',
                'packages_total' => 1,
                'pickup_date'    => '2026-01-07 00:00:00',
                'onboard_at'     => '2026-01-09 00:00:00',
                'arrived_at'     => '2026-01-19 00:00:00',
                'delivered_at'   => '2026-01-20 00:00:00',
                'tracks'         => [
                    ['status' => 'pickup',         'tracked_at' => '2026-01-07', 'normalized' => 10],
                    ['status' => 'vessel_depart',  'tracked_at' => '2026-01-09', 'normalized' => 80],
                    ['status' => 'vessel_arrival', 'tracked_at' => '2026-01-19', 'normalized' => 90],
                    ['status' => 'delivered',      'tracked_at' => '2026-01-20', 'normalized' => 120],
                ],
            ],
            'V305' => [
                'voyage_key'     => 'V305',
                'code'           => 'JSS0126SH0002',
                'vessel_name'    => 'Tanto Jaya',
                'voyage'         => '305',
                'packages_total' => 40,
                'pickup_date'    => '2026-01-10 00:00:00',
                'onboard_at'     => '2026-01-14 00:00:00',
                'arrived_at'     => '2026-01-26 00:00:00',
                'delivered_at'   => '2026-01-27 00:00:00',
                'tracks'         => [
                    ['status' => 'pickup',         'tracked_at' => '2026-01-10', 'normalized' => 10],
                    ['status' => 'vessel_depart',  'tracked_at' => '2026-01-14', 'normalized' => 80],
                    ['status' => 'vessel_arrival', 'tracked_at' => '2026-01-26', 'normalized' => 90],
                    ['status' => 'delivered',      'tracked_at' => '2026-01-27', 'normalized' => 120],
                ],
            ],
            'V243' => [
                'voyage_key'     => 'V243',
                'code'           => 'JSS0126SH0003',
                'vessel_name'    => 'Tanto Tangguh',
                'voyage'         => '243',
                'packages_total' => 57,
                'pickup_date'    => '2026-01-14 00:00:00',
                'onboard_at'     => '2026-01-19 00:00:00',
                'arrived_at'     => '2026-01-31 00:00:00',
                'delivered_at'   => '2026-02-03 00:00:00',
                'tracks'         => [
                    ['status' => 'pickup',         'tracked_at' => '2026-01-14', 'normalized' => 10],
                    ['status' => 'vessel_depart',  'tracked_at' => '2026-01-19', 'normalized' => 80],
                    ['status' => 'vessel_arrival', 'tracked_at' => '2026-01-31', 'normalized' => 90],
                    ['status' => 'delivered',      'tracked_at' => '2026-02-03', 'normalized' => 120],
                ],
            ],
            'V156' => [
                'voyage_key'     => 'V156',
                'code'           => 'JSS0126SH0004',
                'vessel_name'    => 'Tanto Salam',
                'voyage'         => '156',
                'packages_total' => 49,
                'pickup_date'    => '2026-01-20 00:00:00',
                'onboard_at'     => '2026-01-25 00:00:00',
                'arrived_at'     => '2026-02-04 00:00:00',
                'delivered_at'   => '2026-02-06 00:00:00',
                'tracks'         => [
                    ['status' => 'pickup',         'tracked_at' => '2026-01-20', 'normalized' => 10],
                    ['status' => 'vessel_depart',  'tracked_at' => '2026-01-25', 'normalized' => 80],
                    ['status' => 'vessel_arrival', 'tracked_at' => '2026-02-04', 'normalized' => 90],
                    ['status' => 'delivered',      'tracked_at' => '2026-02-06', 'normalized' => 120],
                ],
            ],
            'V150' => [
                'voyage_key'     => 'V150',
                'code'           => 'JSS0126SH0005',
                'vessel_name'    => 'Tanto Sejahtera',
                'voyage'         => '150',
                'packages_total' => 17,
                'pickup_date'    => '2026-01-26 00:00:00',
                'onboard_at'     => '2026-01-31 00:00:00',
                'arrived_at'     => '2026-02-10 00:00:00',
                'delivered_at'   => '2026-02-12 00:00:00',
                'tracks'         => [
                    ['status' => 'pickup',         'tracked_at' => '2026-01-26', 'normalized' => 10],
                    ['status' => 'vessel_depart',  'tracked_at' => '2026-01-31', 'normalized' => 80],
                    ['status' => 'vessel_arrival', 'tracked_at' => '2026-02-10', 'normalized' => 90],
                    ['status' => 'delivered',      'tracked_at' => '2026-02-12', 'normalized' => 120],
                ],
            ],
        ];

        // ── 3. Unit Records — 164 units ───────────────────────────────────────
        //
        // Kolom: rangka (chassis_no), mesin (engine_no), model (model_no), vkey (voyage key)
        //
        $units = [
            // ── KM. TANTO SEJAHTERA V.149 — 1 unit ───────────────────────────
            ['vkey' => 'V149', 'rangka' => 'MHKAB1BA6SJ152023', 'mesin' => 'A221576',  'model' => 'RAIZE'],
            // ── KM. TANTO JAYA V.305 — 40 units ─────────────────────────────
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BC6TJ076810', 'mesin' => 'A224815',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BY9TJ012594', 'mesin' => '4F27390',  'model' => 'AVANZA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219464', 'mesin' => 'H975958',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219425', 'mesin' => 'H976103',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219483', 'mesin' => 'H976151',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219506', 'mesin' => 'H976297',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219511', 'mesin' => 'H976304',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219515', 'mesin' => 'H976255',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219560', 'mesin' => 'H976120',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219569', 'mesin' => 'H976162',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219570', 'mesin' => 'H976181',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FB3JTK116586', 'mesin' => '4F27602',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FB3JTK116599', 'mesin' => '4F27970',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BY5TJ012589', 'mesin' => '4F27316',  'model' => 'AVANZA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GK6JTJ087675', 'mesin' => 'H975878',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219412', 'mesin' => 'H975988',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219532', 'mesin' => 'H976277',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHFJB8EM1T1181914', 'mesin' => '5916624',  'model' => 'INNOVA'],
            ['vkey' => 'V305', 'rangka' => 'MHFABAAA0T0047498', 'mesin' => 'NF04601',  'model' => 'INNOVA ZENIX'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FA3JTK138962', 'mesin' => '4F27507',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FA3JTK138964', 'mesin' => '4F26968',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BC5TJ076796', 'mesin' => 'A224439',  'model' => 'AGYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BC1TJ076844', 'mesin' => 'A224453',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BY3TJ012669', 'mesin' => '4F27400',  'model' => 'AVANZA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219440', 'mesin' => 'H975984',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219443', 'mesin' => 'H975900',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219451', 'mesin' => 'H975922',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219452', 'mesin' => 'H975993',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219442', 'mesin' => 'H976048',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219529', 'mesin' => 'H976276',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219538', 'mesin' => 'H976246',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219553', 'mesin' => 'H976127',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FA3JTK138971', 'mesin' => '4F26923',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FA3JTK138973', 'mesin' => '4F26969',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219468', 'mesin' => 'H975858',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219536', 'mesin' => 'H976249',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKA6GJ6JTJ219632', 'mesin' => 'H976401',  'model' => 'CALYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKE8FB3JTK116622', 'mesin' => '4F28955',  'model' => 'RUSH'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BC3TJ077008', 'mesin' => 'A225210',  'model' => 'AGYA'],
            ['vkey' => 'V305', 'rangka' => 'MHKAB1BC2TJ076920', 'mesin' => 'A224993',  'model' => 'AGYA STYLIX'],
            // ── KM. TANTO TANGGUH V.243 — 57 units ───────────────────────────
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC8SJ075673', 'mesin' => 'A223431',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC0TJ076947', 'mesin' => 'A224908',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC8TJ076887', 'mesin' => 'A224025',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC5TJ076880', 'mesin' => 'A224834',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219496', 'mesin' => 'H976280',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219499', 'mesin' => 'H976345',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219598', 'mesin' => 'H976368',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219631', 'mesin' => 'H976404',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHFAA8GS2T0933821', 'mesin' => 'J140546',  'model' => 'FORTUNER'],
            ['vkey' => 'V243', 'rangka' => 'MHFJB8EM4T1181938', 'mesin' => '5916516',  'model' => 'INNOVA'],
            ['vkey' => 'V243', 'rangka' => 'MHFAAAAA7T0047971', 'mesin' => 'NF09767',  'model' => 'INNOVA ZENIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FB3JTK116606', 'mesin' => '4F28899',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139022', 'mesin' => '4F27691',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139031', 'mesin' => '4F28110',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139033', 'mesin' => '4F28115',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC6SJ075672', 'mesin' => 'A223463',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC7TJ076878', 'mesin' => 'A224028',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC0TJ076964', 'mesin' => 'A224919',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC6TJ076886', 'mesin' => 'A224965',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BY8TJ012764', 'mesin' => '4F27939',  'model' => 'AVANZA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219567', 'mesin' => 'H976156',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219479', 'mesin' => 'H975942',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219609', 'mesin' => 'H976429',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139066', 'mesin' => '4F28987',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139083', 'mesin' => '4F29218',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MR0KB8CD0S1233021', 'mesin' => 'D562070',  'model' => 'HILUX D-CAB'],
            ['vkey' => 'V243', 'rangka' => 'MR0AC9EA0S0100623', 'mesin' => 'D557797',  'model' => 'HILUX PICK UP'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219493', 'mesin' => 'H975940',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC8SJ075785', 'mesin' => 'A223736',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC6TJ076984', 'mesin' => 'A225545',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC9TJ077031', 'mesin' => 'A225866',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC3TJ077025', 'mesin' => 'A225940',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC1TJ076973', 'mesin' => 'A225295',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC7TJ077044', 'mesin' => 'A225925',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219621', 'mesin' => 'H976507',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219634', 'mesin' => 'H976693',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219639', 'mesin' => 'H976397',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219677', 'mesin' => 'H976683',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHFJB8EM8T1181957', 'mesin' => '1901903',  'model' => 'INNOVA'],
            ['vkey' => 'V243', 'rangka' => 'MHFAAAAA1T0047996', 'mesin' => 'NF11820',  'model' => 'INNOVA ZENIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FB3JTK116720', 'mesin' => '4F29631',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139154', 'mesin' => '4F29574',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKE8FA3JTK139169', 'mesin' => '4F30378',  'model' => 'RUSH'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC6TJ076998', 'mesin' => 'A225552',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219583', 'mesin' => 'H976212',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC7SJ075891', 'mesin' => 'A224092',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC9TJ077028', 'mesin' => 'A225867',  'model' => 'AGYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKAB1BC6TJ077021', 'mesin' => 'A225611',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219698', 'mesin' => 'H976616',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219700', 'mesin' => 'H976646',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219720', 'mesin' => 'H976745',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219725', 'mesin' => 'H976770',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219743', 'mesin' => 'H976831',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHFAA8GS4T0933996', 'mesin' => '5920194',  'model' => 'FORTUNER'],
            ['vkey' => 'V243', 'rangka' => 'MHFJB8EM6T1181942', 'mesin' => '5916457',  'model' => 'INNOVA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219704', 'mesin' => 'H976383',  'model' => 'CALYA'],
            ['vkey' => 'V243', 'rangka' => 'MHKA6GJ6JTJ219740', 'mesin' => 'H976369',  'model' => 'CALYA'],
            // ── KM. TANTO SALAM V.156 — 49 units ─────────────────────────────
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC5TJ077060', 'mesin' => 'A225868',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BY6TJ013072', 'mesin' => '4F30368',  'model' => 'AVANZA'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GK6JTJ087879', 'mesin' => 'H977540',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FA3JTK139207', 'mesin' => '4F30550',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FA3JTK139275', 'mesin' => '4F31530',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC1TJ077136', 'mesin' => 'A226368',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC7TJ077237', 'mesin' => 'A226830',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC0TJ077192', 'mesin' => 'A226216',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ219848', 'mesin' => 'H977101',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ219921', 'mesin' => 'H977303',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ219925', 'mesin' => 'H977256',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC7TJ076850', 'mesin' => 'A224750',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC4TJ077177', 'mesin' => 'A226440',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC3TJ077087', 'mesin' => 'A226116',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC0TJ077175', 'mesin' => 'A226371',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC7TJ077092', 'mesin' => 'A226304',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHFABAAA7T0047790', 'mesin' => 'NF08004',  'model' => 'INNOVA ZENIX'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FA3JTK139203', 'mesin' => '4F30580',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FA3JTK139253', 'mesin' => '4F31218',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ219798', 'mesin' => 'H976587',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FA3JTK139198', 'mesin' => '4F30576',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ220068', 'mesin' => 'H977402',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC7TJ077206', 'mesin' => 'A226831',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC6TJ077231', 'mesin' => 'A226842',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC6TJ077293', 'mesin' => 'A227216',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC8TJ077134', 'mesin' => 'A226236',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ220006', 'mesin' => 'H976184',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ220368', 'mesin' => 'H978226',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC2TJ077226', 'mesin' => 'A226835',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC9TJ077191', 'mesin' => 'A226167',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC6TJ077214', 'mesin' => 'A226829',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC8TJ077277', 'mesin' => 'A227206',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC9TJ077286', 'mesin' => 'A227207',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BCXTJ077295', 'mesin' => 'A227119',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC0TJ077256', 'mesin' => 'A226659',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ220229', 'mesin' => 'H977868',  'model' => 'CALYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FB3JTK116878', 'mesin' => '4F31975',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKE8FA3JTK139273', 'mesin' => '4F31582',  'model' => 'RUSH'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BY1TJ013299', 'mesin' => '4F33143',  'model' => 'AVANZA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC8TJ077201', 'mesin' => 'A226656',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHFBA3FSXT1174368', 'mesin' => 'J139826',  'model' => 'FORTUNER'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC4TJ077034', 'mesin' => 'A225633',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAB1BC7TJ077058', 'mesin' => 'A225865',  'model' => 'AGYA'],
            ['vkey' => 'V156', 'rangka' => 'MHFAA8GS7T0934074', 'mesin' => '5921382',  'model' => 'FORTUNER'],
            ['vkey' => 'V156', 'rangka' => 'MHFAAAAA7T0048165', 'mesin' => 'NF14356',  'model' => 'INNOVA ZENIX'],
            ['vkey' => 'V156', 'rangka' => 'MHKAA1BY7SJ007718', 'mesin' => 'G312317',  'model' => 'AVANZA'],
            ['vkey' => 'V156', 'rangka' => 'MHFJB8EM0T1181905', 'mesin' => '5916632',  'model' => 'INNOVA'],
            ['vkey' => 'V156', 'rangka' => 'MHKAA1BA1TJ182048', 'mesin' => 'B024742',  'model' => 'RAIZE'],
            ['vkey' => 'V156', 'rangka' => 'MHKA6GJ6JTJ219981', 'mesin' => 'H976998',  'model' => 'CALYA'],
            // ── KM. TANTO SEJAHTERA V.150 — 17 units ─────────────────────────
            ['vkey' => 'V150', 'rangka' => 'MR0BB3CD5S5818043', 'mesin' => 'J144095',  'model' => 'HILUX D-CAB'],
            ['vkey' => 'V150', 'rangka' => 'MHKA6GJ6JTJ220176', 'mesin' => 'H977770',  'model' => 'CALYA'],
            ['vkey' => 'V150', 'rangka' => 'MHKAA1BA3TJ182066', 'mesin' => 'B025307',  'model' => 'RAIZE'],
            ['vkey' => 'V150', 'rangka' => 'MHFJB8EM1T1182027', 'mesin' => '1902336',  'model' => 'INNOVA'],
            ['vkey' => 'V150', 'rangka' => 'MHKA6GJ6JTJ220190', 'mesin' => 'H977812',  'model' => 'CALYA'],
            ['vkey' => 'V150', 'rangka' => 'MR0KB8CD1S1165456', 'mesin' => 'D560356',  'model' => 'HILUX D-CAB'],
            ['vkey' => 'V150', 'rangka' => 'MHFAB8BF8T0037076', 'mesin' => 'Y625064',  'model' => 'YARIS CROSS'],
            ['vkey' => 'V150', 'rangka' => 'MHKAB1BC3TJ077557', 'mesin' => 'A228508',  'model' => 'AGYA'],
            ['vkey' => 'V150', 'rangka' => 'MHKA6GJ6JTJ220073', 'mesin' => 'H977570',  'model' => 'CALYA'],
            ['vkey' => 'V150', 'rangka' => 'MHFAB8BF1T0037064', 'mesin' => 'Y625095',  'model' => 'YARIS CROSS'],
            ['vkey' => 'V150', 'rangka' => 'MHKAB1BC6TJ077617', 'mesin' => 'A227585',  'model' => 'AGYA STYLIX'],
            ['vkey' => 'V150', 'rangka' => 'MHFAB8BFXT0036799', 'mesin' => 'Y610528',  'model' => 'YARIS CROSS'],
            ['vkey' => 'V150', 'rangka' => 'MHFAA8GS5T0934106', 'mesin' => '5922445',  'model' => 'FORTUNER'],
            ['vkey' => 'V150', 'rangka' => 'MR0KB8CD1S1165876', 'mesin' => 'D563748',  'model' => 'HILUX D-CAB'],
            ['vkey' => 'V150', 'rangka' => 'MHKAA1BA0TJ182302', 'mesin' => 'B026920',  'model' => 'RAIZE'],
            ['vkey' => 'V150', 'rangka' => 'MHKAA1BY6TJ011342', 'mesin' => 'G316861',  'model' => 'AVANZA'],
            ['vkey' => 'V150', 'rangka' => 'MHKA6GJ6JTJ220335', 'mesin' => 'H978159',  'model' => 'CALYA'],
        ];

        // ── 4. Run Inserts ────────────────────────────────────────────────────

        DB::transaction(function () use ($voyageDefs, $shipmentDefs, $units, $now) {

            // 4a. Create voyages (idempotent by vessel_id + voyage_no)
            $voyageIds = [];
            foreach ($voyageDefs as $key => $vd) {
                $existing = DB::table('voyages')
                    ->where('vessel_id', $vd['vessel_id'])
                    ->where('voyage_no', $vd['voyage_no'])
                    ->value('id');

                if ($existing) {
                    $voyageIds[$key] = $existing;
                    $this->command->line("  [skip] Voyage {$vd['voyage_no']} sudah ada (id={$existing})");
                } else {
                    $id = DB::table('voyages')->insertGetId([
                        'vessel_id'           => $vd['vessel_id'],
                        'pol_id'              => 1,
                        'pod_id'              => 2,
                        'voyage_no'           => $vd['voyage_no'],
                        'etd'                 => $vd['etd'],
                        'eta'                 => $vd['eta'],
                        'atd_at'              => $vd['atd_at'],
                        'ata_at'              => $vd['ata_at'],
                        'period_month'        => $vd['period_month'],
                        'actual_sailing_days' => $vd['actual_sailing_days'],
                        'cargo_actual'        => $vd['cargo_actual'],
                        'shipping_line_id'    => 1,
                        'registry_status'     => 'completed',
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                    $voyageIds[$key] = $id;
                    $this->command->info("  [+] Voyage {$vd['voyage_no']} dibuat (id={$id})");
                }
            }

            // 4b. Create shipments (idempotent by code)
            $shipmentIds = [];
            $seqMap = ['V149' => 1, 'V305' => 2, 'V243' => 3, 'V156' => 4, 'V150' => 5];

            foreach ($shipmentDefs as $key => $sd) {
                $existing = DB::table('shipments')
                    ->where('code', $sd['code'])
                    ->value('id');

                if ($existing) {
                    $shipmentIds[$key] = $existing;
                    $this->command->line("  [skip] Shipment {$sd['code']} sudah ada (id={$existing})");
                } else {
                    $voyageId = $voyageIds[$key];
                    $id = DB::table('shipments')->insertGetId([
                        'code'                   => $sd['code'],
                        'customer_id'            => 1,   // Toyota Astra Motor
                        'receiver_id'            => 2,   // Hasjrat Abadi
                        'receiver_name'          => 'Hasjrat Abadi',
                        'branch_id'              => 1,   // Jakarta
                        'origin_city_id'         => 3,   // JAKARTA
                        'destination_city_id'    => 1,   // MANADO
                        'assigned_depot_id'      => 1,   // Depo PDI Jakarta
                        'voyage_id'              => $voyageId,
                        'vessel_name'            => $sd['vessel_name'],
                        'voyage'                 => $sd['voyage'],
                        'pol'                    => 'Tanjung Priok',
                        'pod'                    => 'Bitung',
                        'pol_id'                 => 1,
                        'pod_id'                 => 2,
                        'etd'                    => $sd['onboard_at'],
                        'eta'                    => $sd['arrived_at'],
                        'mode'                   => 'sea',
                        'route_from'             => 'JAKARTA',
                        'route_to'               => 'MANADO',
                        'route_summary'          => 'JAKARTA → MANADO',
                        'service_type'           => 'sea_freight',
                        'service_option'         => 'fcl',
                        'cargo_type'             => 'vehicle',
                        'vehicle_type'           => 'car',
                        'vehicle_kind'           => 'car',
                        'vehicle_loading'        => 'regular',
                        'request_type'           => 'sppb_do',
                        'priority'               => 'normal',
                        'packages_total'         => $sd['packages_total'],
                        'status'                 => 'delivered',
                        'pickup_date'            => $sd['pickup_date'],
                        'pickup_started_at'      => $sd['pickup_date'],
                        'onboard_at'             => $sd['onboard_at'],
                        'arrived_at'             => $sd['arrived_at'],
                        'delivered_at'           => $sd['delivered_at'],
                        'requested_at'           => $sd['pickup_date'],
                        'confirm_is_true'        => true,
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ]);
                    $shipmentIds[$key] = $id;
                    $this->command->info("  [+] Shipment {$sd['code']} dibuat (id={$id})");

                    // 4b-i. Create tracks for this shipment
                    foreach ($sd['tracks'] as $track) {
                        $trackExists = DB::table('shipment_tracks')
                            ->where('shipment_id', $id)
                            ->where('status', $track['status'])
                            ->exists();

                        if (! $trackExists) {
                            DB::table('shipment_tracks')->insert([
                                'shipment_id'       => $id,
                                'status'            => $track['status'],
                                'tracked_at'        => $track['tracked_at'] . ' 00:00:00',
                                'status_normalized' => $track['normalized'],
                                'note'              => 'Imported from TAM Jan 2026 ' . $sd['voyage_key'],
                                'has_issue'         => false,
                                'created_at'        => $now,
                                'updated_at'        => $now,
                            ]);
                        }
                    }
                }
            }

            // 4c. Create units (idempotent by chassis_no)
            $skipped   = 0;
            $inserted  = 0;

            foreach ($units as $u) {
                $exists = DB::table('units')
                    ->where('chassis_no', $u['rangka'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $shipmentId = $shipmentIds[$u['vkey']] ?? null;
                if (! $shipmentId) {
                    $this->command->warn("  [!] Shipment untuk vkey={$u['vkey']} tidak ditemukan — skip {$u['rangka']}");
                    continue;
                }

                DB::table('units')->insert([
                    'shipment_id' => $shipmentId,
                    'chassis_no'  => $u['rangka'],
                    'engine_no'   => $u['mesin'],
                    'model_no'    => $u['model'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $inserted++;
            }

            $this->command->info("  Unit: {$inserted} dibuat, {$skipped} dilewati (sudah ada).");
        });

        $this->command->info('✓ JanuariDataSeeder selesai.');
    }
}

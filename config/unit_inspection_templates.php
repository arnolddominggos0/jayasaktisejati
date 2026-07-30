<?php
return [

    // ── Pickup (PDC Asal) ──────────────────────────────────────────────────────
    'pickup' => [
        'EXTERIOR' => [
            [
                'name' => 'Lampu Depan',
                'type' => 'major_damage',
                'criteria' => [
                    'Menyala normal',
                    'Tidak retak',
                    'Tidak pecah',
                    'Terpasang baik',
                ],
            ],
            [
                'name' => 'Lampu Belakang',
                'type' => 'major_damage',
                'criteria' => [
                    'Menyala normal',
                    'Tidak retak',
                    'Tidak pecah',
                    'Terpasang baik',
                ],
            ],
            [
                'name' => 'Lampu Sign',
                'type' => 'major_damage',
                'criteria' => [
                    'Menyala dan berkedip normal',
                    'Tidak retak',
                    'Tidak pecah',
                    'Terpasang baik',
                ],
            ],
            [
                'name' => 'Bumper Depan',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak penyok',
                    'Tidak retak',
                    'Tidak baret',
                    'Terpasang baik',
                ],
            ],
            [
                'name' => 'Bumper Belakang',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak penyok',
                    'Tidak retak',
                    'Tidak baret',
                    'Terpasang baik',
                ],
            ],
            [
                'name' => 'Emblem',
                'type' => 'major_damage',
                'criteria' => [
                    'Lengkap',
                    'Tidak lepas',
                    'Tidak rusak',
                ],
            ],
            [
                'name' => 'Spion',
                'type' => 'major_damage',
                'criteria' => [
                    'Kaca tidak retak',
                    'Rumah tidak rusak',
                    'Berfungsi normal',
                ],
            ],
            [
                'name' => 'Ban',
                'type' => 'major_damage',
                'criteria' => [
                    'Tekanan normal',
                    'Tidak bocor',
                    'Tidak sobek',
                ],
            ],
            [
                'name' => 'Velg',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak penyok',
                    'Tidak retak',
                    'Baut lengkap',
                ],
            ],
        ],
        'INTERIOR' => [
            [
                'name' => 'AC',
                'type' => 'major_damage',
                'criteria' => [
                    'Dingin normal',
                    'Tidak berisik',
                    'Tidak bocor',
                ],
            ],
            [
                'name' => 'Radio',
                'type' => 'major_damage',
                'criteria' => [
                    'Menyala normal',
                    'Speaker jernih',
                    'Tombol lengkap',
                ],
            ],
            [
                'name' => 'Dashboard',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak retak',
                    'Indikator normal',
                    'Tidak ada bagian lepas',
                ],
            ],
            [
                'name' => 'Power Window',
                'type' => 'major_damage',
                'criteria' => [
                    'Naik-turun normal',
                    'Tidak macet',
                    'Saklar berfungsi',
                ],
            ],
        ],
        'DOCUMENT' => [
            [
                'name' => 'Buku Service',
                'type' => 'minor_missing',
                'criteria' => [
                    'Tersedia',
                    'Sesuai unit',
                    'Data lengkap',
                ],
            ],
            [
                'name' => 'Owner Manual',
                'type' => 'minor_missing',
                'criteria' => [
                    'Tersedia',
                    'Sesuai unit',
                    'Tidak rusak',
                ],
            ],
        ],
        'ACCESSORIES' => [
            [
                'name' => 'Toolkit',
                'type' => 'minor_missing',
                'criteria' => [
                    'Lengkap',
                    'Tidak hilang',
                    'Tidak rusak',
                ],
            ],
            [
                'name' => 'Dongkrak',
                'type' => 'minor_missing',
                'criteria' => [
                    'Tersedia',
                    'Berfungsi normal',
                    'Tidak rusak',
                ],
            ],
            [
                'name' => 'Segitiga Pengaman',
                'type' => 'minor_missing',
                'criteria' => [
                    'Tersedia',
                    'Tidak rusak',
                    'Tidak patah',
                ],
            ],
        ],
    ],

    // ── Handover Depo ──────────────────────────────────────────────────────────
    'handover_depot' => [
        'DOKUMEN' => [
            [
                'name' => 'Verifikasi Nomor Rangka',
                'type' => 'information_only',
                'criteria' => [
                    'Sesuai dokumen',
                    'Tercetak jelas',
                    'Tidak cacat',
                ],
            ],
            [
                'name' => 'Verifikasi SJKB',
                'type' => 'information_only',
                'criteria' => [
                    'Tersedia',
                    'Sesuai unit',
                    'Terbaca jelas',
                ],
            ],
        ],
        'KONDISI EKSTERIOR' => [
            [
                'name' => 'Kondisi Body',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak penyok',
                    'Tidak baret',
                    'Cat tidak kusam',
                    'Tidak ada benturan baru',
                ],
            ],
            [
                'name' => 'Kondisi Lampu',
                'type' => 'major_damage',
                'criteria' => [
                    'Menyala normal',
                    'Tidak retak',
                    'Tidak pecah',
                    'Terpasang baik',
                ],
            ],
            [
                'name' => 'Kondisi Kaca',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak retak',
                    'Tidak pecah',
                    'Tidak baret',
                    'Seal terpasang baik',
                ],
            ],
            [
                'name' => 'Kondisi Ban',
                'type' => 'major_damage',
                'criteria' => [
                    'Tekanan normal',
                    'Tidak bocor',
                    'Tidak sobek',
                ],
            ],
        ],
        'KELENGKAPAN' => [
            [
                'name' => 'Kelengkapan Unit',
                'type' => 'minor_missing',
                'criteria' => [
                    'Aksesoris lengkap',
                    'Tidak ada hilang',
                    'Sesuai daftar',
                ],
            ],
        ],
        'CATATAN KEDATANGAN' => [
            [
                'name' => 'Catatan Kerusakan Saat Tiba',
                'type' => 'information_only',
                'criteria' => [
                    'Catat semua kerusakan',
                    'Sertakan lokasi jelas',
                ],
            ],
        ],
    ],

    // ── Loading / Stuffing ─────────────────────────────────────────────────────
    'loading' => [
        'LOADING' => [
            [
                'name' => 'Unit Condition',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak ada kerusakan baru',
                    'Sesuai catatan sebelumnya',
                ],
            ],
            [
                'name' => 'Container Condition',
                'type' => 'information_only',
                'criteria' => [
                    'Bersih',
                    'Tidak bocor',
                    'Lantai kokoh',
                ],
            ],
            [
                'name' => 'Seal Condition',
                'type' => 'information_only',
                'criteria' => [
                    'Kondisi baik',
                    'Nomor sesuai dokumen',
                ],
            ],
        ],
    ],

    // ── Unloading / Stripping ──────────────────────────────────────────────────
    'unloading' => [
        'UNLOADING' => [
            [
                'name' => 'Unit Condition',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak ada kerusakan baru',
                    'Sesuai catatan sebelumnya',
                ],
            ],
            [
                'name' => 'Physical Damage Check',
                'type' => 'major_damage',
                'criteria' => [
                    'Periksa seluruh sisi',
                    'Catat jika ada kerusakan',
                ],
            ],
        ],
    ],

    // ── Selfdrive ──────────────────────────────────────────────────────────────
    'selfdrive' => [
        'SELFDRIVE' => [
            [
                'name' => 'Unit Condition',
                'type' => 'major_damage',
                'criteria' => [
                    'Layak dikendarai',
                    'Tidak ada kerusakan berbahaya',
                ],
            ],
            [
                'name' => 'Fuel Check',
                'type' => 'information_only',
                'criteria' => [
                    'Catat level BBM',
                    'Sesuai kebutuhan perjalanan',
                ],
            ],
        ],
    ],

    // ── Dooring (PDC Tujuan) ───────────────────────────────────────────────────
    'dooring' => [
        'FINAL' => [
            [
                'name' => 'Unit Condition',
                'type' => 'major_damage',
                'criteria' => [
                    'Tidak ada kerusakan baru',
                    'Sesuai standar serah terima',
                ],
            ],
            [
                'name' => 'Customer Acceptance',
                'type' => 'information_only',
                'criteria' => [
                    'Sudah diperiksa customer',
                    'Tidak ada keberatan',
                ],
            ],
            [
                'name' => 'Final Quality',
                'type' => 'major_damage',
                'criteria' => [
                    'Bersih dan rapi',
                    'Checklist lengkap',
                    'Siap diserahkan',
                ],
            ],
        ],
    ],

];

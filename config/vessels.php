<?php

return [
    // Regex => Prefix line
    'lines' => [
        '/\bMERATUS\b/i'  => 'MR',
        '/\bTANTO\b/i'    => 'TT',
        '/\bTEMAS\b/i'    => 'TM',
        '/\bSAMUDERA\b/i' => 'SM',
    ],

    // Override full name -> code (menang paling tinggi)
    'overrides' => [
        // 'KM MERATUS MALINO' => 'MRML',
        // 'KM TANTO SEJAHTERA' => 'TTSE',
    ],

    // Override khusus suffix per line (TT/MR/..)
    // contoh: paksa "TANGGUH" jadi 'G' sehingga "TT" + "G" => "TTG"
    'suffix_overrides' => [
        'TT' => [
            'TANGGUH'   => 'G',
            'JAYA'      => 'J',   // jadikan 1 huruf
        ],
        'MR' => [
            // 'MALINO' => 'ML',  // default sudah ML, override kalau mau beda
        ],
    ],
];

<?php

/**
 * Unit Inspection Templates
 *
 * Konfigurasi template item pemeriksaan per stage dalam journey unit.
 * Digunakan oleh UnitInspectionGenerator saat membuat inspection records.
 *
 * Struktur:
 *   stage → [ category → [ item_name, ... ] ]
 *
 * Stage urutan journey:
 *   pickup → handover_depot → loading → unloading → selfdrive → dooring
 */
return [

    // ── Pickup (PDC Asal) ──────────────────────────────────────────────────────
    'pickup' => [
        'EXTERIOR' => [
            'Lampu Depan',
            'Lampu Belakang',
            'Lampu Sign',
            'Bumper Depan',
            'Bumper Belakang',
            'Emblem',
            'Spion',
            'Ban',
            'Velg',
        ],
        'INTERIOR' => [
            'AC',
            'Radio',
            'Dashboard',
            'Power Window',
        ],
        'DOCUMENT' => [
            'Buku Service',
            'Owner Manual',
        ],
        'ACCESSORIES' => [
            'Toolkit',
            'Dongkrak',
            'Segitiga Pengaman',
        ],
    ],

    // ── Handover Depo ──────────────────────────────────────────────────────────
    // Menggunakan template yang sama dengan pickup (serah terima ke depo)
    'handover_depot' => null,   // null = inherit dari 'pickup' — lihat UnitInspectionGenerator

    // ── Loading / Stuffing ─────────────────────────────────────────────────────
    'loading' => [
        'LOADING' => [
            'Unit Condition',
            'Container Condition',
            'Seal Condition',
        ],
    ],

    // ── Unloading / Stripping ──────────────────────────────────────────────────
    'unloading' => [
        'UNLOADING' => [
            'Unit Condition',
            'Physical Damage Check',
        ],
    ],

    // ── Selfdrive ──────────────────────────────────────────────────────────────
    'selfdrive' => [
        'SELFDRIVE' => [
            'Unit Condition',
            'Fuel Check',
        ],
    ],

    // ── Dooring (PDC Tujuan) ───────────────────────────────────────────────────
    'dooring' => [
        'FINAL' => [
            'Unit Condition',
            'Customer Acceptance',
            'Final Quality',
        ],
    ],

];

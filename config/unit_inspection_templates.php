<?php

/**
 * Unit Inspection Templates
 *
 * Konfigurasi template item pemeriksaan per stage dalam journey unit.
 * Digunakan oleh UnitInspectionGenerator saat membuat inspection records.
 *
 * Struktur:
 *   stage → [ category → [ ['name' => string, 'type' => string], ... ] ]
 *
 * Finding type per item (digunakan oleh InspectionGateEvaluator):
 *   major_damage     = kerusakan fisik → RETURN_TO_PDC jika NG
 *   minor_missing    = item hilang     → ALLOW_WITH_REMARK jika NG
 *   information_only = catatan saja    → tidak mempengaruhi gate decision
 *
 * Stage urutan journey:
 *   pickup → handover_depot → loading → unloading → selfdrive → dooring
 */
return [

    // ── Pickup (PDC Asal) ──────────────────────────────────────────────────────
    'pickup' => [
        'EXTERIOR' => [
            ['name' => 'Lampu Depan',    'type' => 'major_damage'],
            ['name' => 'Lampu Belakang', 'type' => 'major_damage'],
            ['name' => 'Lampu Sign',     'type' => 'major_damage'],
            ['name' => 'Bumper Depan',   'type' => 'major_damage'],
            ['name' => 'Bumper Belakang','type' => 'major_damage'],
            ['name' => 'Emblem',         'type' => 'major_damage'],
            ['name' => 'Spion',          'type' => 'major_damage'],
            ['name' => 'Ban',            'type' => 'major_damage'],
            ['name' => 'Velg',           'type' => 'major_damage'],
        ],
        'INTERIOR' => [
            ['name' => 'AC',           'type' => 'major_damage'],
            ['name' => 'Radio',        'type' => 'major_damage'],
            ['name' => 'Dashboard',    'type' => 'major_damage'],
            ['name' => 'Power Window', 'type' => 'major_damage'],
        ],
        'DOCUMENT' => [
            ['name' => 'Buku Service',  'type' => 'minor_missing'],
            ['name' => 'Owner Manual',  'type' => 'minor_missing'],
        ],
        'ACCESSORIES' => [
            ['name' => 'Toolkit',            'type' => 'minor_missing'],
            ['name' => 'Dongkrak',           'type' => 'minor_missing'],
            ['name' => 'Segitiga Pengaman',  'type' => 'minor_missing'],
        ],
    ],

    // ── Handover Depo ──────────────────────────────────────────────────────────
    // null = inherit template dari 'pickup' — lihat UnitInspectionGenerator
    'handover_depot' => null,

    // ── Loading / Stuffing ─────────────────────────────────────────────────────
    'loading' => [
        'LOADING' => [
            ['name' => 'Unit Condition',      'type' => 'major_damage'],
            ['name' => 'Container Condition', 'type' => 'information_only'],
            ['name' => 'Seal Condition',      'type' => 'information_only'],
        ],
    ],

    // ── Unloading / Stripping ──────────────────────────────────────────────────
    'unloading' => [
        'UNLOADING' => [
            ['name' => 'Unit Condition',    'type' => 'major_damage'],
            ['name' => 'Physical Damage Check', 'type' => 'major_damage'],
        ],
    ],

    // ── Selfdrive ──────────────────────────────────────────────────────────────
    'selfdrive' => [
        'SELFDRIVE' => [
            ['name' => 'Unit Condition', 'type' => 'major_damage'],
            ['name' => 'Fuel Check',     'type' => 'information_only'],
        ],
    ],

    // ── Dooring (PDC Tujuan) ───────────────────────────────────────────────────
    'dooring' => [
        'FINAL' => [
            ['name' => 'Unit Condition',      'type' => 'major_damage'],
            ['name' => 'Customer Acceptance', 'type' => 'information_only'],
            ['name' => 'Final Quality',       'type' => 'major_damage'],
        ],
    ],

];

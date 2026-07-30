<?php

namespace App\Enums;

/**
 * Container Allocation (CA-01) + Stuffing (ST-01) — satu lifecycle Unit
 * yang menyambung dua domain berurutan, BUKAN dua status kompetitif.
 *
 * CA-01 s.d. ReadyForStuffing: keputusan PLANNING (unit ini akan masuk
 * container yang mana). ST-01 menambah SATU state terminal: Stuffed —
 * konfirmasi FISIK bahwa unit benar-benar sudah dimasukkan operator.
 * Ditambahkan di sini (bukan enum baru terpisah) karena ini kelanjutan
 * langsung siklus hidup Unit yang sama, konsisten dengan prinsip "Satu
 * Entitas — Satu Status Aktif" (docs/tam-vehicle/UX-PRINCIPLE-STATUS-
 * DRIVEN-WORKSPACE.md).
 *
 * Sengaja TIDAK ada state "Stuffing" (in-progress) di level Unit — untuk
 * SATU unit fisik, aksi menandai adalah satu langkah atomik (Waiting →
 * Stuffed), tidak ada sub-state yang berarti untuk disimpan. "Sedang
 * berlangsung" adalah properti CONTAINER (agregat banyak unit), dilacak
 * terpisah di ContainerStuffingStatus.
 */
enum UnitAllocationStatus: string
{
    case NotInContainer = 'not_in_container';
    case InContainer = 'in_container';
    case ReadyForStuffing = 'ready_for_stuffing';
    case Stuffed = 'stuffed';

    public function label(): string
    {
        return match ($this) {
            self::NotInContainer => 'Belum Masuk Container',
            self::InContainer => 'Sudah Masuk Container',
            self::ReadyForStuffing => 'Siap Stuffing',
            self::Stuffed => 'Sudah Distuffing',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotInContainer => 'gray',
            self::InContainer => 'warning',
            self::ReadyForStuffing => 'success',
            self::Stuffed => 'success',
        };
    }
}

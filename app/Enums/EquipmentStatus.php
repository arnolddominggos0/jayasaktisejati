<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case Ok = 'ok';
    case NotOk = 'not_ok';
    case New = 'new';
    case Worn = 'worn';
    case Strong = 'strong';
    case Loose = 'loose';
    case Thick = 'thick';
    case Cracked = 'cracked';
    case Stable = 'stable';
    case Unstable = 'unstable';
    case Clean = 'clean';
    case Dirty = 'dirty';
    case Present = 'present';
    case NotPresent = 'not_present';
    case Tight = 'tight';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::NotOk => 'Tidak OK',
            self::New => 'Baru',
            self::Worn => 'Aus',
            self::Strong => 'Kuat',
            self::Loose => 'Longgar',
            self::Thick => 'Tebal',
            self::Cracked => 'Retak',
            self::Stable => 'Stabil',
            self::Unstable => 'Tidak Stabil',
            self::Clean => 'Bersih',
            self::Dirty => 'Kotor',
            self::Present => 'Ada',
            self::NotPresent => 'Tidak Ada',
            self::Tight => 'Kencang',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ok,
            self::New,
            self::Strong,
            self::Thick,
            self::Stable,
            self::Clean,
            self::Present,
            self::Tight => 'success',
            self::Worn => 'warning',
            self::NotOk,
            self::Loose,
            self::Cracked,
            self::Unstable,
            self::Dirty,
            self::NotPresent => 'danger',
        };
    }

    public function isSafe(): bool
    {
        return in_array($this, [
            self::Ok,
            self::New,
            self::Strong,
            self::Thick,
            self::Stable,
            self::Clean,
            self::Present,
            self::Tight,
        ], true);
    }
}

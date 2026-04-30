<?php

namespace App\Enums;

enum FcTaskStatus: string
{
    case TODO       = 'todo';
    case ON_SITE    = 'on_site';
    case DONE       = 'done';
    case EXCEPTION  = 'exception';

    public function label(): string
    {
        return match ($this) {
            self::TODO      => 'Belum Dikerjakan',
            self::ON_SITE   => 'Sedang Dikerjakan',
            self::DONE      => 'Selesai',
            self::EXCEPTION => 'Exception',
        };
    }
}

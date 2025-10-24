<?php

namespace App\Enums;

enum ScheduleState: string
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Final => 'Final',
        };
    }

    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Final->value => 'Final',
        ];
    }
}

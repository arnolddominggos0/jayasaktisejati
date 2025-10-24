<?php

namespace App\Enums;

enum ScheduleState: string
{
    case Draft = 'draft';
    case Final = 'final';
<<<<<<< HEAD

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
=======
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
}

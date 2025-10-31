<?php

namespace App\Enums;

enum ScheduleState: string
{
    case Draft = 'draft';
    case Feedback = 'feedback';
    case Final = 'final';

    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Feedback->value => 'Feedback',
            self::Final->value => 'Final',
        ];
    }
}

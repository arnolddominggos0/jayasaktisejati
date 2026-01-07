<?php

namespace App\Enums;

enum ScheduleStatus: string
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

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Feedback => 'Feedback',
            self::Final => 'Final',
        };
    }
}

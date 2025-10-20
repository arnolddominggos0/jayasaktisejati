<?php

namespace App\Enums;

enum ScheduleState: string
{
    case Draft = 'draft';
    case Final = 'final';
}

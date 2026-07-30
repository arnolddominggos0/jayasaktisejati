<?php

namespace App\Enums;

enum ContainerStuffingStatus: string
{
    case Ready = 'ready';
    case Stuffing = 'stuffing';
    case Full = 'full';
    case ReadyLoading = 'ready_loading';

    public function label(): string
    {
        return match ($this) {
            self::Ready => 'Ready',
            self::Stuffing => 'Stuffing',
            self::Full => 'Full',
            self::ReadyLoading => 'Ready Loading',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ready => 'gray',
            self::Stuffing => 'warning',
            self::Full => 'success',
            self::ReadyLoading => 'success',
        };
    }
}

<?php

namespace App\Enums;

enum ContainerStatus: string
{
    case Reserved     = 'reserved';      // slot disetujui carrier
    case PickedUp     = 'picked_up';     // ambil empty
    case GateIn       = 'gate_in';       // gate in terminal
    case Loaded       = 'loaded';        // loaded on vessel
    case Arrived      = 'arrived';       // vessel arrived POD
    case EmptyReturn  = 'empty_return';  // empty returned
    case Cancelled    = 'cancelled';

    public function label(): string
    {
        return str_replace('_', ' ', ucfirst($this->value));
    }

    public function color(): string
    {
        return match($this) {
            self::Reserved    => 'warning',
            self::PickedUp    => 'primary',
            self::GateIn      => 'info',
            self::Loaded      => 'success',
            self::Arrived     => 'success',
            self::EmptyReturn => 'gray',
            self::Cancelled   => 'danger',
        };
    }
}

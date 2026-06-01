<?php

namespace App\Enums;

enum DeliveryScope: string
{
    case PortToPort = 'port_to_port';
    case PortToDoor = 'port_to_door';
    case DoorToPort = 'door_to_port';
    case DoorToDoor = 'door_to_door';

    public function hasOriginDoor(): bool
    {
        return in_array($this, [self::DoorToDoor, self::DoorToPort], true);
    }

    public function hasDestinationDoor(): bool
    {
        return in_array($this, [self::DoorToDoor, self::PortToDoor], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PortToPort => 'Port to Port',
            self::PortToDoor => 'Port to Door',
            self::DoorToPort => 'Door to Port',
            self::DoorToDoor => 'Door to Door',
        };
    }

    public static function options(): array
    {
        return [
            self::PortToPort->value => self::PortToPort->label(),
            self::PortToDoor->value => self::PortToDoor->label(),
            self::DoorToPort->value => self::DoorToPort->label(),
            self::DoorToDoor->value => self::DoorToDoor->label(),
        ];
    }

    public static function short(?self $v): ?string
    {
        return match ($v) {
            self::PortToPort => 'P2P',
            self::PortToDoor => 'P2D',
            self::DoorToPort => 'D2P',
            self::DoorToDoor => 'D2D',
            default => null,
        };
    }
}

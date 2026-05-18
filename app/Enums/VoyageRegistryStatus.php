<?php

namespace App\Enums;

enum VoyageRegistryStatus: string
{
    case DRAFT     = 'draft';
    case PLANNED   = 'planned';
    case ACTIVE    = 'active';
    case DELAYED   = 'delayed';
    case COMPLETED = 'completed';
    case CLOSED    = 'closed';
    case ARCHIVED  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Rancangan',
            self::PLANNED   => 'Direncanakan',
            self::ACTIVE    => 'Aktif',
            self::DELAYED   => 'Tertunda',
            self::COMPLETED => 'Selesai',
            self::CLOSED    => 'Ditutup',
            self::ARCHIVED  => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::PLANNED   => 'info',
            self::ACTIVE    => 'primary',
            self::DELAYED   => 'warning',
            self::COMPLETED => 'success',
            self::CLOSED    => 'danger',
            self::ARCHIVED  => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::CLOSED, self::ARCHIVED], true);
    }

    public function canTransitionTo(self $new): bool
    {
        $transitions = [
            self::DRAFT->value     => [self::PLANNED->value],
            self::PLANNED->value   => [self::ACTIVE->value, self::DELAYED->value, self::CLOSED->value],
            self::ACTIVE->value    => [self::DELAYED->value, self::COMPLETED->value],
            self::DELAYED->value   => [self::ACTIVE->value, self::COMPLETED->value, self::CLOSED->value],
            self::COMPLETED->value => [self::CLOSED->value, self::ARCHIVED->value],
            self::CLOSED->value    => [self::ARCHIVED->value],
            self::ARCHIVED->value  => [],
        ];

        return in_array($new->value, $transitions[$this->value] ?? [], true);
    }
}

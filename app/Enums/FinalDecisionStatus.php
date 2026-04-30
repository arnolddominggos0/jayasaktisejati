<?php

namespace App\Enums;

enum FinalDecisionStatus: string
{
    case Go = 'go';
    case Warning = 'warning';
    case Stop = 'stop';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Go => 'Lanjut Loading',
            self::Warning => 'Peringatan - Butuh Approval',
            self::Stop => 'STOP - Tidak Boleh Loading',
            self::Pending => 'Menunggu Keputusan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Go,
            self::Approved => 'success',
            self::Warning => 'warning',
            self::Stop,
            self::Rejected => 'danger',
            self::Pending => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Go => 'heroicon-o-check-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Stop => 'heroicon-o-hand-raised',
            self::Pending => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-badge',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }

    public function canProceed(): bool
    {
        return in_array($this, [self::Go, self::Approved], true);
    }

    public function isCritical(): bool
    {
        return $this === self::Stop;
    }
}

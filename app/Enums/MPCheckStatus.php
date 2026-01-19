<?php

namespace App\Enums;

enum MPCheckStatus: string
{
    case Draft = 'draft';
    case OnCheck = 'on_check';
    case WaitingAction = 'waiting_action';
    case Failed = 'failed';
    case Cleared = 'cleared';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::OnCheck => 'Dalam Pemeriksaan',
            self::WaitingAction => 'Menunggu Tindakan',
            self::Failed => 'Gagal',
            self::Cleared => 'Selesai',
            self::Approved => 'Disetujui',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::OnCheck => 'info',
            self::WaitingAction => 'warning',
            self::Failed => 'danger',
            self::Cleared => 'success',
            self::Approved => 'primary',
        };
    }
}

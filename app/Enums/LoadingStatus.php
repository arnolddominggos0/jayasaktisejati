<?php

namespace App\Enums;

enum LoadingStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case MpAttendanceCheck = 'mp_attendance_check';
    case HealthCheck = 'health_check';
    case ApdCheck = 'apd_check';
    case EquipmentCheck = 'equipment_check';
    case RackContainerCheck = 'rack_container_check';
    case UnitCheck = 'unit_check';
    case StockApdCheck = 'stock_apd_check';
    case ManpowerAvailabilityCheck = 'manpower_availability_check';
    case FinalDecision = 'final_decision';
    case Completed = 'completed';
    case Stopped = 'stopped';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'Sedang Berjalan',
            self::MpAttendanceCheck => 'Cek Kehadiran MP',
            self::HealthCheck => 'Cek Kesehatan',
            self::ApdCheck => 'Cek APD',
            self::EquipmentCheck => 'Cek Alat',
            self::RackContainerCheck => 'Cek Rack Container',
            self::UnitCheck => 'Cek Unit',
            self::StockApdCheck => 'Cek Stok APD',
            self::ManpowerAvailabilityCheck => 'Cek Ketersediaan MP',
            self::FinalDecision => 'Keputusan Final',
            self::Completed => 'Selesai',
            self::Stopped => 'Dihentikan',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InProgress => 'info',
            self::MpAttendanceCheck,
            self::HealthCheck,
            self::ApdCheck,
            self::EquipmentCheck,
            self::RackContainerCheck,
            self::UnitCheck,
            self::StockApdCheck,
            self::ManpowerAvailabilityCheck => 'warning',
            self::FinalDecision => 'primary',
            self::Completed => 'success',
            self::Stopped,
            self::Rejected => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-document',
            self::InProgress => 'heroicon-o-play',
            self::MpAttendanceCheck => 'heroicon-o-users',
            self::HealthCheck => 'heroicon-o-heart',
            self::ApdCheck => 'heroicon-o-shield-check',
            self::EquipmentCheck => 'heroicon-o-wrench',
            self::RackContainerCheck => 'heroicon-o-cube',
            self::UnitCheck => 'heroicon-o-truck',
            self::StockApdCheck => 'heroicon-o-clipboard-document-list',
            self::ManpowerAvailabilityCheck => 'heroicon-o-user-group',
            self::FinalDecision => 'heroicon-o-check-circle',
            self::Completed => 'heroicon-o-flag',
            self::Stopped => 'heroicon-o-hand-raised',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }
}

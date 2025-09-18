<?php

namespace App\Enums;

enum TrackStatus: string
{
    case Pickup              = 'pickup';
    case Handover            = 'handover';
    case Stuffing            = 'stuffing';
    case DeliveryToPort      = 'delivery_to_port';
    case Stacking            = 'stacking';
    case UnitLoading         = 'unit_loading';
    case OnShip              = 'onship';
    case VesselDepart        = 'vessel_depart';
    case VesselArrival       = 'vessel_arrival';
    case Unloading           = 'unloading';
    case DeliveryToCustomer  = 'delivery_to_customer';
    case Delivered           = 'delivered';

    case Hold                = 'hold';
    case Cancelled           = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pickup             => 'Penjemputan',
            self::Handover           => 'Handover Depo JSS',
            self::Stuffing           => 'Stuffing & Segel',
            self::DeliveryToPort     => 'Antar ke Pelabuhan',
            self::Stacking           => 'Stacking (Terminal)',
            self::UnitLoading        => 'Dimuat di Kapal',
            self::OnShip             => 'On Ship',
            self::VesselDepart       => 'Kapal Berangkat',
            self::VesselArrival      => 'Kapal Tiba',
            self::Unloading          => 'Pembongkaran',
            self::DeliveryToCustomer => 'Antar ke Customer',
            self::Delivered          => 'Terkirim',
            self::Hold               => 'Ditahan',
            self::Cancelled          => 'Dibatalkan',
        };
    }

    /** Urutan tahapan normal (progress bar pelanggan) */
    public static function order(): array
    {
        return [
            self::Pickup,
            self::Handover,
            self::Stuffing,
            self::DeliveryToPort,
            self::Stacking,
            self::UnitLoading,
            self::OnShip,
            self::VesselDepart,
            self::VesselArrival,
            self::Unloading,
            self::DeliveryToCustomer,
            self::Delivered,
        ];
    }

    public static function finished(): array
    {
        return [self::Delivered, self::Cancelled];
    }

    public static function inTransit(): array
    {
        return array_values(array_diff(self::order(), [self::Delivered]));
    }
}

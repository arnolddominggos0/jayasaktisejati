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

    public static function orderSea(): array
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

    public static function orderLand(): array
    {
        return [
            self::Pickup,
            self::DeliveryToCustomer,
            self::Delivered,
        ];
    }

    // AMAN DARI NULL: terima nilai campur aduk, normalize ke string, pakai alias sederhana
    public static function orderForMode($mode): array
    {
        $val = $mode instanceof \BackedEnum ? strtolower((string) $mode->value) : strtolower((string) $mode);
        $landAliases = ['land', 'land_trucking', 'car_carrier', 'towing', 'truck'];

        return in_array($val, $landAliases, true)
            ? self::orderLand()
            : self::orderSea(); // default ke sea kalau ragu
    }

    public static function optionsForMode($mode): array
    {
        $list = self::orderForMode($mode);
        $out = [];
        foreach ($list as $s) {
            $out[$s->value] = $s->label();
        }
        return $out;
    }

    public function toShipmentStatus(): ?ShipmentStatus
    {
        return match ($this) {
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
            self::DeliveryToCustomer => ShipmentStatus::Transit,
            self::Delivered          => ShipmentStatus::Delivered,
            self::Hold               => ShipmentStatus::Hold,
            self::Cancelled          => ShipmentStatus::Cancelled,
        };
    }

    public static function finished(): array
    {
        return [self::Delivered, self::Cancelled];
    }

    public static function inTransitSea(): array
    {
        return array_values(array_diff(self::orderSea(), [self::Delivered]));
    }

    public static function inTransitLand(): array
    {
        return array_values(array_diff(self::orderLand(), [self::Delivered]));
    }
}

<?php

namespace App\Enums;

use App\Enums\ShipmentStatus;

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

    case HandoverTrucking    = 'handover_trucking';

    case DeliveryToCustomer  = 'delivery_to_customer';
    case Delivered           = 'delivered';
    case Hold                = 'hold';
    case Cancelled           = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pickup             => 'Penjemputan',
            self::Handover           => 'Handover Depo',
            self::Stuffing           => 'Stuffing & Segel',
            self::DeliveryToPort     => 'Antar ke Pelabuhan',
            self::Stacking           => 'Stacking (Terminal)',
            self::UnitLoading        => 'Dimuat di Kapal',
            self::OnShip             => 'On Ship',
            self::VesselDepart       => 'Kapal Berangkat',
            self::VesselArrival      => 'Kapal Tiba',
            self::Unloading          => 'Pembongkaran',
            self::HandoverTrucking   => 'Handover Self-Drive',
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
            self::HandoverTrucking,
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

    public static function orderForMode($mode): array
    {
        $val = $mode instanceof \BackedEnum ? strtolower((string) $mode->value) : strtolower((string) $mode);
        $landAliases = ['land', 'land_trucking', 'car_carrier', 'towing', 'truck'];

        return in_array($val, $landAliases, true) ? self::orderLand() : self::orderSea();
    }

    public function toShipmentStatus(): ?ShipmentStatus
    {
        return match ($this) {
            self::Delivered => ShipmentStatus::Delivered,
            self::Hold      => ShipmentStatus::Hold,
            self::Cancelled => ShipmentStatus::Cancelled,
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
            self::HandoverTrucking,
            self::DeliveryToCustomer => ShipmentStatus::Transit,
        };
    }

    public static function normalize(null|string|\BackedEnum|self $val): ?self
    {
        if ($val instanceof self) {
            return $val;
        }

        if ($val instanceof \BackedEnum) {
            $val = (string) $val->value;
        }

        if ($val === null) {
            return null;
        }

        $key = strtolower(trim((string) $val));

        if ($case = self::tryFrom($key)) {
            return $case;
        }

        $map = [
            'stuffing_start'    => self::Stuffing,
            'stuffing_briefing' => self::Stuffing,
            'stuffing_done'     => self::Stuffing,
            'port_in'           => self::DeliveryToPort,
            'vessel_atd'        => self::VesselDepart,
            'vessel_ata'        => self::VesselArrival,
            'stripping_start'   => self::Unloading,
            'stacking_start'    => self::Stacking,
            'handover_self_drive' => self::HandoverTrucking,
        ];

        return $map[$key] ?? null;
    }

    public function toNormalizedValue(): int
    {
        return match ($this) {
            self::Pickup              => 10,
            self::Handover            => 20,
            self::Stuffing            => 30,
            self::DeliveryToPort      => 40,
            self::Stacking            => 50,
            self::UnitLoading         => 60,
            self::OnShip              => 70,
            self::VesselDepart        => 80,
            self::VesselArrival       => 90,
            self::Unloading           => 100,
            self::HandoverTrucking    => 105,
            self::DeliveryToCustomer  => 110,
            self::Delivered           => 120,
            self::Hold                => 900,
            self::Cancelled           => 999,
        };
    }

    public static function simplifiedForMode($mode, ?array $mask = null): array
    {
        $mask = array_merge([
            'show_planning'        => true,
            'show_terminal_detail' => true,
            'show_legacy'          => false,
        ], $mask ?? []);

        $order = self::orderForMode($mode);

        if (! $mask['show_planning']) {
            $order = array_values(array_filter($order, fn(self $s) => ! in_array($s, [
                self::Handover,
                self::Stuffing,
                self::DeliveryToPort,
                self::Stacking,
            ], true)));
        }

        if (! $mask['show_terminal_detail']) {
            $order = array_values(array_filter($order, fn(self $s) => ! in_array($s, [
                self::UnitLoading,
                self::VesselDepart,
                self::VesselArrival,
            ], true)));
        }

        if (! $mask['show_legacy']) {
        }

        return $order;
    }

    public static function finished(): array
    {
        return [self::Delivered, self::Cancelled];
    }

    public static function inTransitSea(): array
    {
        return array_values(array_diff(self::orderSea(), [self::Delivered, self::Cancelled]));
    }

    public static function inTransitLand(): array
    {
        return array_values(array_diff(self::orderLand(), [self::Delivered, self::Cancelled]));
    }

    /**
     * Determine if this status is a sea-specific milestone.
     * Used to conditionally display sea-only tracking columns.
     */
    public function isSeaMilestone(): bool
    {
        return in_array($this, [
            self::OnShip,
            self::VesselDepart,
            self::VesselArrival,
            self::UnitLoading,
            self::Stacking,
            self::DeliveryToPort,
        ], true);
    }

    /**
     * Determine if this status requires a note to be provided.
     * Critical statuses like Hold and Cancelled always require notes.
     */
    public function requiresNote(): bool
    {
        return in_array($this, [
            self::Hold,
            self::Cancelled,
        ], true);
    }

    /**
     * Check if this is a critical status that affects shipment flow.
     */
    public function isCriticalStatus(): bool
    {
        return in_array($this, [
            self::Hold,
            self::Cancelled,
            self::Delivered,
        ], true);
    }
}

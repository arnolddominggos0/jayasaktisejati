<?php

namespace App\ViewModels\Monitoring;

use Illuminate\Support\Carbon;

final readonly class AdministrativeInfo
{
    public function __construct(
        public readonly ?string $vessel_name,
        public readonly ?string $voyage_no,
        public readonly ?Carbon $etd,
        public readonly ?Carbon $eta,
        public readonly ?string $pol_name,
        public readonly ?string $pod_name,
        public readonly ?string $driver_name,
        public readonly ?string $vehicle_plate,
        public readonly ?string $priority,
        public readonly ?Carbon $requested_at,
        public readonly ?Carbon $delivered_at,
        public readonly ?string $pic_name,
        public readonly ?string $pic_phone,
        public readonly ?Carbon $last_tracked_at,
    ) {}

    public static function empty(): self
    {
        return new self(
            vessel_name: null,
            voyage_no: null,
            etd: null,
            eta: null,
            pol_name: null,
            pod_name: null,
            driver_name: null,
            vehicle_plate: null,
            priority: null,
            requested_at: null,
            delivered_at: null,
            pic_name: null,
            pic_phone: null,
            last_tracked_at: null,
        );
    }
}
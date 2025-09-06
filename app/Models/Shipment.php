<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentMode;
use App\Enums\ServiceType;
use App\Enums\CargoType;
use BackedEnum;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_id',
        'origin_office_id',
        'destination_office_id',

        // A. Data Customer & Dokumen
        'pic_name',
        'pic_phone',
        'request_type',
        'doc_number',
        'priority',
        'requested_at',
        'attachments',

        // B. Informasi Rute & Moda
        'mode',
        'route_from',
        'route_to',
        'route_summary',

        // Layanan & Muatan
        'service_type',     // enum: sea_freight|land_trucking|car_carrier (kategori)
        'service_option',   // string: fcl|lcl|truck|towing|car_carrier (opsi)
        'cargo_type',       // enum: vehicle|general

        // Laut
        'vessel_name',
        'voyage',
        'pol',
        'pod',
        'etd',
        'eta',

        // Darat
        'vehicle_type',
        'vehicle_plate',
        'pickup_date',
        'driver_name',
        'driver_phone',

        // Umum
        'status',
        'notes',

        // Konfirmasi
        'confirm_is_true',
    ];

    protected $casts = [
        'status'          => ShipmentStatus::class,
        'mode'            => ShipmentMode::class,
        'service_type'    => ServiceType::class,
        'cargo_type'      => CargoType::class,
        'requested_at'    => 'datetime',
        'attachments'     => 'array',
        'etd'             => 'datetime',
        'eta'             => 'datetime',
        'pickup_date'     => 'datetime',
        'confirm_is_true' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            if (empty($shipment->status)) {
                $shipment->status = ShipmentStatus::default();
            }
            if (empty($shipment->code)) {
                $attempts = 0;
                do {
                    $shipment->code = self::generateCode();
                    $exists = self::where('code', $shipment->code)->exists();
                    $attempts++;
                } while ($exists && $attempts < 3);
            }
        });

        static::saving(function (Shipment $m) {
            // Normalisasi kategori & opsi layanan
            if ($m->mode === ShipmentMode::Sea) {
                $m->service_type = ServiceType::SeaFreight;

                // opsi sea: fcl/lcl
                $m->service_option = in_array($m->service_option, ['fcl', 'lcl'], true)
                    ? $m->service_option
                    : ($m->service_option ?: 'fcl');

                // bersihkan field darat
                $m->vehicle_type = null;
                $m->vehicle_plate = null;
                $m->driver_name = null;
                $m->driver_phone = null;
                $m->pickup_date = null;

            } else {
                // Land
                $m->service_type = $m->vehicle_type === 'car_carrier'
                    ? ServiceType::CarCarrier
                    : ServiceType::LandTrucking;

                // opsi land turunan dari vehicle_type
                $m->service_option = match ($m->vehicle_type) {
                    'car_carrier' => 'car_carrier',
                    'towing'      => 'towing',
                    default       => 'truck',
                };

                // bersihkan field laut
                $m->vessel_name = null;
                $m->voyage = null;
                $m->pol = null;
                $m->pod = null;
                $m->etd = null;
                $m->eta = null;
            }

            // ringkasan rute
            $middle = $m->mode === ShipmentMode::Sea
                ? strtoupper($m->service_option) // FCL/LCL
                : ucfirst(str_replace('_', ' ', $m->service_option)); // Truck/Towing/Car carrier

            $m->route_summary = implode(' → ', array_filter([
                optional($m->originOffice)->name,
                $middle,
                optional($m->destinationOffice)->name,
            ]));
        });
    }

    public static function generateCode(?int $year = null): string
    {
        $year = $year ?: now()->year;
        $prefix = "JSS-$year-";

        $last = static::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('code');

        $next = 1;
        if ($last) {
            $num = (int) substr($last, strrpos($last, '-') + 1);
            $next = $num + 1;
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // Helpers UI agar selalu string
    public function getStatusValueAttribute(): string
    {
        $s = $this->status;
        return $s instanceof BackedEnum ? $s->value : (string) $s;
    }
    public function getModeValueAttribute(): ?string
    {
        return $this->mode instanceof BackedEnum ? $this->mode->value : ($this->mode ?: null);
    }
    public function getServiceTypeValueAttribute(): ?string
    {
        return $this->service_type instanceof BackedEnum ? $this->service_type->value : ($this->service_type ?: null);
    }

    public function scopeInProgress($q)
    {
        return $q->whereIn('status', ShipmentStatus::inProgress());
    }

    // Relations
    public function customer()          { return $this->belongsTo(Customer::class); }
    public function originOffice()      { return $this->belongsTo(Office::class, 'origin_office_id'); }
    public function destinationOffice() { return $this->belongsTo(Office::class, 'destination_office_id'); }
}

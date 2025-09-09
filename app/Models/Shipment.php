<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentMode;
use App\Enums\ServiceType;
use App\Enums\CargoType;
use BackedEnum;
use Illuminate\Support\Carbon;

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
        'attachments',


        // B. Informasi Rute & Moda
        'mode',
        'route_from',
        'route_to',
        'route_summary',

        // Layanan & Muatan
        'service_type',
        'service_option',
        'cargo_type',

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

        //Perubahan
        'requested_at',
        'cancelled_at',
        'cancelled_by',
        'edited_fields',
        'last_edited_by',

        // Konfirmasi
        'confirm_is_true',

        // Lead Time TL
        'estimated_ready_at',

    ];

    protected $casts = [
        'status'            => ShipmentStatus::class,
        'mode'              => ShipmentMode::class,
        'service_type'      => ServiceType::class,
        'cargo_type'        => CargoType::class,
        'requested_at'      => 'datetime',
        'attachments'       => 'array',
        'etd'               => 'datetime',
        'eta'               => 'datetime',
        'pickup_date'       => 'datetime',
        'estimated_ready_at' => 'datetime',
        'confirm_is_true'   => 'boolean',
        'cancelled_at'      => 'datetime',
        'edited_fields'     => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $m) {
            $mode = $m->mode instanceof \BackedEnum ? $m->mode->value : (string)$m->mode;

            if (blank($m->code)) {
                $m->code = self::generateCode($mode); // JSSMMYYSH/TC####
            }

            if (($m->request_type ?? null) === 'walk_in' && blank($m->doc_number)) {
                $m->doc_number = 'AUTO-' . now()->format('Ymd-His');
            }

            if (blank($m->eta)) {
                $modeCode = match (strtolower($mode)) {
                    'sea', 'sea_freight' => 'SH',
                    default             => 'TC',
                };
                $m->eta = self::computeEta($modeCode, (string)($m->priority ?? 'normal'))->toDateTimeString();
            }
        });

        static::saving(function (Shipment $m) {
            if ($m->mode === ShipmentMode::Sea) {
                $m->service_type   = ServiceType::SeaFreight;
                $m->service_option = in_array($m->service_option, ['fcl', 'lcl'], true) ? $m->service_option : ($m->service_option ?: 'fcl');

                // DARAT fields off
                $m->vehicle_type = null;
                $m->vehicle_plate = null;
                $m->driver_name = null;
                $m->driver_phone = null;
                $m->pickup_date = null;
                $m->estimated_ready_at = null;
            } else { // LAND
                $m->service_type   = $m->vehicle_type === 'car_carrier'
                    ? ServiceType::CarCarrier
                    : ServiceType::LandTrucking;
                $m->service_option = match ($m->vehicle_type) {
                    'car_carrier' => 'car_carrier',
                    'towing'      => 'towing',
                    default       => 'truck',
                };
                $m->vessel_name = $m->voyage = $m->pol = $m->pod = null;
                $m->etd = null;
                $m->schedule_id = null;
            }

            // Ringkasan rute
            $middle = $m->mode === ShipmentMode::Sea
                ? strtoupper((string)$m->service_option)
                : ucfirst(str_replace('_', ' ', (string)$m->service_option));
            $m->route_summary = implode(' → ', array_filter([
                optional($m->originOffice)->name,
                $middle,
                optional($m->destinationOffice)->name,
            ]));
        });

        static::updated(function (Shipment $m) {
            $changed = array_keys($m->getChanges());
            $ignore  = ['updated_at', 'created_at', 'edited_fields', 'last_edited_by'];
            $changed = array_values(array_diff($changed, $ignore));
            if ($changed) {
                $m->forceFill([
                    'edited_fields'  => $changed,
                    'last_edited_by' => auth()->id(),
                ])->saveQuietly();
            }
        });
    }


    public static function generateCode(?string $mode = null, ?int $year = null, ?int $month = null): string
    {
        $now   = now();
        $year  = $year ?: $now->year;
        $month = $month ?: $now->month;

        // Prefix JSS + bulan + tahun → JSSMMYY
        $prefix = 'JSS' . str_pad($month, 2, '0', STR_PAD_LEFT) . substr($year, -2);

        // Kode moda: SH = Sea, TC = Truck/Car (land)
        $modeCode = match (strtolower((string) $mode)) {
            'sea', 'sea_freight' => 'SH',
            'land', 'land_trucking', 'car_carrier', 'towing', 'truck' => 'TC',
            default => 'XX',
        };

        $prefix .= $modeCode;

        // Cari last sequence bulan+mode ini
        $last = static::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if ($last && preg_match('/^' . $prefix . '(\d{4})$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    public static function computeEta(string $modeCode, string $priority, ?Carbon $base = null): Carbon
    {
        $base = $base?->copy() ?? now();
        if ($modeCode === 'SH') { // LAUT
            $days = strtolower($priority) === 'urgent' ? 17 : 19;
            return $base->addDays($days)->endOfDay();
        }
        // DARAT
        return strtolower($priority) === 'urgent' ? $base->endOfDay() : $base->addDay()->endOfDay();
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
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function schedule()
    {
        return $this->belongsTo(FleetSchedule::class, 'schedule_id');
    }
    // public function driver()
    // {
    //     return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    // }
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function originOffice()
    {
        return $this->belongsTo(Office::class, 'origin_office_id');
    }
    public function destinationOffice()
    {
        return $this->belongsTo(Office::class, 'destination_office_id');
    }
}
